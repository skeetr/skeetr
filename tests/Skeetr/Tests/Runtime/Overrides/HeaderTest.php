<?php
namespace Skeetr\Tests\Runtime\Overrides;
use Skeetr\Tests\TestCase;
use Skeetr\Runtime\Manager;

class HeaderTest extends TestCase {
    public function testHeaderWithReplace() {
        header('Foo: bar');
        header('Foo: baz');

        $values = Manager::values();
        $expect = array('Foo: baz');
        
        $this->assertSame($expect, $values['header']['list']['Foo']);
    }

    public function testHeaderWithNoReplace() {
        header('Foo: bar');
        header('Foo: baz', false);

        $values = Manager::values();
        $expect = array('Foo: bar', 'Foo: baz');

        $this->assertSame($expect, $values['header']['list']['Foo']);
    }

    public function testHeaderCode() {
        $values = Manager::values();
        $this->assertSame(200, $values['header']['code']);
    }

    public function testHeaderWithLocation() {
        header('Location: http://www.example.com/');

        $values = Manager::values();
        $this->assertSame(302, $values['header']['code']);    
    }

    public function testHeaderWithCode() {
        header('Foo: bar', true, 404);

        $values = Manager::values();
        $this->assertSame(404, $values['header']['code']);   
    }

    public function testHeaderRemove() {
        header('Foo: bar');
        header_remove();

        $this->assertSame(array(), headers_list());

        header('Foo: bar');
        header('Baz: bar');

        header_remove('Foo');
        $this->assertSame(array('Baz: bar'), headers_list());
    }

    public function testHeaderSent() {
        $this->assertSame(false, headers_sent());
    }

    public function testHeaderRegisterCallback() {
        if ( !function_exists('header_register_callback') ) return true;
        
        $callback = function() use (&$called) {
            $called = 1;
        };

        header_register_callback($callback);

        $values = Manager::values();
        $this->assertSame($callback, $values['header']['callback']); 
    }
}