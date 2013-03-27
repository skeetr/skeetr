<?php
namespace Skeetr\Tests;
use Skeetr\HTTP\Request;

class RequestTest extends TestCase {
    public function getRequest($method) {
        $json = file_get_contents(__DIR__ . '/../../../Resources/Request/' . $method);
        $request = new Request();
        $request->fromJSON($json);

        return $request;
    }

    public function testGetTimestamp() {
        $r = $this->getRequest('GET');
        $this->assertSame((int)microtime(true), (int)$r->getTimestamp());
    }

    public function testGetServerInfo() {
        $r = $this->getRequest('GET');

        $expected = array(
            'addr' => '46.105.116.221', 'proto' => 'HTTP/1.1',
            'name' => '', 'port' => '80'
        );

        $this->assertSame($expected, $r->getServerInfo());
        $this->assertSame('46.105.116.221', $_SERVER['SERVER_ADDR']);
        $this->assertSame('80', $_SERVER['SERVER_PORT']);
        $this->assertSame('foo.bar.com', $_SERVER['SERVER_NAME']);
        $this->assertSame('Skeetr/0.0.1', $_SERVER['SERVER_SOFTWARE']);
    }

    public function testGetRemoteInfo() {
        $r = $this->getRequest('GET');

        $expected = array(
            'addr' => '83.59.53.215', 'port' => '52370'
        );

        $this->assertSame($expected, $r->getRemoteInfo());
        $this->assertSame('83.59.53.215', $_SERVER['REMOTE_ADDR']);
        $this->assertSame('52370', $_SERVER['REMOTE_PORT']);
    }

    public function testGetHeaders() {
        $this->assertTrue(is_array($this->getRequest('GET')->getHeaders()));

        $this->assertSame('foo.bar.com', $_SERVER['HTTP_HOST']);
        $this->assertSame('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', $_SERVER['HTTP_ACCEPT']);
        $this->assertSame('keep-alive', $_SERVER['HTTP_CONNECTION']);
        $this->assertSame('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.160 Safari/537.22', $_SERVER['HTTP_USER_AGENT']);
        $this->assertSame('gzip,deflate,sdch', $_SERVER['HTTP_ACCEPT_ENCODING']);
        $this->assertSame('en-US,en;q=0.8', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->assertSame('ISO-8859-1,utf-8;q=0.7,*;q=0.3', $_SERVER['HTTP_ACCEPT_CHARSET']);
        $this->assertSame('max-age=0', $_SERVER['HTTP_CACHE_CONTROL']);
        $this->assertSame('ppkcookie2=another test', $_SERVER['HTTP_COOKIE']);
    }

    public function testGetMethod() {
        $this->assertSame('GET', $this->getRequest('GET')->getMethod());
        $this->assertSame('POST', $this->getRequest('POST')->getMethod());

        $this->assertSame('POST', $_SERVER['REQUEST_METHOD']);
    }

    public function testGetURL() {
        $this->assertSame('/filename.html', $this->getRequest('GET')->getURL());
        $this->assertSame('/filename.html', $_SERVER['REQUEST_URI']);
    }

    public function testGetPostFields() {
        $expected = array(
            'foo' => 'bar',
            'baz' => 'qux'
        );

        $this->assertSame($expected, $this->getRequest('POST')->getPostFields());
        $this->assertSame($expected, $_POST);

    }

    public function testGetQueryFields() {
        $expected = array(
            'foo' => 'bar',
            'baz' => 'qux'
        );

        $this->assertSame($expected, $this->getRequest('GET')->getQueryFields());
        $this->assertSame($expected, $_GET);
    }

    public function testGetHeader() {
        $this->assertSame('keep-alive', $this->getRequest('GET')->getHeader('Connection'));
    }

    public function testGetCookies() {
        $cookies = $this->getRequest('POST')->getCookies();
        $this->assertSame('value test', $cookies['cookie']);
        $this->assertSame(array('cookie' => 'value test'), $_COOKIE);
    }

    public function testGetQueryData() {
        $qs = $this->getRequest('GET')->getQueryData();
        $this->assertSame('foo=bar&baz=qux', $qs);
    }   
}