<?php
namespace Skeetr\Tests;
use Skeetr\HTTP\Response;

class ResponseTest extends TestCase {
    public function testSetHeader() {
        $response = new Response();
        $response->setHeader('Location: http://www.bar.com/');


        $headers = $response->getHeaders();
        $header = end($headers);

        $this->assertSame('Location', $header[0]);
        $this->assertSame('http://www.bar.com/', $header[1]);
    } 

    public function testSetHeaderReplace() {
        $response = new Response();
        $response->setHeader('Location: http://www.bar.com/');
        $response->setHeader('Location: http://www.foo.com/', true);


        $headers = $response->getHeaders();
        $header = end($headers);

        $this->assertSame(3, count($headers));

        $this->assertSame('Location', $header[0]);
        $this->assertSame('http://www.foo.com/', $header[1]);
    } 

    public function testSetCookie() {
        $response = new Response();
        $response->setCookie('test', 'value', time() + 10);
        $response->setCookie('test2', 'value2', time() + 10);

        $this->assertSame(2, count($response->getCookies()));

        $cookie = $response->getCookie('test');
        $this->assertSame('value', $cookie['cookies']['test']);
    }

    public function testSetBody() {
        $response = new Response();
        $response->setBody('Test AAA');
    }

}