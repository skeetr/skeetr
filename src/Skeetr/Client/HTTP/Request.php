<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client\HTTP;

use http\Message;
use http\Cookie;
use UnexpectedValueException;

class Request extends Message
{
    private $timestamp;
    private $url;
    private $method;
    private $version;
    private $post = array();
    private $get = array();
    private $server = array();
    private $remote = array();
    private $cookies = array();

    public function __construct()
    {
        parent::__construct();
        $this->setType(Message::TYPE_REQUEST);
    }

    /**
     * Configure the request based on a JSON, usually received on the workload
     * from a GearmanJob from the nginx.
     *
     * @param  string  $json
     * @return boolean
     */
    public static function fromJSON($json)
    {
        $request = new static();

        if ( !$data = json_decode($json, true) ) {
            throw new UnexpectedValueException(sprintf(
                'Unexpected message, invalid JSON from nginx: "%s"', $json
            ));
        }

        $request->setTimestamp(microtime(true));

        if ( isset($data['url']) ) $request->setRequestUrl($data['url']);
        if ( isset($data['method']) ) $request->setRequestMethod($data['method']);

        if ( isset($data['headers']) && is_array($data['headers']) ) {
            $request->setHeaders($data['headers']);

            //Malformed cookies will return a fatal error
            if ( $cookies = new Cookie($request->getHeader('Cookie')) ) {
                $request->setCookies($cookies);
            }
        }

        if ( isset($data['post']) && is_array($data['post']) ) {
            $request->setPostFields($data['post']);
        }

        if ( isset($data['get']) && is_array($data['get']) ) {
            $request->setQueryFields($data['get']);
        }

        if ( isset($data['server']) && is_array($data['server']) ) {
            $request->setServerInfo($data['server']);
        }

        if ( isset($data['remote']) && is_array($data['remote']) ) {
            $request->setRemoteInfo($data['remote']);
        }

        return $request;
    }

    /**
     * Sets the remote client info
     *
     * @param float $timestamp microtimestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        $this->setServerGlobal('REQUEST_TIME', (int) $timestamp);
        $this->setServerGlobal('REQUEST_TIME_FLOAT', $timestamp);
    }

    /**
     * Sets the remote client info
     *
     * @param string $info mandatory keys: addr and port
     */
    public function setRemoteInfo($info)
    {
        $this->remote = $info;
        $this->setServerGlobal('REMOTE_ADDR', (string) $info['addr']);
        $this->setServerGlobal('REMOTE_PORT', (string) $info['port']);
    }

    /**
     * Sets the server info
     *
     * @param string $info mandatory keys: name, addr, port and proto
     */
    public function setServerInfo($info)
    {
        $this->server = $info;

        if ( isset($info['name']) && $info['name'] ) $name = (string) $info['name'];
        else $name = (string) $this->getHeader('Host');

        $this->setServerGlobal('SERVER_NAME', $name);

        $this->setServerGlobal('SERVER_ADDR', (string) $info['addr']);
        $this->setServerGlobal('SERVER_PORT', (string) $info['port']);
        $this->setServerGlobal('SERVER_PROTOCOL', (string) $info['proto']);

        //TODO: Version Server
        $this->setServerGlobal('SERVER_SOFTWARE', 'Skeetr/0.0.1');
    }

    /**
     * Sets the headers.
     *
     * @param array $headers associative array containing the new HTTP headers
     */
    public function setHeaders(array $headers)
    {
        parent::addHeaders($headers);

        $this->setServerGlobal('HTTP_HOST', (string) $this->getHeader('Host'));
        $this->setServerGlobal('HTTP_ACCEPT', (string) $this->getHeader('Accept'));
        $this->setServerGlobal('HTTP_CONNECTION', (string) $this->getHeader('Connection'));
        $this->setServerGlobal('HTTP_USER_AGENT', (string) $this->getHeader('User-Agent'));
        $this->setServerGlobal('HTTP_ACCEPT_ENCODING', (string) $this->getHeader('Accept-Encoding'));
        $this->setServerGlobal('HTTP_ACCEPT_LANGUAGE', (string) $this->getHeader('Accept-Language'));
        $this->setServerGlobal('HTTP_ACCEPT_CHARSET', (string) $this->getHeader('Accept-Charset'));
        $this->setServerGlobal('HTTP_CACHE_CONTROL', (string) $this->getHeader('Cache-Control'));
        $this->setServerGlobal('HTTP_COOKIE', (string) $this->getHeader('Cookie'));
    }

