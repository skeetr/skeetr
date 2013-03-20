<?php
namespace Skeetr\Tests\Overrides;
use Skeetr\Tests\TestCase;
use Skeetr\Overrides\Cookie;
use Skeetr\Overrides\Header;


class CookieTest extends TestCase {
    public function testSetCookie() {
        Cookie::register();
        Header::register();
        header_remove();

        setcookie('foo', 'bar', strtotime('25 November 2015'));
    
        $headers = headers_list();
        $this->assertSame(
            'Set-Cookie: foo=bar; expires=Tue, 24 Nov 2015 23:00:00 GMT; ', $headers[0]
        );

        setcookie('foo', 'baz', strtotime('25 November 2015'));
    
        $headers = headers_list();
        $this->assertSame(
            'Set-Cookie: foo=baz; expires=Tue, 24 Nov 2015 23:00:00 GMT; ', $headers[0]
        );

        setcookie('bar', 'foo baz', strtotime('25 November 2015'));
    
        $headers = headers_list();
        $this->assertSame(
            'Set-Cookie: foo=baz; bar=foo%2520baz; expires=Tue, 24 Nov 2015 23:00:00 GMT; ', $headers[0]
        );

        Cookie::reset();
    }

    public function testSetRawCookie() {
        Cookie::register();
        Header::register();
        header_remove();

        setrawcookie('foo', 'bar baz', strtotime('25 November 2015'));
    
        $headers = headers_list();
        $this->assertSame(
            'Set-Cookie: foo=bar+baz; expires=Tue, 24 Nov 2015 23:00:00 GMT; ', $headers[0]
        );

        Cookie::reset();
    }
}



