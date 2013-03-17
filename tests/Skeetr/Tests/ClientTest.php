<?php
namespace Skeetr\Tests;
use Skeetr\Client;
use Skeetr\Mocks\Gearman\Worker;

class ClientTest extends TestCase {
    public function createClient() {
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $worker = new Worker();
        return new ClientMock($logger, $worker);
    }

    public function testSetId() {
        $client = $this->createClient();
        $this->assertTrue((boolean)$client->getId());

        $client->setId('test');
        $this->assertSame('test', $client->getId());
    }

    public function testSetChannel() {
        $client = $this->createClient();
        $this->assertSame('default', $client->getChannel());

        $client->setChannel('test');
        $this->assertSame('test', $client->getChannel());
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