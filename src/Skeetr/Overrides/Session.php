<?php
namespace Skeetr\Overrides;
use Skeetr\HTTP\Response;


/*
+ session_cache_expire — Return current cache expire
+ session_cache_limiter — Get and/or set the current cache limiter
+ session_commit — Alias of session_write_close
+ session_decode — Decodes session data from a session encoded string
+ session_destroy — Destroys all data registered to a session
+ session_encode — Encodes the current session data as a session encoded string
+ session_get_cookie_params — Get the session cookie parameters
+ session_id — Get and/or set the current session id
- session_is_registered — Find out whether a global variable is registered in a session
- session_module_name — Get and/or set the current session module
* session_name — Get and/or set the current session name
+ session_regenerate_id — Update the current session id with a newly generated one
* session_register_shutdown — Session shutdown function
- session_register — Register one or more global variables with the current session
* session_save_path — Get and/or set the current session save path
+ session_set_cookie_params — Set the session cookie parameters
* session_set_save_handler — Sets user-level session storage functions
session_start — Start new or resume existing session
+ session_status — Returns the current session status
- session_unregister — Unregister a global variable from the current session
+ session_unset — Free all session variables
+ session_write_close — Write session data and end session


session.auto_start  "0" PHP_INI_ALL  
session.bug_compat_42   "1" PHP_INI_ALL Disponible desde PHP 4.3.0. Eliminada en PHP 5.4.0.
session.bug_compat_warn "1" PHP_INI_ALL Disponible desde PHP 4.3.0. Eliminada en PHP 5.4.0.
+ session.cache_expire    "180"   PHP_INI_ALL  
+ session.cache_limiter   "nocache"   PHP_INI_ALL  
+ session.cookie_domain   ""  PHP_INI_ALL  
+ session.cookie_httponly ""  PHP_INI_ALL Disponible desde PHP 5.2.0.
+ session.cookie_lifetime "0" PHP_INI_ALL  
+ session.cookie_path "/" PHP_INI_ALL  
+ session.cookie_secure   ""  PHP_INI_ALL Disponible desde PHP 4.0.4.
session.entropy_file    ""  PHP_INI_ALL  
session.entropy_length  "0" PHP_INI_ALL  
session.gc_dividend "100"   PHP_INI_ALL Disponible desde PHP 4.3.0. Eliminada en PHP 4.3.2.
session.gc_divisor  "100"   PHP_INI_ALL Disponible desde PHP 4.3.2.
session.gc_maxlifetime  "1440"  PHP_INI_ALL  
session.gc_probability  "1" PHP_INI_ALL  
session.hash_bits_per_character "4" PHP_INI_ALL Disponible desde PHP 5.0.0.
session.hash_function   "0" PHP_INI_ALL Disponible desde PHP 5.0.0.
session.name    "PHPSESSID" PHP_INI_ALL  
session.referer_check   ""  PHP_INI_ALL  
session.save_handler    "files" PHP_INI_ALL  
session.save_path   ""  PHP_INI_ALL  
session.serialize_handler   "php"   PHP_INI_ALL  
session.use_cookies "1" PHP_INI_ALL  
session.use_only_cookies    "1" PHP_INI_ALL Disponible desde PHP 4.3.0.
session.use_trans_sid   "0" PHP_INI_ALL PHP_INI_ALL en PHP <= 4.2.3. INI_PERDIR en PHP < 5. Disponible desde PHP 4.0.3.
*/

class Session {
    static private $started;
    static private $file;
    static private $id;
    static private $limiter;

