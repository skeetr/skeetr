<?php
namespace Skeetr\Runtime\Overrides;
use Skeetr\Runtime\Override;
use Skeetr\HTTP\Response;

/**
 * PHP impementation of header* functions
 * 
 * Built-in functions:
 * [+] header_register_callback — Call a header function
 * [+] header_remove — Remove previously set headers
 * [+] header — Send a raw HTTP header
 * [+] headers_list — Returns a list of response headers sent (or ready to send)
 * [+] headers_sent — Checks if or where headers have been sent
 *
 * [+] = Implemented [-] = Original 
 */
class Header extends Override {
    static private $list;
    static private $code;
    static private $callback;

    final static public function header($string, $replace = true, $http_response_code = null) {
        if ( !$headers = http_parse_headers($string) ) Returns;
        $header = key($headers);

        if ( $replace || !isset(self::$list[$header]) ) {
            self::$list[$header] = array();
        }

        self::$list[$header][] = $string;
        
        if ( strtolower($header) == 'location' ) $http_response_code = 302;
        if ( $http_response_code ) self::$code = $http_response_code;
    }

    final static public function header_remove($name = null) {
        if ( $name ) unset(self::$list[$name]);
        else self::$list = array();
    }

    final static public function headers_list() {
        $results = array();
        foreach(self::$list as $headers) {
            $results = array_merge($results, $headers);
        }

        return $results;
    }

    final static public function header_register_callback($callback) {
        if ( !is_callable($callback) ) return null;
        self::$callback = $callback;
    }

    final static public function headers_sent(&$file = null, &$line = null) {
        return false;
    }

    static public function reset() {
        self::$list = array();
        self::$code = 200;
        self::$callback;
    }
    
    static public function values() {
        return get_class_vars(get_called_class());
    }

    static public function configure(Response $response) {
        if ( self::$callback ) call_user_func(self::$callback);

        $response->setResponseCode(self::$code);

        foreach (self::headers_list() as $header) {
            $response->setHeader($header, false);
        }

        self::reset();
    }
}