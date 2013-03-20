<?php
namespace Skeetr\Overrides;

class Header {
    static private $headers = array();
    static private $code = 200;
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

    static public function code() {
        return self::$code;
    }

    static public function headers() {
        if ( self::$callback ) call_user_func(self::$callback);
        return self::headers_list();
    }
}