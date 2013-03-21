<?php
namespace Skeetr\Tests\Overrides;
use Skeetr\Tests\TestCase;
use Skeetr\Overrides\Header;
use Skeetr\HTTP\Response;


class HeaderTest extends TestCase {
    public function testHeaderWithReplace() {
        header('Foo: bar');
        header('Foo: baz');

        $response = new Response();
        Header::configure($response);

        $headers = $response->getHeader('Foo');
        $expect = array(
            array('Foo', 'baz')
        );
        
        $this->assertSame($expect, $headers);
    }

    public function testHeaderWithNoReplace() {
        header('Foo: bar');
        header('Foo: baz', false);

        $response = new Response();
        Header::configure($response);

        $headers = $response->getHeader('Foo');
        $expect = array(
            array('Foo', 'bar'),
            array('Foo', 'baz'),
        );

        $this->assertSame($expect, $headers);
    }

    public function testHeaderCode() {
        $response = new Response();
        Header::configure($response);

        $this->assertSame(200, $response->getResponseCode());
    }

    public function testHeaderWithLocation() {
        header('Location: http://www.example.com/');

        $response = new Response();
        Header::configure($response);

        $this->assertSame(302, $response->getResponseCode());
    }

    public function testHeaderWithCode() {
        header('Foo: bar', true, 404);

        $response = new Response();
        Header::configure($response);

        $this->assertSame(404, $response->getResponseCode());
    }

    public function testHeaderRemove() {
        header('Foo: bar');
        header_remove();

        $this->assertSame(array(), headers_list());

        header('Foo: bar');
        header('Baz: bar');

        header_remove('Foo');
        $this->assertSame(array('Baz: bar'), headers_list());

        $response = new Response();
        Header::configure($response);
    }

    public function testHeaderSent() {
        $this->assertSame(null, headers_sent());
    }

    public function testHeaderRegisterCallback() {
        if ( !function_exists('header_register_callback') ) return true;
        
        $called = 0;
        header_register_callback(function() use (&$called) {
            $called = 1;
        });

        $response = new Response();
        Header::configure($response);

        $this->assertSame(1, $called);
    }
}