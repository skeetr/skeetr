<?php
namespace Skeetr\Tests\Runtime\Overrides;

use Skeetr\Tests\TestCase;

class CookieTest extends TestCase
{
    public function testSetCookie()
    {
        setcookie('foo', 'bar', strtotime('25 Nov 2015 00:00:00 GMT'));

        $headers = headers_list();
        $this->assertSame(1, count($headers));
        $this->assertSame(
            'Set-Cookie: foo=bar; expires=Wed, 25 Nov 2015 00:00:00 GMT; ', $headers[0]
        );

        setcookie('foo', 'baz', strtotime('25 November 2015 GMT'));

        $headers = headers_list();
        $this->assertSame(2, count($headers));
        $this->assertSame(
            'Set-Cookie: foo=baz; expires=Wed, 25 Nov 2015 00:00:00 GMT; ', $headers[1]
        );

        setcookie('bar', 'foo baz', strtotime('25 Nov 2015 00:00:00 GMT'));

        $headers = headers_list();
        $this->assertSame(3, count($headers));
        $this->assertSame(
            'Set-Cookie: bar=foo%2520baz; expires=Wed, 25 Nov 2015 00:00:00 GMT; ', $headers[2]
        );
    }

    public function testSetRawCookie()
    {
        setrawcookie('foo', 'bar baz', strtotime('25 Nov 2015 00:00:00 GMT'));

        $headers = headers_list();
        $this->assertSame(
            'Set-Cookie: foo=bar+baz; expires=Wed, 25 Nov 2015 00:00:00 GMT; ', $headers[0]
        );
    }
}
