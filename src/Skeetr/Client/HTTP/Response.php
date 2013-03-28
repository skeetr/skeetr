<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client\HTTP;
use HttpMessage;
use Skeetr\Runtime\Manager;

class Response extends HttpMessage
{
    public function __construct() {
        parent::__construct();
        $this->setType(HttpMessage::TYPE_RESPONSE);
    }

    /**
     * Sets the default server and content type headers and response code if
     * not is setted allready.
     */
    public function setDefaults() {
        if ( !$this->getResponseCode() ) $this->setResponseCode(200);
        if ( !$this->getContentType() ) $this->setContentType('text/html');
        if ( !$this->getServer() ) $this->setServer('Skeetr 0.0.1');
    }

    /**
     * Return a new instance of Response with the headers and response code retuned by
     * Skeetr\Runtime\Manager::value() method
     *
     * @return Response
     */
    public static function fromRuntime()
    {
        $response = new static();

        $values = Manager::values();

        $response->setResponseCode($values['header']['code']);
        $response->setHeaders($values['header']['list'], true);
        return $response;
    }

    /**
     * Sets the headers.
     *
     * @param array $headers associative array containing the new HTTP headers
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function setHeaders($headers)
    {
        return $this->addHeaders($headers, false);
    }

    /**
     * Add headers. If append is true, headers with the same name will be separated, 
     * else overwritten.
     *
     * @param array $headers associative array containing the new HTTP headers
     * @param array $append if true, and a header with the same name of one to add exists
     *                      already, this respective header will be converted to an array 
     *                      containing both header values, otherwise it will be  
     *                      overwritten with the new header value
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function addHeaders($headers, $append = false)
    {
        $data = array();
        foreach($headers as $header => $value) {
            if ( is_array($value) ) {
                foreach($value as $val) $data[$header] = $val;
            } else {
                $data[$header] = (string)$value;
            }
        }

        return parent::addHeaders($data, $append);
    }

    /**
     * Add headers. If append is true, headers with the same name will be separated, 
     * else overwritten.
     *
     * @param string $header just a string with the header
     * @param array $append if true, and a header of the same type allready exists will
     *                      append, if false will be replaced.
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function addHeader($header, $append = false)
    {
        if ( !$headers = http_parse_headers($header) ) return false;
        return $this->addHeaders($headers, $append);
    }

    /**
     * Set the response code of an HTTP Response Message.
     *
     * @param integer $code HTTP response code
     * @return mixed Returns TRUE on success or the response code is out of range (100-510).
     */
    public function setResponseCode($code)
    {
        return parent::setResponseCode((int)$code);
    }

    /**
     * Set message body
     *
     * @param string $body the new body of the message
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function setBody($body)
    {
        parent::setBody($body);
        return $this->addHeaders(array(
            'Content-Length' => (string)strlen($this->body)
        ), true);
    }

    /**
     * Set content type
     *
     * @param string $contentType the content type of the sent entity
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function setContentType($contentType) {
        return parent::addHeaders(array(
            'Content-Type' => $contentType
        ), false);
    }

    /**
     * Set server name and version
     *
     * @param string $server
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function setServer($server) {
        return $this->addHeaders(array(
            'Server' => $server
        ), false);
    }

    /**
     * Defines a cookie to be sent along with the rest of the HTTP headers.
     *
     * @param string $name The name of the cookie.
     * @param string $value The value of the cookie.
     * @param integer $expire (optional) The time the cookie expires. This is a Unix timestamp
     * @param string $path (optional) The path on the server in which the cookie will be available on. 
     * @param string $domain (optional) The domain that the cookie is available to. 
     * @param boolean $secure (optional) Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client. 
     * @param boolean $httpOnly (optional) When TRUE the cookie will be made accessible only through the HTTP protocol.
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function setCookie(
        $name, $value, $expire = 0,  $path = null,
        $domain = null, $secure = false, $httpOnly = false
    ) {
        $cookie = array(
            'cookies' => array($name => $value),
            'flags' => 0,
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain
        );

        if ($secure) $cookie['flags'] = HTTP_COOKIE_SECURE;
        if ($httpOnly) $cookie['flags'] = $cookie['flags'] | HTTP_COOKIE_HTTPONLY;

        return $this->addHeaders(array(
            'Set-Cookie' => http_build_cookie($cookie)
        ), true);
    }

    /**
     * Get headers
     *
     * @return array 
     */
    public function getHeaders()
    { 
        return parent::getHeaders(); 
    }

    /**
     * Get a header by name
     *
     * @param string $header
     * @return string  
     */
    public function getHeader($header)
    { 
        return parent::getHeader($header);
    }

    /**
     * Get the Response Code of the Message.
     *
     * @return string  
     */
    public function getResponseCode()
    {
        return parent::getResponseCode();
    }

    /**
     * Get message body
     *
     * @return string  
     */
    public function getBody()
    {
        return parent::getBody();
    }

    /**
     * Get content type
     *
     * @return string  
     */
    public function getContentType()
    {
        return parent::getHeader('Content-Type');
    }

    /**
     * Get content length
     *
     * @return integer  
     */
    public function getContentLength()
    {
        return (int)parent::getHeader('Content-Length');
    }

    /**
     * Get server name
     *
     * @return string  
     */
    public function getServer()
    {
        return parent::getHeader('Server');
    }

    /**
     * Get cookies
     *
     * @return array  
     */
    public function getCookies()
    { 
        $cookies = parent::getHeader('Set-Cookie');
        
        if ( !$cookies ) return false;
        else if ( $cookies && !is_array($cookies) ) $cookies = array($cookies);
        
        foreach($cookies as &$cookie) $cookie = http_parse_cookie($cookie);
        return $cookies; 
    }

    /**
     * Returns a array with the values of this Response
     *
     * @param boolean $default if TRUE setDefaults() is called
     * @return array
     */
    public function toArray($default = true)
    {
        if ( $default ) $this->setDefaults();
        return array(
            'responseCode' => $this->getResponseCode(),  
            'body' => $this->getBody(),
            'headers' => $this->getHeaders()
        ); 
    }

    /**
     * Returns a string with the values of this Response
     *
     * @param boolean $default if TRUE setDefaults() is called
     * @return string
     */
    public function toString($default = true)
    {
        if ( $default ) $this->setDefaults();
        return (string)$this;
    }

    /**
     * Returns a JSON with the values of this Response
     *
     * @param boolean $default if TRUE setDefaults() is called
     * @return string
     */
    public function toJSON($default = true)
    {
        return json_encode($this->toArray($default));
    }
}