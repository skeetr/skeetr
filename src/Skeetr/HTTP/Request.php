<?php
namespace Skeetr\HTTP;

class Request {
    private $timestamp;
    private $url;
    private $method;
    private $headers = array();
    private $post = array();
    private $get = array();
    private $server = array();
    private $remote = array();
    private $cookies = array();

    public function fromJSON($json) {
        if ( !$data = json_decode($json, true) ) {
            throw new \UnexpectedValueException(sprintf(
                'Unexpected message invalid JSON from nginx: "%s"', $message
            ));
        }

        $this->setTimestamp(microtime(true));
        
        if ( isset($data['uri']) ) $this->setUrl($data['uri']);
        if ( isset($data['method']) ) $this->setMethod($data['method']);

        if ( isset($data['headers']) && is_array($data['headers']) ) {
            $this->setHeaders($data['headers']);

            $cookies = http_parse_cookie($this->getHeader('Cookie'));
            $this->setCookies($cookies->cookies);
        }

        if ( isset($data['post']) && is_array($data['post']) ) {
            $this->setPostFields($data['post']);
        }

        if ( isset($data['get']) && is_array($data['get']) ) {
            $this->setQueryFields($data['get']);
        }

        if ( isset($data['server']) && is_array($data['server']) ) {
            $this->setServerInfo($data['server']);
        }

        if ( isset($data['remote']) && is_array($data['remote']) ) {
            $this->setRemoteInfo($data['remote']);
        }

        return true; 
    }
    
    /*
    public function setBody( string $body )
    public function setHttpVersion ( string $version )
    */

    /**
     * Sets the remote client info
     *
     * @param float $timestamp microtimestamp
     */
    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;
        $_SERVER['REQUEST_TIME'] = (int)$timestamp;
        $_SERVER['REQUEST_TIME_FLOAT'] = $timestamp;
    }

    /**
     * Sets the remote client info
     *
     * @param string $info mandatory keys: addr and port
     */
    public function setRemoteInfo($info) {
        $this->remote = $info;
        $_SERVER['REMOTE_ADDR'] = (string)$info['addr'];
        $_SERVER['REMOTE_PORT'] = (string)$info['port'];
    }

    /**
     * Sets the server info
     *
     * @param string $info mandatory keys: name, addr, port and proto
     */
    public function setServerInfo($info) {
        $this->server = $info;

        $_SERVER['SERVER_NAME'] = (string)$info['name'];
        if ( !$_SERVER['SERVER_NAME'] ) $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];


        $_SERVER['SERVER_ADDR'] = (string)$info['addr'];
        $_SERVER['SERVER_PORT'] = (string)$info['port'];
        $_SERVER['SERVER_PROTOCOL'] = (string)$info['proto'];

        //TODO: Version Server
        $_SERVER['SERVER_SOFTWARE'] = 'Skeetr/0.0.1';
    }

    /**
     * Sets the headers.
     *
     * @param array $headers associative array containing the new HTTP headers
     */
    public function setHeaders(array $headers) {
        $this->headers = $headers;

        $_SERVER['HTTP_HOST'] = (string)$this->getHeader('Host');
        $_SERVER['HTTP_ACCEPT'] = (string)$this->getHeader('Accept');
        $_SERVER['HTTP_CONNECTION'] = (string)$this->getHeader('Connection'); 
        $_SERVER['HTTP_USER_AGENT'] = (string)$this->getHeader('User-Agent'); 
        $_SERVER['HTTP_ACCEPT_ENCODING'] = (string)$this->getHeader('Accept-Encoding'); 
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = (string)$this->getHeader('Accept-Language'); 
        $_SERVER['HTTP_ACCEPT_CHARSET'] = (string)$this->getHeader('Accept-Charset'); 
        $_SERVER['HTTP_CACHE_CONTROL'] = (string)$this->getHeader('Cache-Control'); 
        $_SERVER['HTTP_COOKIE'] = (string)$this->getHeader('Cookie'); 
    }

    /**
     * Sets the method.
     *
     * @param string $method
     */
    public function setMethod($method) {
        $this->method = $method;
        $this->applyToServer('REQUEST_METHOD', $method);
    }

    /**
     * Sets URL
     *
     * @param string $url 
     */
    public function setUrl($url) {
        $this->url = $url;
        $this->applyToServer('REQUEST_URI', $url);
    }

    /**
     * Sets POST fields
     *
     * @param array $fields 
     */
    public function setPostFields(array $fields) {
        $this->post = $fields;
        $_POST = $fields;
        $_REQUEST = array_merge($_GET, $_POST);
    }

    /**
     * Sets POST files
     *
     * @param array $fields 
     */
    public function setPostFiles() { 
        $_FILES = array();
        throw new \Exception('Not implemented'); 
    }

    /**
     * Sets GET fields
     *
     * @param array $fields 
     */
    public function setQueryFields(array $fields) {
        $this->get = $fields;

        $_SERVER['QUERY_STRING'] = $this->getQueryData();

        $_GET = $fields;
        $_REQUEST = array_merge($_GET, $_POST);
    }

    /**
     * Sets cookies
     *
     * @param array $cookies 
     */
    public function setCookies(array $cookies) {
        $this->cookies = $cookies;
        $_COOKIE = $cookies;
    }

    public function getTimestamp() { return $this->timestamp; }

    public function getRemoteInfo() { return $this->remote; }

    public function getServerInfo() { return $this->server; }


    /**
     * Get headers
     *
     * @return array 
     */
    public function getHeaders() { return $this->headers; }

    /**
     * Get a header by name
     *
     * @param string $header
     * @return string  
     */
    public function getHeader($header) { 
        $header = strtolower($header);
        if ( !isset($this->headers[$header]) ) return null;
        return $this->headers[$header];
    }

    /**
     * Get method
     *
     * @return string 
     */
    public function getMethod() { return $this->method; }

    /**
     * Get URL
     *
     * @return string 
     */
    public function getUrl() { return $this->url; }

    /**
     * Get POST fields
     *
     * @return array 
     */
    public function getPostFields() { return $this->post; }

    /**
     * Get GET fields
     *
     * @return array 
     */
    public function getQueryFields() { return $this->get; }

    /**
     * Get the current query data in form of an urlencoded query string.
     *
     * @return string
     */
    public function getQueryData() { return http_build_query($this->get); }

    /**
     * Get POST files
     *
     * @return array 
     */
    public function getPostFiles() { throw new \Exception('Not implemented'); }

    /**
     * Get cookies
     *
     * @return array 
     */
    public function getCookies() {
       return $this->cookies;
    }
    
    private function applyToServer($key, $value) {
        $_SERVER[$key] = $value;
    }
}
