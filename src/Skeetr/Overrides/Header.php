<?php
namespace Skeetr\Overrides;
use Skeetr\HTTP\Response;

class Header {
    static private $headers;
    static private $code;
    static private $callback;

    static public function register() {
        skeetr_override_function(
            'header', 
            '$string, $replace = true, $http_response_code = null',
            'return Skeetr\Overrides\Header::header($string, $replace, $http_response_code);' 
        );

        skeetr_override_function(
            'headers_list', 
            '',
            'return Skeetr\Overrides\Header::headers_list();' 
        );

        skeetr_override_function(
            'header_remove', 
            '$name = false',
            'return Skeetr\Overrides\Header::header_remove($name);' 
        );

        skeetr_override_function(
            'headers_sent', 
            '&$file = null, &$line = null',
            'return Skeetr\Overrides\Header::headers_sent($file, $line);' 
        );

        if ( function_exists('header_register_callback') ) {
            skeetr_override_function(
                'header_register_callback', 
                '$callback',
                'return Skeetr\Overrides\Header::header_register_callback($callback);' 
            );
        }

        self::reset();
    }

    static public function reset() {
        self::$headers = array();
        self::$code = 200;
        self::$callback;
    }

    static public function header($string, $replace = true, $http_response_code = null) {
        if ( !$headers = http_parse_headers($string) ) return false;
        $header = key($headers);

        if ( $replace || !isset(self::$headers[$header]) ) {
            self::$headers[$header] = array();
        }

        self::$headers[$header][] = $string;
        
        if ( strtolower($header) == 'location' ) $http_response_code = 302;
        if ( $http_response_code ) self::$code = $http_response_code;
    }

    static public function header_remove($name = null) {
        if ( $name ) unset(self::$headers[$name]);
        else self::$headers = array();
    }

    static public function headers_list() {
        $results = array();
        foreach(self::$headers as $headers) {
            $results = array_merge($results, $headers);
        }

        return $results;
    }

    static public function header_register_callback($callback) {
        if ( !is_callable($callback) ) return null;
        self::$callback = $callback;
    }

    static public function headers_sent(&$file = null, &$line = null) {
        return null;
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