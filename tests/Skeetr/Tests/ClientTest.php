<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Tests;
use Skeetr\Client;
use Skeetr\Mocks\Gearman\Worker;

class ClientTest extends TestCase {
    public function createClient() {
        $client = new ClientMock(new Worker);
        $client->setCallback(function() {});
        $client->setLogger($this->logger);

        return $client;
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

    public function testSetInterationsLimit() {
        $client = $this->createClient();
        $client->setInterationsLimit(5);
        $this->assertSame(5, $client->getInterationsLimit());
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

    public function testShutdown()
    {
        $client = $this->createClient();

        $this->assertFalse($client->shutdown());

        $client->work();
        $this->assertTrue($client->shutdown());
    }

    public function testCheckStatusMemoryLimit()
    {
        $client = $this->createClient();
        
        $client->work();
        $this->assertTrue($client->loop);

        $client->setMemoryLimit(10);

        $client->work();
        $this->assertFalse($client->loop);

        $last = end($this->logs);
        $this->assertContains('Memory limit reached', $last['message']);
    }

    public function testCheckStatusIterationsLimit()
    {
        $client = $this->createClient();
        
        $client->work();
        $this->assertTrue($client->loop);

        $client->setInterationsLimit(1);

        $client->work();
        $this->assertFalse($client->loop);

        $last = end($this->logs);
        $this->assertContains('Iteration limit reached', $last['message']);
    }
}


class ClientMock extends Client {
    public $loop = false;

    public function evaluate($code)
    {
        return parent::evaluate($code);
    }

    protected function loop()
    {
        $this->loop = true;
        $this->notify(Worker::STATUS_SUCCESS);

        $this->checkStatus();
    }
}