    static public function register() {
        skeetr_override_function(
            'session_start', 
            '',
            'return Skeetr\Overrides\Session::session_start();' 
        );

        skeetr_override_function(
            'session_status', 
            '',
            'return Skeetr\Overrides\Session::session_status();' 
        );

        skeetr_override_function(
            'session_unset', 
            '',
            'return Skeetr\Overrides\Session::session_unset();' 
        );

        skeetr_override_function(
            'session_write_close', 
            '',
            'return Skeetr\Overrides\Session::session_write_close();' 
        );

        skeetr_override_function(
            'session_commit', 
            '',
            'return Skeetr\Overrides\Session::session_write_close();' 
        );
   
        skeetr_override_function(
            'session_destroy', 
            '',
            'return Skeetr\Overrides\Session::session_destroy();' 
        );     

        skeetr_override_function(
            'session_id', 
            '$id = null',
            'return Skeetr\Overrides\Session::session_id($id);' 
        );

        skeetr_override_function(
            'session_encode', 
            '',
            'return Skeetr\Overrides\Session::session_encode();' 
        );

        skeetr_override_function(
            'session_decode', 
            '$data',
            'return Skeetr\Overrides\Session::session_decode($data);' 
        );

        skeetr_override_function(
            'session_get_cookie_params', 
            '',
            'return Skeetr\Overrides\Session::session_get_cookie_params();' 
        );

        skeetr_override_function(
            'session_cache_limiter', 
            '$cache_limiter = null',
            'return Skeetr\Overrides\Session::session_cache_limiter($cache_limiter);' 
        );

        skeetr_override_function(
            'session_cache_expire', 
            '$new_cache_expire = null',
            'return Skeetr\Overrides\Session::session_cache_expire($new_cache_expire);' 
        );

        skeetr_override_function(
            'session_regenerate_id', 
            '$delete_old_session = false',
            'return Skeetr\Overrides\Session::session_regenerate_id($delete_old_session);' 
        );

        self::reset();
    }

    static public function reset() {
        self::$started = null;
        self::$file = null;
        self::$id = null;
        self::session_unset();
    }

    static public function session_start() {
        self::reset();
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
        if ( file_exists(self::$file) ) {
            $data = file_get_contents(self::$file);
            session_decode($data);
        }

        return true;
    }

    static public function session_id($id = null) {
        if ( $id ) self::$id = $id;
        return self::$id; 
    }

    static public function session_write_close() {
        var_dump(self::$started, self::session_encode());
        if ( !self::$started ) return;
        file_put_contents(self::$file, self::session_encode());
    }

    static public function session_unset() {
        if ( !isset($_SESSION) ) return false;

        foreach( $_SESSION as $key => $value ) {
            unset($_SESSION[$key]);
        }
    }

    static public function session_status() {
        if ( self::$started ) return PHP_SESSION_ACTIVE;
        return PHP_SESSION_NONE;
    }

    static public function session_destroy() {
        if ( !self::$started ) return false;
        if ( !file_exists(self::$file) ) return false;
        return unlink(self::$file);
    }

    static public function session_encode() {
        return serialize($_SESSION);
    }

    static public function session_decode($data) {
        if ( !$values = unserialize($data) ) return false;
        foreach( $values as $key => $value ) {
            $_SESSION[$key] = $value;
        }

        return true;
    }

    static public function session_get_cookie_params() {
        return array(
            'lifetime' => (int)ini_get('session.cookie_lifetime'),
            'path' => ini_get('session.cookie_path'),
            'domain' => ini_get('session.cookie_domain'),
            'secure' => (boolean)ini_get('session.cookie_secure'),
            'httponly' => (boolean)ini_get('session.cookie_httponly')
        );
    }

    static public function session_set_cookie_params(
        $lifetime = null, $path = null, $domain = null, 
        $secure = null, $httponly = null
    ) {
        if ( $lifetime !== null ) ini_set('session.cookie_lifetime', (int)$lifetime);
        if ( $path !== null ) ini_set('session.cookie_path', (string)$path);
        if ( $domain !== null ) ini_set('session.cookie_domain', (string)$domain);
        if ( $secure !== null ) ini_set('session.cookie_secure', (boolean)$secure);
        if ( $httponly !== null ) ini_set('session.cookie_httponly', (boolean)$httponly);
    }

    static public function session_cache_limiter($cache_limiter = null) {
        if( !$cache_limiter ) return ini_get('session.cache_limiter');
        ini_set('session.cache_limiter', $cache_limiter);
        return $cache_limiter;
    }

    static public function session_cache_expire($new_cache_expire = null) {
        if( !$new_cache_expire ) return (int)ini_get('session.cache_expire');
        ini_set('session.cache_expire', (int)$new_cache_expire);
        return (int)$new_cache_expire;
    }

    static public function session_regenerate_id($delete_old_session = false) {
        if ( !self::$started ) return false;

        if ( $delete_old_session ) self::session_destroy();
        return self::session_start();
    }

    public function gc($maxlifetime)
    {
        foreach (glob("$this->savePath/sess_*") as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }

        return true;
    }

    static public function configure(Response $response) {
        self::session_write_close();
    }
}