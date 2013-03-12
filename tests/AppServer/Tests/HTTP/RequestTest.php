<?php
namespace AppServer\Tests;
use AppServer\HTTP\Request;

class RequestTest extends TestCase {
    public function getRequest($method) {
        return new Request(
            file_get_contents(__DIR__ . '/../../../Resources/Request/' . $method)
        );
    }

    public function testGetTimestamp() {
        $this->assertSame((int)microtime(true), (int)$this->getRequest('GET')->getTimestamp());
    }

    public function testGetURL() {
        $this->assertSame('/filename.html', $this->getRequest('GET')->getURL());
    }

    public function testGetMethod() {
        $this->assertSame('GET', $this->getRequest('GET')->getMethod());
        $this->assertSame('POST', $this->getRequest('POST')->getMethod());
    }

    public function testGetHeaders() {
        $this->assertTrue(is_array($this->getRequest('GET')->getHeaders()));
    }

    public function testGetHeader() {
        $this->assertSame('keep-alive', $this->getRequest('GET')->getHeader('Connection'));
    }

    public function testGetPostFields() {
        $this->assertSame(array(
            'foo' => 'bar',
            'baz' => 'qux'
        ), $this->getRequest('POST')->getPostFields());
    }

    public function testGetQueryFields() {
        $this->assertSame(array(
            'foo' => 'bar',
            'baz' => 'qux'
        ), $this->getRequest('GET')->getQueryFields());
    }

    public function testGetCookies() {
        $cookies = $this->getRequest('POST')->getCookies();
        $this->assertSame('value test', $cookies['cookie']);
    }

    public function testGetQueryData() {
        $qs = $this->getRequest('GET')->getQueryData();
        $this->assertSame('foo=bar&baz=qux', $qs);
    }   
}