<?php
namespace Skeetr\Tests\Gearman;
use Skeetr\Tests\TestCase;
use Skeetr\Debugger\Watcher;

class WatcherTest extends TestCase {
    public function testAddPattern() {
        $watcher = new WatcherMock();
        $watcher->addPattern('foo');

        $expect = array('foo');
        $this->assertSame($expect, $watcher->getPatterns());
    }

    public function testAddPatterns() {
        $watcher = new WatcherMock();
        $watcher->addPatterns(array('foo', 'bar'));

        $expect = array('foo', 'bar');
        $this->assertSame($expect, $watcher->getPatterns());
    }

    public function testTrack() {
        $watcher = new WatcherMock();
        $watcher->addPattern(__DIR__ . '/*.php');
        $watcher->track();

        //$this->assertSame(2, count($watcher->getFiles()));
    }
}


class WatcherMock extends Watcher {
    protected $files = array();

    public function watch() {

    }

    public function getFiles() {
        return $this->files;
    }

    protected function trackFile($filename) {
        $this->files[] = $filename;
    }
}