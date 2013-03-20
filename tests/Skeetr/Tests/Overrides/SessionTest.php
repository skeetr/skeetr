<?php
namespace Skeetr\Tests\Overrides;
use Skeetr\Tests\TestCase;
use Skeetr\Overrides\Cookie;
use Skeetr\Overrides\Header;
use Skeetr\Overrides\Session;

class SessionTest extends TestCase {
    public function register() {
        Cookie::register();
        Header::register();
        Session::register();
        //header_remove(); 
    }

    public function testSessionId() {
        $this->register();

        $this->assertSame(null, session_id());

        session_start();
        $this->assertTrue(strlen(session_id()) > 0);
    }

    public function testSessionStatus() {
        $this->register();

        $this->assertSame(PHP_SESSION_NONE, session_status());

        session_start();
        $this->assertSame(PHP_SESSION_ACTIVE, session_status());    
    }

    public function testSessionWriteClose() {
        $this->register();

        session_start();
        $_SESSION['foo'] = 'bar';

        session_write_close();

        $file = sprintf('%s/sess_%s', sys_get_temp_dir(), session_id());
        $data = unserialize(file_get_contents($file));

        $this->assertSame('bar', $data['foo']);
    }

    public function testSessionDestroy() {
        $this->register();

        $this->assertFalse(session_destroy());

        $_SESSION['foo'] = 'bar';

        session_start();
        session_commit();
        $this->assertTrue(session_destroy());


        $file = sprintf('%s/sess_%s', sys_get_temp_dir(), session_id());
        $this->assertFalse(file_exists($file));
    }

    public function testSessionEncode() {
        $this->register();

        $_SESSION['foo'] = 'bar';

        $data = unserialize(session_encode());
        $this->assertSame('bar', $data['foo']); 
    }

    public function testSessionDecode() {
        $this->register();

        session_decode('a:1:{s:3:"foo";s:3:"bar";}');
        $this->assertSame('bar', $_SESSION['foo']); 
    }

    public function testSessionGetCookieParams() {
        $this->register();

        $expect = Array (
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false
        );

        $this->assertSame($expect, session_get_cookie_params()); 
    }

    public function testSessionCacheLimiter() {
        $this->register();

        ini_set('session.cache_limiter', 'nocache');
        $this->assertSame('nocache', session_cache_limiter()); 

        session_cache_limiter('private');
        $this->assertSame('private', ini_get('session.cache_limiter')); 
    }

    public function testSessionCacheExpire() {
        $this->register();

        ini_set('session.cache_expire', '180');
        $this->assertSame(180, session_cache_expire()); 

        session_cache_expire('200');
        $this->assertSame(200, (int)ini_get('session.cache_expire')); 
    }

    public function testSessionRegenerateId() {
        $this->register();

        session_start();
        $old = session_id();
        session_regenerate_id();
        $new = session_id();

        $this->assertFalse($old == $new); 
    }
}