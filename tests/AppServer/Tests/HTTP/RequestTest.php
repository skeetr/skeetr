<?php
namespace AppServer\Tests;
use AppServer\HTTP\Request;

class RequestTest extends TestCase {
    public function getRequest($method) {
        return new Request(
            file_get_contents(__DIR__ . '/../../../Resources/Request/' . $method)
        );
    }

    public function testGetCookies() {
        $cookies = $this->getRequest('GET')->getCookies();

        $this->assertSame('1111', $cookies['testA']);
        $this->assertSame('2222', $cookies['testB']);
    }

    public function testGetQueryData() {
        $qs = $this->getRequest('GET')->getQueryData();

        $this->assertSame('arg_a=1&arg_b=2', $qs);
    }
    
    public function testGetRawPostData() {
        $post = $this->getRequest('POST')->getRawPostData();

        $this->assertSame('firstname=John&lastname=Doe', $post);
    }
    
    public function testGetPostFields() {
        $post = $this->getRequest('POST')->getPostFields();

        $this->assertSame('John', $post['firstname']);
        $this->assertSame('Doe', $post['lastname']);    
    }

    public function testGetQueryFields() {
        $get = $this->getRequest('GET')->getQueryFields();

        $this->assertSame('1', $get['arg_a']);
        $this->assertSame('2', $get['arg_b']);    
    }

}