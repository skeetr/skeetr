<?php
namespace AppServer\Tests\Gearman;
use AppServer\Tests\TestCase;
use AppServer\Gearman\Monitor;

class MonitorTest extends TestCase {
    public function testAddServer() {
        $monitor = new Monitor();
        $monitor->addServer();
        $monitor->addServer('foo', 1111);

        $expect = Array (
            '127.0.0.1:4730',
            'foo:1111'
        );

        $this->assertSame($expect, $monitor->getServers());
    }

    public function testSetTimeout() {
        $monitor = new Monitor();

        $this->assertSame(10, $monitor->getTimeout());

        $monitor->setTimeout(1111);
        $this->assertSame(1111, $monitor->getTimeout());
    }

    public function testGetVersion() {
        $monitor = new MonitorMock();
        $monitor->addServer();

        $expect = Array (
            '127.0.0.1:4730' => '1.1.5'
        );

        $this->assertSame($expect, $monitor->getVersion());
    }

    public function testGetStatus() {
        $monitor = new MonitorMock();
        $monitor->addServer();

        $expect = Array (
            '127.0.0.1:4730' => Array (
                'foo' => Array (
                    'queued' => 1,
                    'running' => 0,
                    'workers' => 0
                ),
                'qux' => Array (
                    'queued' => 0,
                    'running' => 0,
                    'workers' => 4
                )
            )
        );

        $this->assertSame($expect, $monitor->getStatus());

        $status = $monitor->getStatus(true);
        $this->assertSame(3, count($status['127.0.0.1:4730']));
    }

    public function testGetWorkers() {
        $monitor = new MonitorMock();
        $monitor->addServer();

        $expect = Array (
            '127.0.0.1:4730' => Array (
                Array (
                    'fd' => '37',
                    'ip' => '0.0.0.0',
                    'id' => '-',
                    'functions' => Array (
                        'foo',
                        'bar',
                    )
                )
            )
        );

        $this->assertSame($expect, $monitor->getWorkers());

        $status = $monitor->getWorkers(true);
        $this->assertSame(2, count($status['127.0.0.1:4730']));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCheckForError() {
        $monitor = new MonitorMock();
        $monitor->addServer();
        $monitor->produceError();
    } 
}


class MonitorMock extends Monitor {
    protected function sendCommand($command, $multiline, $servers = null) {
        return array(
            '127.0.0.1:4730' => TestCase::getResource('Gearman/' . ucfirst($command))
        );
    }

    public function produceError() {
        $this->checkForError(TestCase::getResource('Gearman/Error'));
    }
}