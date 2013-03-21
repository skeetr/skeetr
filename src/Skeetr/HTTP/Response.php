<?php
namespace Skeetr\HTTP;

class Response {
    private $cookies = array();
    private $code;
    private $body;
    private $headers = array();
 
    public function __construct() {
        $this->setServer('Skeetr 0.0.1');
        $this->setContentType('text/html');
    }

    public function getResponseCode() { return $this->code; }
    public function setResponseCode($code) {
        $this->code = (int)$code;
    }

    public function getBody() { return $this->body; }
    public function setBody($body) {
        $this->body = $body;
    }

    public function setContentType($contentType) {
        $this->setHeader(sprintf('Content-Type: %s', $contentType));
    }

    public function setServer($server) {
        $this->setHeader(sprintf('Server: %s', $server));    
    }

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
         
        $this->cookies[$name] = $cookie;
        return true;
    }

    public function getCookies() { return $this->cookies; }
    public function getCookie($name) { 
        if ( !isset($this->cookies[$name]) ) return false;
        return $this->cookies[$name];
    }

    public function setHeader($string, $replace = true) {
        if ( !$headers = http_parse_headers($string) ) return false;
        $header = key($headers);
        $value = current($headers);

        if ( $replace || !isset($this->headers[$header]) ) {
            $this->headers[$header] = array();
        }

        $this->headers[$header][] = array($header, $value);
        return true;
    }

    public function getHeader($name) { 
        if ( !isset($this->headers[$name]) ) return false;
        return $this->headers[$name]; 
    }

    public function getHeaders() {
        $results = array();
        foreach($this->headers as $headers) {
            $results = array_merge($results, $headers);
        }
        
        $output = array();
        foreach($results as $header) {
            $output[$header[0]] = $header[1];
        }

        return $output;
    }

    public function __toString() {
        if ( $this->body ) {
            $this->setHeader(sprintf('Content-Length: %d', strlen($this->body)));
        }
        
        $cookies = array();
        foreach($this->cookies as $cookie) $cookies[] = http_build_cookie($cookie);
        if ( $cookies ) $this->setHeader(sprintf('Set-Cookie: %s', $cookies));
        
        return json_encode(array(
            'code' => $this->getResponseCode(),  
            'body' => $this->getBody(),
            'headers' => $this->getHeaders()
        ));
    }
}