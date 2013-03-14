<?php
namespace AppServer\Tests;
use AppServer\Client;
use AppServer\Tests\Mocks\GearmanWorkerMock;

class ClientTest extends TestCase {
    public function createClient() {
        $worker = new GearmanWorkerMock();
        return new ClientMock($worker, 'test');
    }

    public function testConstruct() {
        $client = $this->createClient();

        $this->assertInstanceOf('GearmanWorker', $client->getGearman());
        $this->assertInstanceOf('AppServer\Client\Journal', $client->getJournal());
        $this->assertSame('test', $client->getChannel());
    }

    public function testAddServer() {
        $client = $this->createClient();

        $host = 'test'; $port = 1111;
        $this->assertSame([$host, $port], $client->addServer($host, $port));
    }

    public function testSetCallback() {
        $client = $this->createClient();

        $closure = function() { };
        $client->setCallback($closure);

        $this->assertSame($closure, $client->getCallback());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetCallbackException() {
        $client = $this->createClient();
        $client->setCallback(null);
    }

    public function testSetSleepTimeOnError() {
        $client = $this->createClient();

        $client->setSleepTimeOnError(5);
        $this->assertSame(5, $client->getSleepTimeOnError());
    }

    public function testNotifyExecution() {
        $client = $this->createClient();
        $client->notifyExecution(5);

        $journal = $client->getJournal();
        $this->assertSame(1, $journal->getWorks());
    }

    public function testEvaluate() {
        $client = $this->createClient();
        $client->setSleepTimeOnError(1);

        $journal = $client->getJournal();

        $client->evaluate(GEARMAN_SUCCESS);
        $client->evaluate(GEARMAN_SUCCESS);
        $this->assertTrue($journal->getIdle() > 0);

        $client->evaluate(GEARMAN_TIMEOUT);
        $this->assertSame(1, $journal->getTimeouts());

        $client->evaluate(GEARMAN_NO_JOBS);
        $this->assertSame(1, $journal->getLostConnection());

        $client->evaluate(GEARMAN_IO_WAIT);
        $this->assertSame(2, $journal->getLostConnection());

        $client->evaluate(GEARMAN_ERRNO);
        $this->assertSame(1, $journal->getErrors());
        $this->assertSame('mocked error', $journal->getLastError());
    }
}


class ClientMock extends Client {
    public function evaluate($code) {
        return parent::evaluate($code);
    }
}