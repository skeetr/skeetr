<?php
namespace AppServer\Tests;
use AppServer\Client;
use AppServer\Client\Channel;

use AppServer\Tests\Mocks\GearmanWorkerMock;
use AppServer\Tests\Mocks\GearmanJobMock;
use AppServer\Tests\Mocks\ClientMock;

class ChannelTest extends TestCase {
    public function testSetChannel() {
        $channel = new ChannelMock(new ClientMock);
        $channel->setChannel('channel');

        $this->assertSame('channel', $channel->getChannel());
    }

    public function testSetTimeout() {
        $channel = new ChannelMock(new ClientMock);
        $channel->setTimeout(5);

        $this->assertSame(5, $channel->getTimeout());
    }

    public function testRegister() {
        $client = new ClientMock;

        $channel = new ChannelMock($client);
        $channel->setTimeout(3);
        $channel->setChannel('test');

        $worker = new GearmanWorkerMock;
        $this->assertTrue($channel->register($worker));
    }
}

class ChannelMock extends Channel {
    public function process(\GearmanJob $job) {}
}