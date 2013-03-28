<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Tests\Client\HTTP;
use Skeetr\Tests\TestCase;
use Skeetr\Client\HTTP\Response;

class ResponseTest extends TestCase
{
    public function testFromRuntime()
    {
        header('Location: http://foo.bar');
        setcookie('foo', 'bar');

        $response = Response::fromRuntime();

        $this->assertSame('Location: http://foo.bar', $response->getHeader('Location'));
        $this->assertSame(302, $response->getResponseCode());
    }

    public function testSetHeadersAndGetHeaders()
    {
        $headers = array(
            'Foo' => 'bar',
            'Baz' => 'qux'
        );

        $response = new Response();
        $response->setHeaders($headers);

        $this->assertSame($headers, $response->getHeaders());
    } 

    public function testAddHeaders()
    {
        $headers = array(
            'Foo' => 'bar',
            'Baz' => 'qux'
        );

        $response = new Response();
        $response->addHeaders($headers);

        $this->assertSame($headers, $response->getHeaders());
    } 

    public function testAddHeadersAppend()
    {
        $headers = array(
            'Foo' => 'bar',
            'Baz' => 'qux'
        );

        $response = new Response();
        $response->addHeaders($headers);
        $response->addHeaders($headers, true);

        $expected = array(
            'Foo' => array('bar', 'bar'),
            'Baz' => array('qux', 'qux')
        );

        $this->assertSame($expected, $response->getHeaders());
    } 


    public function testAddHeader()
    {
        $headers = array(
            'Foo' => 'bar',
            'Baz' => 'qux'
        );

        $response = new Response();
        $response->addHeaders($headers);
        $response->addHeader('Foo: baz');

        $expected = array(
            'Foo' => 'baz',
            'Baz' => 'qux'
        );

        $this->assertSame($expected, $response->getHeaders());
    }

    public function testAddHeaderAppend() {
        $headers = array(
            'Foo' => 'bar',
            'Baz' => 'qux'
        );

        $response = new Response();
        $response->addHeaders($headers);
        $response->addHeader('Foo: baz', true);

        $expected = array(
            'Foo' => array('bar', 'baz'),
            'Baz' => 'qux'
        );

        $this->assertSame($expected, $response->getHeaders());
    } 

    public function testSetResponseCodeAndGetResponseCode()
    {
        $response = new Response();
        $response->setResponseCode(200);

        $this->assertSame(200, $response->getResponseCode());
    }

    public function testSetBodyAndGetBodyAndGetContentLength()
    {
        $body = rand(0, 1000000);
        $response = new Response();
        $response->setBody($body);

        $this->assertSame((string)$body, $response->getBody());
        $this->assertSame(strlen($body), $response->getContentLength());
    }

    public function testSetContentTypeAndGetContentType()
    {
        $type = 'text/html';
        $response = new Response();
        $response->setContentType($type);

        $this->assertSame($type, $response->getContentType());
    }

    public function testSetServerAndGetServer()
    {
        $server = 'Foo / 0.π';
        $response = new Response();
        $response->setServer($server);

        $this->assertSame($server, $response->getServer());
    }

    public function testSetCookieAndGetCookies() {
        $time = time() + 10;
        $response = new Response();
        $response->setCookie('foo', 'bar', $time, '/', null, false, true);
        $response->setCookie('baz', 'qux', $time, null, 'foo.com', true);
        $response->setCookie('qux', 'foo', $time, null, null, true, true);
        $response->setCookie('bar', 'baz', $time);

        $this->assertSame(4, count($response->getCookies()));

        $cookies = $response->getCookies();

        $this->assertTrue(isset($cookies[0]->cookies['foo']));
        $this->assertSame('bar', $cookies[0]->cookies['foo']);
        $this->assertSame($time, $cookies[0]->expires);
        $this->assertSame('/', $cookies[0]->path);
        $this->assertSame('', $cookies[0]->domain);
        $this->assertSame(32, $cookies[0]->flags);

        $this->assertTrue(isset($cookies[1]->cookies['baz']));
        $this->assertSame('qux', $cookies[1]->cookies['baz']);
        $this->assertSame($time, $cookies[1]->expires);
        $this->assertSame('foo.com', $cookies[1]->domain);
        $this->assertSame(16, $cookies[1]->flags);

        $this->assertTrue(isset($cookies[2]->cookies['qux']));
        $this->assertSame('foo', $cookies[2]->cookies['qux']);
        $this->assertSame(48, $cookies[2]->flags);

        $this->assertTrue(isset($cookies[3]->cookies['bar']));
        $this->assertSame('baz', $cookies[3]->cookies['bar']);
        $this->assertSame(0, $cookies[3]->flags);
    }

    public function testToString()
    {
        $body = (string)rand(0, 100000);
        $response = new Response();
        $response->setBody($body);

        $message = $response->toString();
        $object = http_parse_message($message);

        $this->assertSame(200, $object->responseCode);
        $this->assertSame($body, $object->body);
        $this->assertSame('Skeetr 0.0.1', $object->headers['Server']);
        $this->assertSame('text/html', $object->headers['Content-Type']);
        $this->assertSame((string)strlen($body), $object->headers['Content-Length']);
    }

    public function testToJSON()
    {
        $body = (string)rand(0, 100000);
        $response = new Response();
        $response->setBody($body);

        $message = $response->toJSON();
        $array = json_decode($message, true);

        $this->assertSame(200, $array['responseCode']);
        $this->assertSame($body, $array['body']);
        $this->assertSame('Skeetr 0.0.1', $array['headers']['Server']);
        $this->assertSame('text/html', $array['headers']['Content-Type']);
        $this->assertSame((string)strlen($body), $array['headers']['Content-Length']);
    }

    public function testToArrayWithoutDefaults()
    {
        $body = (string)rand(0, 100000);
        $response = new Response();
        $response->setBody($body);

        $array = $response->toArray(false);

        $this->assertSame(0, $array['responseCode']);
        $this->assertSame($body, $array['body']);
        $this->assertFalse(isset($array['headers']['Server']));
        $this->assertFalse(isset($array['headers']['Content-Type']));
        $this->assertSame((string)strlen($body), $array['headers']['Content-Length']);
    }
}