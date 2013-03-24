<?php
namespace Skeetr\Runtime\Overrides;
use Skeetr\Runtime\OverrideInterface;
use Skeetr\HTTP\Response;

class Header implements OverrideInterface {
    static private $headers = array();
    static private $code = 200;
    static private $callback;

    final static public function header($string, $replace = true, $http_response_code = null) {
        if ( !$headers = http_parse_headers($string) ) return false;
        $header = key($headers);

        if ( $replace || !isset(self::$headers[$header]) ) {
            self::$headers[$header] = array();
        }

        self::$headers[$header][] = $string;
        
        if ( strtolower($header) == 'location' ) $http_response_code = 302;
        if ( $http_response_code ) self::$code = $http_response_code;
    }

    final static public function header_remove($name = null) {
        if ( $name ) unset(self::$headers[$name]);
        else self::$headers = array();
    }

    final static public function headers_list() {
        $results = array();
        foreach(self::$headers as $headers) {
            $results = array_merge($results, $headers);
        }

        return $results;
    }

    final static public function header_register_callback($callback) {
        if ( !is_callable($callback) ) return null;
        self::$callback = $callback;
    }

    final static public function headers_sent(&$file = null, &$line = null) {
        return null;
    }


    static public function reset() {
        self::$headers = array();
        self::$code = 200;
        self::$callback;
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