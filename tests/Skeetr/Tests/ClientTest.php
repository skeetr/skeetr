<?php
namespace Skeetr\Tests;
use Skeetr\Client;
use Skeetr\Mocks\Gearman\Worker;

class ClientTest extends TestCase {
    public function createClient() {
        $worker = new Worker();
        return new ClientMock($worker, 'test');
    }

    public function testConstruct() {
        $worker = new Worker();
        $client = new ClientMock($worker, 'test', 'myId');

        $this->assertInstanceOf('GearmanWorker', $client->getGearman());
        $this->assertInstanceOf('Skeetr\Client\Journal', $client->getJournal());
        $this->assertSame('test', $client->getChannel());
        $this->assertSame('myId', $client->getId());
    }

    public function testAddServer() {
        $client = $this->createClient();

        $host = 'test'; $port = 1111;
        $this->assertSame(array($host, $port), $client->addServer($host, $port));
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

    public function testGetId() {
        $client = $this->createClient();
        $this->assertTrue((boolean)$client->getId());
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