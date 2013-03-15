<?php
namespace Skeetr\HTTP;
//http://us.php.net/manual/en/http.constants.php
class Request {
    private $message;
    private $raw;
    private $timestamp;
    private $cookies;
    private $data;

    public function __construct($json) {
        $this->set($json);
        $this->overrideGlobals();
    }

    private function set($message) {
        $this->timestamp = microtime(true);
        $this->raw = $message;
        if ( !$this->data = json_decode($message, true) ) {
            throw new \UnexpectedValueException(sprintf(
                'Unexpected message invalid JSON from nginx: "%s"', $message
            ));
        }
        
        if ( !isset($this->data['uri']) ) {
            throw new \InvalidArgumentException('Invalid request, missing uri');  
        }

        if ( !isset($this->data['method']) ) {
            throw new \InvalidArgumentException('Invalid request, missing method');  
        }

        if ( !isset($this->data['headers']) || !is_array($this->data['headers']) ) {
            throw new \InvalidArgumentException('Invalid request, missing headers');  
        }

        if ( !isset($this->data['post']) || !is_array($this->data['post']) ) {
            throw new \InvalidArgumentException('Invalid request, missing post data');  
        }

        if ( !isset($this->data['get']) || !is_array($this->data['get']) ) {
            throw new \InvalidArgumentException('Invalid request, missing get data');  
        }

        if ( !isset($this->data['get']) || !is_array($this->data['get']) ) {
            throw new \InvalidArgumentException('Invalid request, missing get data');  
        }

        if ( !isset($this->data['server']) || !is_array($this->data['server']) ) {
            throw new \InvalidArgumentException('Invalid request, missing server data');  
        }
        
        if ( !isset($this->data['server']) || !is_array($this->data['server']) ) {
            throw new \InvalidArgumentException('Invalid request, missing server data');  
        }

        return true; 
    }

    public function overrideGlobals() {
        $_SERVER['HTTP_HOST'] = (string)$this->getHeader('Host');
        $_SERVER['HTTP_ACCEPT'] = (string)$this->getHeader('Accept');
        $_SERVER['HTTP_CONNECTION'] = (string)$this->getHeader('Connection'); 
        $_SERVER['HTTP_USER_AGENT'] = (string)$this->getHeader('User-Agent'); 
        $_SERVER['HTTP_ACCEPT_ENCODING'] = (string)$this->getHeader('Accept-Encoding'); 
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = (string)$this->getHeader('Accept-Language'); 
        $_SERVER['HTTP_ACCEPT_CHARSET'] = (string)$this->getHeader('Accept-Charset'); 
        $_SERVER['HTTP_CACHE_CONTROL'] = (string)$this->getHeader('Cache-Control'); 
        $_SERVER['HTTP_COOKIE'] = (string)$this->getHeader('Cookie'); 

        $_SERVER['REQUEST_METHOD'] = (string)$this->getMethod();
        $_SERVER['REQUEST_URI'] = $this->getUrl();
        $_SERVER['QUERY_STRING'] = $this->getQueryData();

        $_SERVER['REQUEST_TIME'] = (int)$this->getTimestamp();
        $_SERVER['REQUEST_TIME_FLOAT'] = $this->getTimestamp();

        $_SERVER['SERVER_NAME'] = (string)$this->data['server']['name'];
        if ( !$_SERVER['SERVER_NAME'] ) $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];


        $_SERVER['SERVER_ADDR'] = (string)$this->data['server']['addr'];
        $_SERVER['SERVER_PORT'] = (string)$this->data['server']['port'];
        $_SERVER['SERVER_PROTOCOL'] = (string)$this->data['server']['proto'];

        $_SERVER['REMOTE_ADDR'] = (string)$this->data['remote']['addr'];
        $_SERVER['REMOTE_PORT'] = (string)$this->data['remote']['port'];

        //TODO: Version Server
        $_SERVER['SERVER_SOFTWARE'] = 'Skeetr/0.0.1';

        //TODO: REDIRECT_URL
        //TODO: GATEWAY_INTERFACE
        //TODO: REDIRECT_STATUS
    }

    public function getTimestamp() { return $this->timestamp; }
    public function getUrl() { return $this->data['uri']; }
    public function getMethod() { return $this->data['method']; }
    public function getHeaders() { return $this->data['headers']; }

    public function getHeader($header) { 
        $header = strtolower($header);
        if ( !isset($this->data['headers'][$header]) ) return null;
        return $this->data['headers'][$header];
    }
    
    public function getPostFields() { return $this->data['post']; }
    public function getQueryFields() { return $this->data['get']; }
    public function getPostFiles() { throw new Exception('Not implemented'); }

    public function getQueryData() { return http_build_query($this->data['get']); }
    public function getCookies() {
        if ( !$this->cookies ) {
            $this->cookies = http_parse_cookie($this->getHeader('Cookie'));
        }
       
       return $this->cookies->cookies;
    }
}
