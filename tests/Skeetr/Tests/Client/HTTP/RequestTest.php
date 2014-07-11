<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Tests\Client\HTTP;

use Skeetr\Tests\TestCase;
use Skeetr\Client\HTTP\Request;
use http\Message;

class RequestTest extends TestCase
{
    public function getRequest($method)
    {
        $json = file_get_contents(__DIR__ . '/../../../../Resources/Request/' . $method);
        $data = json_decode($json, true);

        $params = end($data['params']);

        return Request::fromArray($params);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testFromArrayInvalid()
    {
        Request::fromArray([]);
    }

    public function testGetTimestamp()
    {
        $r = $this->getRequest('GET');
        $this->assertSame((int) microtime(true), (int) $r->getTimestamp());
    }

    public function testGetServerInfo()
    {
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

    public function testGetRemoteInfo()
    {
        $r = $this->getRequest('GET');

        $expected = array(
            'addr' => '83.59.53.215', 'port' => '52370'
        );

        $this->assertSame($expected, $r->getRemoteInfo());
        $this->assertSame('83.59.53.215', $_SERVER['REMOTE_ADDR']);
        $this->assertSame('52370', $_SERVER['REMOTE_PORT']);
    }

    public function testGetHeaders()
    {
        $this->assertTrue(is_array($this->getRequest('GET')->getHeaders()));

        $this->assertSame('localhost:1234', $_SERVER['HTTP_HOST']);
        $this->assertSame('*/*', $_SERVER['HTTP_ACCEPT']);
        $this->assertSame('keep-alive', $_SERVER['HTTP_CONNECTION']);
        $this->assertSame('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.153 Safari/537.36', $_SERVER['HTTP_USER_AGENT']);
        $this->assertSame('gzip,deflate,sdch', $_SERVER['HTTP_ACCEPT_ENCODING']);
        $this->assertSame('en-US,en;q=0.8,de;q=0.6,es;q=0.4', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->assertSame('__utma=62420665.1497995081.1405013807.1405013807.1405098118.2; __utmc=62420665; __utmz=62420665.1405098118.2.2.utmcsr=golanggo.com|utmccn=(referral)|utmcmd=referral|utmcct=/post/91465473544/visualising-the-go-garbage-collector', $_SERVER['HTTP_COOKIE']);
    }

    public function testGetMethod()
    {
        $this->assertSame('GET', $this->getRequest('GET')->getRequestMethod());
        $this->assertSame('POST', $this->getRequest('POST')->getRequestMethod());

        $this->assertSame('POST', $_SERVER['REQUEST_METHOD']);
    }

    public function testGetURL()
    {
        $this->assertSame('/foo?foo=bar', $this->getRequest('GET')->getRequestUrl());
        $this->assertSame('/foo?foo=bar', $_SERVER['REQUEST_URI']);
    }

    public function testGetPostFields()
    {
        $expected = array(
            'foo' => 'bar',
            'qux' => 'baz'
        );

        $this->assertSame($expected, $this->getRequest('POST')->getPostFields());
        $this->assertSame($expected, $_POST);
    }

    /**
     * @expectedException Exception
     */
    public function testGetPostFiles()
    {
        $this->getRequest('POST')->getPostFiles();
    }

    /**
     * @expectedException Exception
     */
    public function testSetPostFiles()
    {
        $this->getRequest('POST')->setPostFiles(array());
    }

    public function testGetQueryFields()
    {
        $expected = array(
            'foo' => 'bar'
        );

        $this->assertSame($expected, $this->getRequest('GET')->getQueryFields());
        $this->assertSame($expected, $_GET);
    }

    public function testGetHeader()
    {
        $this->assertSame(['keep-alive'], $this->getRequest('GET')->getHeader('Connection'));
    }

    public function testGetCookies()
    {
        $cookies = $this->getRequest('POST')->getCookies();

        $this->assertSame('value test', $cookies['cookie']);
        $this->assertSame(array('cookie' => 'value test'), $_COOKIE);
    }

    public function testGetQueryData()
    {
        $qs = $this->getRequest('GET')->getQueryData();
        $this->assertSame('foo=bar', $qs);
    }

    public function testToString()
    {
        $request = $this->getRequest('POST');

        $message = new Message($request->toString());

        $this->assertSame('/foo?foo=bar', $request->getRequestUrl());
        $this->assertSame('POST', $request->getRequestMethod());

        $this->assertTrue((bool) $request->getHeader('Host'));
        $this->assertTrue((bool) $request->getHeader('Cookie'));

        $this->assertCount(10, $request->getHeaders());

      //  $this->assertSame("foo=bar&baz=qux", $message->getBody()->toString());
    }

    public function testToJSON()
    {
        $request = $this->getRequest('GET');

        $message = $request->toJSON();
        $array = json_decode($message, true);
        print_r($array);
        var_dump((string) $message);

        $this->assertSame('/foo?foo=bar', $array['url']);
        $this->assertSame('GET', $array['method']);

        $this->assertTrue(isset($array['headers']['Host']));
        $this->assertTrue(isset($array['headers']['Cookie']));
        $this->assertTrue(isset($array['server']));
        $this->assertTrue(isset($array['remote']));

        $this->assertCount(7, $array['headers']);
        $this->assertSame('bar', $array['get']['foo']);
    }

    public function testToArray()
    {
        $request = $this->getRequest('GET');

        $array = $request->toArray(false);

        $this->assertSame('/foo?foo=bar', $array['url']);
        $this->assertSame('GET', $array['method']);


        $this->assertTrue(isset($array['headers']['Host']));
        $this->assertTrue(isset($array['headers']['Cookie']));
        $this->assertTrue(isset($array['server']));
        $this->assertTrue(isset($array['remote']));

        $this->assertCount(7, $array['headers']);

        $this->assertSame('bar', $array['get']['foo']);
    }
}
