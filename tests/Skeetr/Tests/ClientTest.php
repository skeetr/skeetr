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
use Skeetr\Client\Journal;
use Skeetr\Mocks\Gearman\Worker;

class ClientTest extends TestCase {
    public function createClient() {
        $client = new ClientMock(new Worker);
        $client->setCallback(function() {});
        $client->setLogger($this->logger);

        return $client;
    }
    
    public function testGetIdAndSetId()
    {
        $client = $this->createClient();
        $this->assertTrue((boolean)$client->getId());

        $result = $client->setId('test');
        $this->assertSame('test', $client->getId());

        $this->assertInstanceOf('Skeetr\Client', $result);
    }

    public function testGetWorkerAndSetWorker()
    {
        $client = $this->createClient();

        $worker = $client->getWorker();
        $this->assertInstanceOf('Skeetr\Gearman\Worker', $worker);

        $result = $client->setWorker(new Worker);
        $this->assertNotSame($worker, $client->getWorker());

        $this->assertInstanceOf('Skeetr\Client', $result);
    }

    public function testGetJournalAndSetJournal()
    {
        $client = $this->createClient();

        $journal = $client->getJournal();
        $this->assertInstanceOf('Skeetr\Client\Journal', $journal);

        $result = $client->setJournal(new Journal);
        $this->assertNotSame($journal, $client->getJournal());

        $this->assertInstanceOf('Skeetr\Client', $result);
    }

    public function testGetChannelNameAndSetChannelName()
    {
        $client = $this->createClient();
        $this->assertSame('default', $client->getChannelName());

        $result = $client->setChannelName('test');
        $this->assertSame('test', $client->getChannelName());

        $this->assertInstanceOf('Skeetr\Client', $result);
    }

    public function testGetMemoryLimitAndSetMemoryLimit() 
    {
        $client = $this->createClient();
        
        $result = $client->setMemoryLimit(5);
        $this->assertSame(5, $client->getMemoryLimit());

        $this->assertInstanceOf('Skeetr\Client', $result);
    }

    public function testGetInterationsLimitAndSetInterationsLimit()
    {
        $client = $this->createClient();
        
        $result = $client->setInterationsLimit(5);
        $this->assertSame(5, $client->getInterationsLimit());

        $this->assertInstanceOf('Skeetr\Client', $result);
    }

    public function testGetSleepTimeOnErrorAndSetSleepTimeOnError()
    {
        $client = $this->createClient();
        
        $result = $client->setSleepTimeOnError(50);
        $this->assertSame(50, $client->getSleepTimeOnError());

        $this->assertInstanceOf('Skeetr\Client', $result);
    }    

    public function testGetLoggerAndSetLogger()
    {
        $client = $this->createClient();

        $this->assertInstanceOf('Psr\Log\LoggerInterface', $client->getLogger());

        $result = $client->setLogger(null);
        $this->assertNull($client->getLogger());

        $this->assertInstanceOf('Skeetr\Client', $result);
    }

    public function testSetCallbackAndGetCallback()
    {
        $client = $this->createClient();

        $closure = function() { };
        $result = $client->setCallback($closure);

        $this->assertSame($closure, $client->getCallback());

        $this->assertInstanceOf('Skeetr\Client', $result);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetCallbackException()
    {
        $client = $this->createClient();
        $client->setCallback(null);
    }

    public function testNotifySuccess()
    {
        $client = $this->createClient();
        $client->notify(Worker::STATUS_SUCCESS, 5);

        $journal = $client->getJournal();
        $this->assertSame(1, $journal->getWorks());

        $last = end($this->logs);
        $this->assertContains('Executed job in', $last['message']);
    }

    public function testNotifyDisconnected()
    {
        $client = $this->createClient();
        $client->setSleepTimeOnError(1);
        $client->notify(Worker::STATUS_DISCONNECTED);

        $journal = $client->getJournal();
        $this->assertSame(1, $journal->getLostConnection());

        $last = reset($this->logs);
        $this->assertContains('waiting 1 seconds', $last['message']);
    }

    public function testNotifyTimeout()
    {
        $client = $this->createClient();
        $client->setSleepTimeOnError(1);
        $client->notify(Worker::STATUS_TIMEOUT);

        $journal = $client->getJournal();
        $this->assertSame(1, $journal->getTimeouts());

        $last = end($this->logs);
        $this->assertContains('Timeout', $last['message']);
    }

    public function testNotifyError()
    {
        $client = $this->createClient();
        $client->setSleepTimeOnError(1);
        $client->notify(Worker::STATUS_ERROR);

        $journal = $client->getJournal();
        $this->assertSame(1, $journal->getErrors());

        $last = end($this->logs);
        $this->assertContains('mocked error', $last['message']);
    }

    public function testNotifyIdle()
    {
        $client = $this->createClient();
        $client->setSleepTimeOnError(1);
        $client->notify(Worker::STATUS_IDLE);

        $journal = $client->getJournal();
        $this->assertGreaterThan(0, $journal->getIdle());

        $last = end($this->logs);
        $this->assertContains('Waiting for job', $last['message']);
    }

    public function testWork()
    {
        $client = $this->createClient();
        $client->work();

        $this->assertTrue($client->loop);

        $channels = $client->getChannels();
        $this->assertInstanceOf('Skeetr\Client\Channels\ControlChannel', $channels['control']);
        $this->assertInstanceOf('Skeetr\Client\Channels\RequestChannel', $channels['request']);
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

    public function getChannels()
    {
        return $this->channels;
    }

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