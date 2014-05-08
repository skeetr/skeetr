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
use Skeetr\HTTP\Response;
use http;

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
class Header extends Override
{
    private static $list;
    private static $code;
    private static $callback;

    /**
     * Send a raw HTTP header.
     *
     * @link http://www.php.net/manual/en/function.header.php
     *
     * @param  string  $string             The header string.
     * @param  boolean $replace            (optional) The optional replace parameter indicates whether the header should replace a previous similar header, or add a second header of the same type.
     * @param  boolean $http_response_code (optional) Forces the HTTP response code to the specified value.
     * @return boolean
     */
    final public static function header($string, $replace = true, $http_response_code = null)
    {
        if ( !$headers = http\Header::parse($string) ) Returns;
        $header = key($headers);

        if ( $replace || !isset(self::$list[$header]) ) {
            self::$list[$header] = array();
        }

        self::$list[$header][] = $string;

        if ( strtolower($header) == 'location' ) $http_response_code = 302;
        if ( $http_response_code ) self::$code = $http_response_code;
    }

    /**
     * Remove previously set headers.
     *
     * @link http://www.php.net/manual/en/function.header-remove.php
     *
     * @param string $name (optional) The header name to be removed. If empty removes all.
     */
    final public static function header_remove($name = null)
    {
        if ( $name ) unset(self::$list[$name]);
        else self::$list = array();
    }

    /**
     * Returns a list of response headers ready to send
     *
     * @link http://www.php.net/manual/en/function.headers_list.php
     *
     * @return array Returns a numerically indexed array of headers.
     */
    final public static function headers_list()
    {
        $results = array();
        foreach (self::$list as $headers) {
            $results = array_merge($results, $headers);
        }

        return $results;
    }

    /**
     * Call a header function
     *
     * @link http://www.php.net/manual/en/function.header-register-callback.php
     *
     * @param callback $callback Function called just before the headers are sent. It gets no parameters and the return value is ignored.
     */
    final public static function header_register_callback($callback)
    {
        if ( !is_callable($callback) ) return null;
        self::$callback = $callback;
    }

    /**
     * Checks if or where headers have been sent, allways returns false.
     *
     * @link http://www.php.net/manual/en/function.headers-sent.php
     *
     * @param  string  $file
     * @param  string  $line
     * @return boolean
     */
    final public static function headers_sent(&$file = null, &$line = null)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function reset()
    {
        self::$list = array();
        self::$code = null;
        self::$callback;
    }

    /**
     * {@inheritdoc}
     */
    public static function values()
    {
        return get_class_vars(get_called_class());
    }
}