    /**
     * Sets the method.
     *
     * @param string $method
     */
    public function setRequestMethod($method)
    {
        parent::setRequestMethod($method);
        $this->setServerGlobal('REQUEST_METHOD', $method);
    }

    /**
     * Sets URL
     *
     * @param string $url
     */
    public function setRequestUrl($url)
    {
        parent::setRequestUrl($url);
        $this->setServerGlobal('REQUEST_URI', $url);
    }

    /**
     * Sets POST fields
     *
     * @param array $fields
     */
    public function setPostFields(array $fields)
    {
        $this->post = $fields;

        $body = new Message\Body();
        $body->append(http_build_query($fields));
        parent::setBody($body);

        $_POST = $fields;
        $_REQUEST = array_merge($_GET, $_POST);
    }

    /**
     * Sets POST files
     *
     * @param array $fields
     */
    public function setPostFiles()
    {
        $_FILES = array();
        throw new \Exception('Not implemented');
    }

    /**
     * Sets GET fields
     *
     * @param array $fields
     */
    public function setQueryFields(array $fields)
    {
        $this->get = $fields;

        $this->setServerGlobal('QUERY_STRING', $this->getQueryData());

        $_GET = $fields;
        $_REQUEST = array_merge($_GET, $_POST);
    }

    /**
     * Sets cookies
     *
     * @param array $cookies
     */
    public function setCookies(Cookie $cookies)
    {
        $data = $cookies->toArray();
        $this->cookies = $data['cookies'];
        $_COOKIE = $this->cookies;
    }

    /**
     * Get timestamp of the request
     *
     * @return float
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get remote user info
     *
     * @return array
     */
    public function getRemoteInfo()
    {
        return $this->remote;
    }

    /**
     * Get server info
     *
     * @return array
     */
    public function getServerInfo()
    {
        return $this->server;
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
     * @param  string $header
     * @return string
     */
    public function getHeader($header, $into_class = null)
    {
        return parent::getHeader($header);
    }

    /**
     * Get method
     *
     * @return string
     */
    public function getRequestMethod()
    {
        return parent::getRequestMethod();
    }

    /**
     * Get URL
     *
     * @return string
     */
    public function getRequestUrl()
    {
        return parent::getRequestUrl();
    }

    /**
     * Get POST fields
     *
     * @return array
     */
    public function getPostFields()
    {
        return $this->post;
    }

    /**
     * Get GET fields
     *
     * @return array
     */
    public function getQueryFields()
    {
        return $this->get;
    }

    /**
     * Get the current query data in form of an urlencoded query string.
     *
     * @return string
     */
    public function getQueryData()
    {
        return http_build_query($this->get);
    }

    /**
     * Get POST files
     *
     * @return array
     */
    public function getPostFiles()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Get cookies
     *
     * @return array
     */
    public function getCookies()
    {
       return $this->cookies;
    }

    private function setServerGlobal($key, $value)
    {
        $_SERVER[$key] = $value;
    }

    /**
     * Returns a array with the values of this Request
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'url' => $this->getRequestUrl(),
            'method' => $this->getRequestMethod(),
            'headers' => $this->getHeaders(),
            'post' => $this->getPostFields(),
            'get' => $this->getQueryFields(),
            'server' => $this->getServerInfo(),
            'remote' => $this->getRemoteInfo()
        );
    }

    /**
     * Returns a string with the values of this Request
     *
     * @param  boolean $default if TRUE setDefaults() is called
     * @return string
     */
    public function toString($deprecated = false)
    {
        return (string) $this;
    }

    /**
     * Returns a JSON with the values of this Request
     *
     * @return string
     */
    public function toJSON()
    {
        return json_encode($this->toArray());
    }
}
