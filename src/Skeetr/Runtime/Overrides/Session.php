<?php
namespace Skeetr\Runtime\Overrides;
use Skeetr\Runtime\Override;
use Skeetr\HTTP\Response;

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
class Session extends Override {
    static private $started;
    static private $file;
    static private $id;
    static private $limiter;
    static private $handler;

    final static public function session_start() {
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

        if ( self::$handler ) {
            self::$handler->open($savePath, session_name());
            self::$handler->read(self::$id);
        }

        if ( file_exists(self::$file) ) {
            $data = file_get_contents(self::$file);
            session_decode($data);
        }

        return true;
    }

    final static public function session_regenerate_id($delete_old_session = false) {
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

    final static public function session_id($id = null) {
        if ( $id ) self::$id = $id;
        return self::$id; 
    }

    final static public function session_write_close() {
        if ( !self::$started ) return;

        if ( self::$handler ) {
            self::$handler->write(self::$id, self::session_encode());
            self::$handler->close();
        }

        file_put_contents(self::$file, self::session_encode());
    }

    final static public function session_unset() {
        if ( !isset($_SESSION) ) return false;

        foreach( $_SESSION as $key => $value ) {
            unset($_SESSION[$key]);
        }
    }

    final static public function session_status() {
        if ( self::$started ) return PHP_SESSION_ACTIVE;
        return PHP_SESSION_NONE;
    }

    final static public function session_destroy() {        
        if ( !self::$started ) return false;
        self::reset();

        if ( file_exists(self::$file) ) unlink(self::$file);
        return true;
    }

    final static public function session_encode() {
        return serialize($_SESSION);
    }

    final static public function session_decode($data) {
        if ( !$values = unserialize($data) ) return false;
        foreach( $values as $key => $value ) {
            $_SESSION[$key] = $value;
        }

        return true;
    }

    final static public function session_get_cookie_params() {
        return array(
            'lifetime' => (int)ini_get('session.cookie_lifetime'),
            'path' => ini_get('session.cookie_path'),
            'domain' => ini_get('session.cookie_domain'),
            'secure' => (boolean)ini_get('session.cookie_secure'),
            'httponly' => (boolean)ini_get('session.cookie_httponly')
        );
    }

    final static public function session_set_cookie_params(
        $lifetime = null, $path = null, $domain = null, 
        $secure = null, $httponly = null
    ) {
        if ( $lifetime !== null ) ini_set('session.cookie_lifetime', (int)$lifetime);
        if ( $path !== null ) ini_set('session.cookie_path', (string)$path);
        if ( $domain !== null ) ini_set('session.cookie_domain', (string)$domain);
        if ( $secure !== null ) ini_set('session.cookie_secure', (boolean)$secure);
        if ( $httponly !== null ) ini_set('session.cookie_httponly', (boolean)$httponly);
    }

    final static public function session_cache_limiter($cache_limiter = null) {
        if( !$cache_limiter ) return ini_get('session.cache_limiter');
        ini_set('session.cache_limiter', $cache_limiter);
        return $cache_limiter;
    }

    final static public function session_cache_expire($new_cache_expire = null) {
        if( !$new_cache_expire ) return (int)ini_get('session.cache_expire');
        ini_set('session.cache_expire', (int)$new_cache_expire);
        return (int)$new_cache_expire;
    }

    final static public function session_set_save_handler($sessionhandler, $register_shutdown = true) {
        self::$handler = $sessionhandler;
    }

    
    static public function values() {
        return get_class_vars(get_called_class());
    }

    static public function reset() {
        self::$started = null;
        self::$file = null;
        self::$id = null;
        self::session_unset();

        $_SESSION = array();
    }

    static public function configure(Response $response) {
        self::session_write_close();
        self::reset();
    }
}