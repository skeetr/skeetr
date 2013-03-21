<?php
namespace Skeetr\Tests\Overrides;
use Skeetr\Tests\TestCase;
use Skeetr\Overrides\Cookie;
use Skeetr\Overrides\Header;


class CookieTest extends TestCase {
    public function testSetCookie() {
        setcookie('foo', 'bar', strtotime('25 Nov 2015 00:00:00 GMT'));
    
        $headers = headers_list();
        $this->assertSame(
            'Set-Cookie: foo=bar; expires=Wed, 25 Nov 2015 00:00:00 GMT; ', $headers[0]
        );

        setcookie('foo', 'baz', strtotime('25 November 2015 GMT'));
    
        $headers = headers_list();
        $this->assertSame(
            'Set-Cookie: foo=baz; expires=Wed, 25 Nov 2015 00:00:00 GMT; ', $headers[0]
        );

        setcookie('bar', 'foo baz', strtotime('25 Nov 2015 00:00:00 GMT'));
    
        $headers = headers_list();
        $this->assertSame(
            'Set-Cookie: foo=baz; bar=foo%2520baz; expires=Wed, 25 Nov 2015 00:00:00 GMT; ', $headers[0]
        );
    }

    public function testSetRawCookie() {
        setrawcookie('foo', 'bar baz', strtotime('25 Nov 2015 00:00:00 GMT'));
    
        $headers = headers_list();
        $this->assertSame(
            'Set-Cookie: foo=bar+baz; expires=Wed, 25 Nov 2015 00:00:00 GMT; ', $headers[0]
        );
    }
}



