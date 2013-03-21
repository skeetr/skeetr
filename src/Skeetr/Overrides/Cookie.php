<?php
namespace Skeetr\Overrides;
use Skeetr\HTTP\Response;

class Cookie {
    static $values;
    static $secure;

    static public function register() {
        skeetr_override_function(
            'setcookie', 
            '$name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false',
            'return Skeetr\Overrides\Cookie::setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);' 
        );

        skeetr_override_function(
            'setrawcookie', 
            '$name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false',
            'return Skeetr\Overrides\Cookie::setrawcookie($name, $value, $expire, $path, $domain, $secure, $httponly);' 
        );

        self::reset();
    }

    static public function reset() {
        self::$values = array();
        self::$secure = false;
    }

    static public function setcookie(
        $name, $value, $expire = 0, $path = null, 
        $domain = null, $secure = false, $httponly = false
    ) {
        $value = rawurlencode($value);
        return Cookie::setrawcookie(
            $name, $value, $expire, $path, $domain, $secure, $httponly
        );
    }

    static public function setrawcookie(
        $name, $value, $expire = 0, $path = null, 
        $domain = null, $secure = false, $httponly = false
    ) {
        self::$values[$name] = $value;
        self::$secure = $secure;

        $cookie = http_build_cookie(array(
            'cookies' => self::$values,
            'expires' => $expire,
            'path' => $path, 
            'domain' => $domain
        ));

        Header::header(sprintf('Set-Cookie: %s', $cookie));
    }
}