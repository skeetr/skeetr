<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Runtime\Overrides;

use Skeetr\Runtime\Override;

/**
 * PHP impementation of session support functions
 *
 * Built-in functions:
 * [+] session_cache_expire — Return current cache expire
 * [+] session_cache_limiter — Get and/or set the current cache limiter
 * [+] session_commit — Alias of session_write_close
 * [+] session_decode — Decodes session data from a session encoded string
 * [+] session_destroy — Destroys all data registered to a session
 * [+] session_encode — Encodes the current session data as a session encoded string
 * [+] session_get_cookie_params — Get the session cookie parameters
 * [+] session_id — Get and/or set the current session id
 * [*] session_is_registered — Find out whether a global variable is registered in a session
 * [ ] session_module_name — Get and/or set the current session module
 * [=] session_name — Get and/or set the current session name
 * [+] session_regenerate_id — Update the current session id with a newly generated one
 * [*] session_register_shutdown — Session shutdown function
 * [*] session_register — Register one or more global variables with the current session
 * [=] session_save_path — Get and/or set the current session save path
 * [+] session_set_cookie_params — Set the session cookie parameters
 * [ ] session_set_save_handler — Sets user-level session storage functions
 * [+] session_start — Start new or resume existing session
 * [+] session_status — Returns the current session status
 * [*] session_unregister — Unregister a global variable from the current session
 * [+] session_unset — Free all session variables
 * [+] session_write_close — Write session data and end session
 *
 * [+] = Implemented [=] = Original [*] = Deprecated [ ] = TODO
 * @link http://www.php.net/manual/en/ref.session.php
 *
 * Built-in ini settings:
 * [ ] session.auto_start
 * [i] session.bug_compat_42
 * [i] session.bug_compat_warn
 * [+] session.cache_expire
 * [+] session.cache_limiter
 * [+] session.cookie_domain
 * [+] session.cookie_httponly
 * [+] session.cookie_lifetime
 * [+] session.cookie_path
 * [+] session.cookie_secure
 * [i] session.entropy_file
 * [i] session.entropy_length
 * [ ] session.gc_divisor
 * [ ] session.gc_maxlifetime
 * [ ] session.gc_probability
 * [?] session.hash_bits_per_character
 * [ ] session.hash_function
 * [+] session.name
 * [?] session.referer_check
 * [i] session.save_handler
 * [+] session.save_path
 * [i] session.serialize_handler
 * [?] session.use_cookies
 * [?] session.use_only_cookies
 * [?] session.use_trans_sid
 *
 * [+] = Used [i] = Ignored [?] = What? [ ] = TODO
 * @link http://www.php.net/manual/en/session.configuration.php
 *
 * TODO:
 * Implements gc
 * Generate correct ID's
 * Serialization session style
 * Save handlers
 */
class Session extends Override
{
    private static $started;
    private static $file;
    private static $id;
    private static $limiter;
    private static $handler;

    /**
     * Start new or resume existing session
     *
     * @link http://www.php.net/manual/en/function.session-start.php
     */
    final public static function session_start()
    {
        if ( self::$started ) trigger_error('Session already started', E_USER_WARNING);

        self::$started = true;

        if ( isset($_COOKIE[session_name()]) ) {
            self::$id = $_COOKIE[session_name()];
        } else {
            self::$id = uniqid();

            $config = self::session_get_cookie_params();
            Cookie::setcookie(
                session_name(), self::$id,
                $config['lifetime'], $config['path'], $config['domain'],
                $config['secure'], $config['httponly']
            );
        }

        if ( !$savePath = session_save_path() ) $savePath = sys_get_temp_dir();
        if ( !is_dir($savePath) ) mkdir($savePath, 0777);

        self::$file = sprintf('%s/sess_%s', $savePath, self::$id);

        if (self::$handler) {
            self::$handler->open($savePath, session_name());
            self::$handler->read(self::$id);
        }

        if ( file_exists(self::$file) ) {
            $data = file_get_contents(self::$file);
            session_decode($data);
        }

        return true;
    }

    /**
     * Update the current session id with a newly generated one
     *
     * @link http://www.php.net/manual/en/function.session-regenerate-id.php
     *
     * @param  boolean $delete_old_session (optional) Whether to delete the old associated session file or not.
     * @return boolean
     */
    final public static function session_regenerate_id($delete_old_session = false)
    {
        if ( !self::$started ) return false;

        if ( $delete_old_session ) self::session_destroy();
        else {
            self::$started = null;
            self::$file = null;
            self::$id = null;
        }

        if ( isset($_COOKIE[session_name()]) ) unset($_COOKIE[session_name()]);
        return self::session_start();
    }

    /**
     * Get and/or set the current session id
     *
     * @link http://www.php.net/manual/en/function.session-id.php
     *
     * @param  boolean $id (optional) If id is specified, it will replace the current session id.
     * @return string  returns the session id for the current session or the empty string ("") if there is no current session
     */
    final public static function session_id($id = null)
    {
        if ( $id ) self::$id = $id;
        return self::$id;
    }

    /**
     * Write session data and end session
     *
     * @link http://www.php.net/manual/en/function.session-write-close.php
     */
    final public static function session_write_close()
    {
        if ( !self::$started ) return;

        if (self::$handler) {
            self::$handler->write(self::$id, self::session_encode());
            self::$handler->close();
        }

        self::$started = false;
        file_put_contents(self::$file, self::session_encode());
    }

