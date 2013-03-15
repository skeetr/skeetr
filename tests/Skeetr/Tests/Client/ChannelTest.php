<?php
namespace Skeetr\Tests;
use Skeetr\Client;
use Skeetr\Client\Channel;

use Skeetr\Mocks\Client as ClientMock;
use Skeetr\Mocks\GearmanJob;
use Skeetr\Mocks\Gearman\Worker;

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

        $worker = new Worker;
        $this->assertTrue($channel->register($worker));
    }
}

class ChannelMock extends Channel {
    public function process(\GearmanJob $job) {}
}