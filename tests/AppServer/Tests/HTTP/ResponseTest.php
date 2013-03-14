<?php
namespace AppServer\Tests;
use AppServer\HTTP\Response;

class ResponseTest extends TestCase {
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