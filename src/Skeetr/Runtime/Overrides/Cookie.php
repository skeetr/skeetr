<?php
namespace Skeetr\Runtime\Overrides;
use Skeetr\Runtime\OverrideInterface;
use Skeetr\HTTP\Response;

class Cookie implements OverrideInterface {
    static $values;
    static $secure;

    static public function reset() {
        self::$values = array();
        self::$secure = false;
    }

    static public function configure(Response $response) {

    }
    
    final static public function setcookie(
        $name, $value, $expire = 0, $path = null, 
        $domain = null, $secure = false, $httponly = false
    ) {
        $value = rawurlencode($value);
        return Cookie::setrawcookie(
            $name, $value, $expire, $path, $domain, $secure, $httponly
        );
    }

    final static public function setrawcookie(
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