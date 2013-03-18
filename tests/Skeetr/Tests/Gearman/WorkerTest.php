<?php
namespace Skeetr\Tests;
use Skeetr\Client;
use Skeetr\Gearman\Worker;

class WorkerTest extends TestCase {
    public function createWorker() {
        $this->mock = $this->getMock('GearmanWorker');

        $worker = new WorkerMock();
        $worker->setWorker($this->mock);



        return $worker;
    }
    public function testAddServer() {
        $worker = $this->createWorker();
        $worker->addServer();
        $worker->addServer('192.168.0.1', 1111);

        $expect = Array (
            '127.0.0.1:4730',
            '192.168.0.1:1111'
        );

        $this->assertSame($expect, $worker->getServers());
    }

    public function testGetLastError() {
        $worker = $this->createWorker();

        $this->mock->expects($this->any())
            ->method('error')
            ->will($this->returnValue('foo'));

        $this->assertSame('foo', $worker->getLastError());
    }

    public function testEvaluateIdle() {
        $worker = $this->createWorker();
        $this->mock->expects($this->any())
            ->method('returnCode')
            ->will($this->returnValue(GEARMAN_SUCCESS));

        $this->assertSame(Worker::STATUS_IDLE, $worker->work());
    }


    public function testEvaluateError() {
        $worker = $this->createWorker();
        $this->mock->expects($this->any())
            ->method('returnCode')
            ->will($this->returnValue(2000));

        $this->assertSame(Worker::STATUS_ERROR, $worker->work());
    }


    public function testEvaluateDisconnect() {
        $worker = $this->createWorker();
        $this->mock->expects($this->any())
            ->method('returnCode')
            ->will($this->onConsecutiveCalls(
                GEARMAN_NO_JOBS,
                GEARMAN_NO_ACTIVE_FDS
            ));

        $this->assertSame(Worker::STATUS_DISCONNECTED, $worker->work());
    }

    public function testEvaluateDisconnectIdle() {
        $worker = $this->createWorker();
        $this->mock->expects($this->any())
            ->method('returnCode')
            ->will($this->onConsecutiveCalls(
                GEARMAN_IO_WAIT,
                null
            ));

        $this->assertSame(Worker::STATUS_IDLE, $worker->work());
    }

    public function testEvaluateTimeout() {
        $worker = $this->createWorker();
        $this->mock->expects($this->any())
            ->method('returnCode')
            ->will($this->returnValue(GEARMAN_TIMEOUT));

        $this->assertSame(Worker::STATUS_TIMEOUT, $worker->work());
    }
}

class WorkerMock extends Worker {
    public function setWorker($worker) {
        $this->instance = $worker;
    }
}

