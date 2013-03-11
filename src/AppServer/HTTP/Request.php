<?php
namespace AppServer\HTTP;
//http://us.php.net/manual/en/http.constants.php
class Request {
    private $message;
    private $raw;
    private $timestamp;
    private $cookies;
    private $post;
    private $get;

    public function __construct($message) {
        $this->set($message);
        $this->overrideGlobals();
    }

    private function set($message) {
        $this->timestamp = microtime(true);
        $this->raw = $message;
        $this->message = new \HttpMessage($this->raw);

        if ( !$this->message->setRequestUrl(
            $this->message->getHeader('X-AppServer-Uri')
        ) ) {
            throw new \InvalidArgumentException('Invalid X-AppServer-Uri header');   
        }

        if ( !$this->message->setRequestMethod(
            $this->message->getHeader('X-AppServer-Method')
        ) ) {
            throw new \InvalidArgumentException('Invalid X-AppServer-Method header');   
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

        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/' . $this->getHttpVersion();

        $_SERVER['SERVER_NAME'] = (string)$this->getHeader('X-AppServer-Server-Name');
        if ( !$_SERVER['SERVER_NAME'] ) {
            $_SERVER['SERVER_NAME'] = (string)$this->getHeader('X-AppServer-Hostname');
        }

        $_SERVER['SERVER_ADDR'] = (string)$this->getHeader('X-AppServer-Server-Addr');
        $_SERVER['SERVER_PORT'] = (string)$this->getHeader('X-AppServer-Server-Port');

        $_SERVER['REMOTE_ADDR'] = (string)$this->getHeader('X-AppServer-Remote-Addr');
        $_SERVER['REMOTE_PORT'] = (string)$this->getHeader('X-AppServer-Remote-Port');

        //TODO: Version Server
        $_SERVER['SERVER_SOFTWARE'] = 'AppServer/0.1 (Alpha)';

        //TODO: REDIRECT_URL
        //TODO: GATEWAY_INTERFACE
        //TODO: REDIRECT_STATUS
    }

    public function getTimestamp() { return $this->timestamp; }
    public function getUrl() { return $this->message->getRequestUrl(); }
    public function getMethod() { return $this->message->getRequestMethod(); }
    public function getHttpVersion() { return 'HTTP/' . $this->message->getHttpVersion(); }
   
    public function getHeaders() { return $this->message->getHeaders(); }
    public function getHeader($header) { return $this->message->getHeader($header); }
    public function getQueryData() { return parse_url($this->getUrl(), PHP_URL_QUERY); }
    public function getRawPostData() { return $this->message->getBody(); }
    public function getRawRequestMessage() { return $this->raw; }

    //TODO: Comprobar resto de parametros del objecto, como fecha de expiración 
    public function getCookies() {
        if ( !$this->cookies ) {
            $this->cookies = http_parse_cookie($this->getHeader('Cookie'));
        }
       
       return $this->cookies->cookies;
    }

    public function getPostFields() {
        if ( !$this->post ) {
            parse_str($this->getRawPostData(), $this->post);
        }
       
       return $this->post;
    }

    public function getQueryFields() {
        if ( !$this->get ) {
            parse_str($this->getQueryData(), $this->get);
        }
       
       return $this->get;
    }
}

/*
public string getContentType ( vid )
public array getOptions ( void )
public array getPostFiles ( void )
public string getPutData ( void )
public string getPutFile ( void )
public array getSslOptions ( void )
*/