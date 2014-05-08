<?php
namespace Skeetr\Tests\Gearman;

use Skeetr\Tests\TestCase;
use Skeetr\Debugger\Watchers\RecursiveIteratorWatcher;

class RecursiveIteratorWatcherTest extends TestCase
{
    public function testWatch()
    {
        $watcher = new RecursiveIteratorWatcher();
        $watcher->addPattern(sys_get_temp_dir() . '/*.php');

        $file = sys_get_temp_dir() . '/test.php';
        shell_exec(sprintf('echo "1" > %s', $file));

        $watcher->track();
        $this->assertFalse($watcher->watch());

        sleep(1);
        shell_exec(sprintf('echo "2" >> %s', $file));
        clearstatcache();
        //$this->assertTrue($watcher->watch());
    }
}
