<?php
namespace AppServer\HTTP;
//http://projects.ceondo.com/p/photon/source/tree/develop/src/photon/http.php

class Response {
    private $message;
    private $cookies = [];
    private $code = 200;
    private $version = HTTP_VERSION_1_0;
    private $body;
    private $contentType = 'text/html';
    private $server = 'AppServer 1.0 (Alpha)';

    public function __construct() {
        $this->message = new \HttpMessage();
        $this->message->setType(HTTP_MSG_RESPONSE);
    }

    public function setResponseCode($code) {
        $this->code = (int)$code;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function setContentType($contentType) {
        $this->contentType = $contentType;
    }

    public function setServer($server) {
        $this->server = $server;
    }

    public function setCookie(
        $name, 
        $value, 
        $expire = 0, 
        $path = null,
        $domain = null, 
        $secure = false, 
        $httpOnly = false
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

    public function addHeader($name, $string) {
        return $this->message->addHeaders(array($name => $string), true);
    }

    public function __toString() {
        if ( $this->version == HTTP_VERSION_1_1 ) $version = '1.1';
        else $version = '1.0';
        $this->message->setHttpVersion($version);
        $this->message->setResponseCode($this->code);

        $this->addHeader('Content-Type', $this->contentType);
        $this->addHeader('Server', $this->server);

        if ( $this->body ) {
            $this->addHeader('Content-Length', strlen($this->body));
            $this->message->setBody($this->body);
        }
        
        foreach($this->cookies as $name => $cookie) {
            $this->addHeader('Set-Cookie', http_build_cookie($cookie));
        }
        
        return $this->message->toString();
    }
}
 

/*setcookie ( string $name [, string $value [, int $expire = 0 [, string $path [, string $domain [, bool $secure = false [, bool $httponly = false ]]]]]] )


/*

pRequest {
public bool addCookies ( array $cookies )
public bool addHeaders ( array $headers )
public bool addPostFields ( array $post_data )
public bool addPostFile ( string $name , string $file [, string $content_type = "application/x-octetstream" ] )
public bool addPutData ( string $put_data )
public bool addQueryData ( array $query_params )
public bool addRawPostData ( string $raw_post_data )
public bool addSslOptions ( array $options )
public void clearHistory ( void )
public __construct ([ string $url [, int $request_method = HTTP_METH_GET [, array $options ]]] )
public bool enableCookies ( void )
public string getContentType ( void )
public array getCookies ( void )
public array getHeaders ( void )
public HttpMessage getHistory ( void )
public int getMethod ( void )
public array getOptions ( void )
public array getPostFields ( void )
public array getPostFiles ( void )
public string getPutData ( void )
public string getPutFile ( void )
public string getQueryData ( void )
public string getRawPostData ( void )
public string getRawRequestMessage ( void )
public string getRawResponseMessage ( void )
public HttpMessage getRequestMessage ( void )
public string getResponseBody ( void )
public int getResponseCode ( void )
public array getResponseCookies ([ int $flags = 0 [, array $allowed_extras ]] )
public array getResponseData ( void )
public mixed getResponseHeader ([ string $name ] )
public mixed getResponseInfo ([ string $name ] )
public HttpMessage getResponseMessage ( void )
public string getResponseStatus ( void )
public array getSslOptions ( void )
public string getUrl ( void )
public bool resetCookies ([ bool $session_only = false ] )
public HttpMessage send ( void )
bool setBody ([ string $request_body_data ] )
public bool setContentType ( string $content_type )
public bool setCookies ([ array $cookies ] )
public bool setHeaders ([ array $headers ] )
public bool setMethod ( int $request_method )
public bool setOptions ([ array $options ] )
public bool setPostFields ( array $post_data )
public bool setPostFiles ( array $post_files )
public bool setPutData ([ string $put_data ] )
public bool setPutFile ([ string $file = "" ] )
public bool setQueryData ( mixed $query_data )
public bool setRawPostData ([ string $raw_post_data ] )
public bool setSslOptions ([ array $options ] )
public bool setUrl ( string $url )
}
*/