   /**
     * Free all session variables
     *
     * @link http://www.php.net/manual/en/function.session-unset.php
     */
    final public static function session_unset()
    {
        if ( !isset($_SESSION) ) return false;

        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Returns the current session status
     *
     * @link http://www.php.net/manual/en/function.session-status.php
     *
     * @return integer PHP_SESSION_NONE if sessions are enabled, but none exists and PHP_SESSION_ACTIVE if sessions are enabled, and one exists.
     */
    final public static function session_status()
    {
        if ( self::$started ) return PHP_SESSION_ACTIVE;
        return PHP_SESSION_NONE;
    }

    /**
     * Destroys all data registered to a session
     *
     * @link http://www.php.net/manual/en/function.session-destroy.php
     *
     * @return boolean
     */
    final public static function session_destroy()
    {
        if ( !self::$started ) return false;
        self::reset();

        if ( file_exists(self::$file) ) unlink(self::$file);
        return true;
    }

    /**
     * Encodes the current session data as a session encoded string
     *
     * @todo implement standard PHP session serailization
     * @link http://www.php.net/manual/en/function.session-encode.php
     *
     * @return string Returns the contents of the current session encoded.
     */
    final public static function session_encode()
    {
        return serialize($_SESSION);
    }

    /**
     * Decodes session data from a session encoded string
     *
     * @link http://www.php.net/manual/en/function.session-decode.php
     * @todo implement standard PHP session serailization
     *
     * @param  string  $data The encoded data to be stored.
     * @return boolean
     */
    final public static function session_decode($data)
    {
        if ( !$values = unserialize($data) ) return false;
        foreach ($values as $key => $value) {
            $_SESSION[$key] = $value;
        }

        return true;
    }

    /**
     * Get the session cookie parameters
     *
     * @link http://www.php.net/manual/en/function.session-get-cookie-params.php
     *
     * @return array Returns an array with the current session cookie information
     */
    final public static function session_get_cookie_params()
    {
        return array(
            'lifetime' => (int) ini_get('session.cookie_lifetime'),
            'path' => ini_get('session.cookie_path'),
            'domain' => ini_get('session.cookie_domain'),
            'secure' => (boolean) ini_get('session.cookie_secure'),
            'httponly' => (boolean) ini_get('session.cookie_httponly')
        );
    }

    /**
     * Defines a cookie to be sent along with the rest of the HTTP headers.
     *
     * @link http://www.php.net/manual/en/function.session-set-cookie-params.php
     *
     * @param  integer $lifetime (optional) Lifetime of the session cookie, defined in seconds.
     * @param  string  $path     (optional) The path on the server in which the cookie will be available on.
     * @param  string  $domain   (optional) The domain that the cookie is available to.
     * @param  boolean $secure   (optional) Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     * @param  boolean $httponly (optional) When TRUE the cookie will be made accessible only through the HTTP protocol.
     * @return boolean
     */
    final public static function session_set_cookie_params(
        $lifetime = null, $path = null, $domain = null,
        $secure = null, $httponly = null
    ) {
        if ( $lifetime !== null ) ini_set('session.cookie_lifetime', (int) $lifetime);
        if ( $path !== null ) ini_set('session.cookie_path', (string) $path);
        if ( $domain !== null ) ini_set('session.cookie_domain', (string) $domain);
        if ( $secure !== null ) ini_set('session.cookie_secure', (boolean) $secure);
        if ( $httponly !== null ) ini_set('session.cookie_httponly', (boolean) $httponly);
    }

    /**
     * Get and/or set the current cache limiter
     *
     * @todo review if this is working, looks like not.
     * @link http://www.php.net/manual/en/function.session-cache-limiter.php
     *
     * @param  string $cache_limiter (optional) If cache_limiter is specified, the name of the current cache limiter is changed to the new value.
     * @return string Returns the name of the current cache limiter.
     */
    final public static function session_cache_limiter($cache_limiter = null)
    {
        if( !$cache_limiter ) return ini_get('session.cache_limiter');
        ini_set('session.cache_limiter', $cache_limiter);

        return $cache_limiter;
    }

    /**
     * Return current cache expire
     *
     * @todo review if this is working.
     * @link http://www.php.net/manual/en/function.session-cache-expire.php
     *
     * @param  string $new_cache_expire (optional) If new_cache_expire is given, the current cache expire is replaced with new_cache_expire.
     * @return string Returns the current setting of session.cache_expire. The value returned should be read in minutes, defaults to 180.
     */
    final public static function session_cache_expire($new_cache_expire = null)
    {
        if( !$new_cache_expire ) return (int) ini_get('session.cache_expire');
        ini_set('session.cache_expire', (int) $new_cache_expire);

        return (int) $new_cache_expire;
    }

    /**
     * Return current cache expire
     *
     * @todo Make 5.3 version
     * @link http://www.php.net/manual/en/function.session-set-save-handler.php
     *
     * @param  SessionHandlerInterface $sessionhandler    An instance of a class implementing SessionHandlerInterface, such as SessionHandler, to register as the session handler. Since PHP 5.4 only.
     * @param  boolean                 $register_shutdown (optional) Register session_write_close() as a register_shutdown_function() function.
     * @return boolean
     */
    final public static function session_set_save_handler($sessionhandler, $register_shutdown = true)
    {
        self::$handler = $sessionhandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function values()
    {
        return get_class_vars(get_called_class());
    }

    /**
     * {@inheritdoc}
     */
    public static function reset()
    {
        self::$started = null;
        self::$file = null;
        self::$id = null;
        self::session_unset();

        $_SESSION = array();
    }
}
