<?php
namespace Skeetr\Tests\Runtime\Overrides;
use Skeetr\Tests\TestCase;
use Skeetr\Runtime\Manager;
use Skeetr\Runtime\Overrides\Session;

class SessionTest extends TestCase {
    public function testSessionId() {
        $this->assertSame(null, session_id());

        session_start();
        $this->assertTrue(strlen(session_id()) > 0);
    }

    public function testSessionStartWithId() {
        session_name('test');
        $_COOKIE['test'] = 'id';

        session_start();
        $values = Session::values();

        $this->assertSame('id', $values['id']);

        session_write_close();
        session_start();
    }

    public function testSessionStatus() {
        if ( !Manager::overridden('session_status') ) return false;
        
        $this->assertSame(PHP_SESSION_NONE, session_status());

        session_start();
        $this->assertSame(PHP_SESSION_ACTIVE, session_status());    
    }

    public function testSessionWriteClose() {
        session_start();
        $_SESSION['foo'] = 'bar';

        session_write_close();

        $file = sprintf('%s/sess_%s', sys_get_temp_dir(), session_id());
        $data = unserialize(file_get_contents($file));

        $this->assertSame('bar', $data['foo']);
    }

    public function testSessionWriteCloseWithHandler() {
        $handler = $this->getMock('SessionHandlerInterface');

        $close = false;
        $handler->expects($this->any())
            ->method('close')
            ->will($this->returnCallback(function() use (&$close) { $close = true; }));

        $write = false;
        $handler->expects($this->any())
            ->method('write')
            ->will($this->returnCallback(function() use (&$write) { $write = true; }));

        session_set_save_handler($handler);
        session_start();
        $_SESSION['foo'] = 'bar';

        session_write_close();

        $file = sprintf('%s/sess_%s', sys_get_temp_dir(), session_id());
        $data = unserialize(file_get_contents($file));

        $this->assertSame('bar', $data['foo']);

        $this->assertTrue($close);
        $this->assertTrue($write);
    }

    public function testSessionDestroy() {
        $this->assertFalse(session_destroy());

        $_SESSION['foo'] = 'bar';

        session_start();
        session_commit();
        $this->assertTrue(session_destroy());


        $file = sprintf('%s/sess_%s', sys_get_temp_dir(), session_id());
        $this->assertFalse(file_exists($file));
    }

    public function testSessionEncode() {
        $_SESSION['foo'] = 'bar';

        $data = unserialize(session_encode());
        $this->assertSame('bar', $data['foo']); 
    }

    public function testSessionDecode() {
        session_decode('a:1:{s:3:"foo";s:3:"bar";}');
        $this->assertSame('bar', $_SESSION['foo']); 
    }

    public function testSessionGetCookieParams() {
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
        ini_set('session.cache_limiter', 'nocache');
        $this->assertSame('nocache', session_cache_limiter()); 

        session_cache_limiter('private');
        $this->assertSame('private', ini_get('session.cache_limiter')); 
    }

    public function testSessionCacheExpire() {
        ini_set('session.cache_expire', '180');
        $this->assertSame(180, session_cache_expire()); 

        session_cache_expire('200');
        $this->assertSame(200, (int)ini_get('session.cache_expire')); 
    }

    public function testSessionRegenerateId() {
        session_start();
        $old = session_id();
        session_regenerate_id();
        $new = session_id();

        $this->assertFalse($old == $new); 
    }

    public function testSessionSetCookieParams()
    {
        session_set_cookie_params(314159, '/foo/bar', 'foo.com', true, true);

        $this->assertSame('314159', ini_get('session.cookie_lifetime'));
        $this->assertSame('/foo/bar', ini_get('session.cookie_path'));
        $this->assertSame('foo.com', ini_get('session.cookie_domain'));
        $this->assertSame('1', ini_get('session.cookie_secure'));
        $this->assertSame('1', ini_get('session.cookie_httponly'));

        $config = session_get_cookie_params();
        $this->assertSame(314159, $config['lifetime']);
        $this->assertSame('/foo/bar', $config['path']);
        $this->assertSame('foo.com', $config['domain']);
        $this->assertTrue($config['secure']);
        $this->assertTrue($config['httponly']);
    }
}