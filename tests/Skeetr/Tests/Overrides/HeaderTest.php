<?php
namespace Skeetr\Tests\Overrides;
use Skeetr\Tests\TestCase;
use Skeetr\Overrides\Header;


class HeaderTest extends TestCase {
    public function testHeaderWithReplace() {
        Header::register();
        header_remove();

        header('Foo: bar');
        header('Foo: baz');

        $this->assertSame(array('Foo: baz'), headers_list());
    }

    public function testHeaderWithNoReplace() {
        Header::register();
        header_remove();

        header('Foo: bar');
        header('Foo: baz', false);

        $this->assertSame(array('Foo: bar', 'Foo: baz'), headers_list());
    }

    public function testHeaderWithLocation() {
        Header::register();
        header_remove();

        $this->assertSame(200, Header::code());

        header('Location: http://www.example.com/');
        $this->assertSame(302, Header::code());

        header('Foo: bar', true, 404);
        $this->assertSame(404, Header::code());

    }

    public function testHeaderRemove() {
        Header::register();
        header_remove();

        header('Foo: bar');
        header_remove();

        $this->assertSame(array(), headers_list());

        header('Foo: bar');
        header('Baz: bar');

        header_remove('Foo');
        $this->assertSame(array('Baz: bar'), headers_list());
    }

    public function testHeaderSent() {
        $this->assertSame(null, headers_sent());
    }


    public function testHeaderRegisterCallback() {
        $called = 0;
        header_register_callback(function() use (&$called) {
            $called = 1;
        });

        Header::headers();
        $this->assertSame(1, $called);
    }
}