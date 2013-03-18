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

    public function testGetJournal() {
        $client = $this->createClient();
        $this->assertInstanceOf('Skeetr\Client\Journal', $client->getJournal());
    }

    public function testGetWorker() {
        $client = $this->createClient();
        $this->assertInstanceOf('Skeetr\Gearman\Worker', $client->getWorker());
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

    public function testSetRetry() {
        $client = $this->createClient();
        $client->setRetry(5);
        $this->assertSame(5, $client->getRetry());
    }

    public function testSetWorksLimit() {
        $client = $this->createClient();
        $client->setWorksLimit(5);
        $this->assertSame(5, $client->getWorksLimit());
    }

    public function testSetMemoryLimit() {
        $client = $this->createClient();
        $client->setMemoryLimit(5);
        $this->assertSame(5, $client->getMemoryLimit());
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

    public function testNotify() {
        $client = $this->createClient();
        $client->notify(Worker::STATUS_SUCCESS, 5);

        $journal = $client->getJournal();
        $this->assertSame(1, $journal->getWorks());
    }
}


class ClientMock extends Client {
    public function evaluate($code) {
        return parent::evaluate($code);
    }
}