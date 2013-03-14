<?php
namespace AppServer\Tests;
use AppServer\Client\Channels\ControlChannel;

use AppServer\Tests\Mocks\GearmanWorkerMock;
use AppServer\Tests\Mocks\GearmanJobMock;
use AppServer\Tests\Mocks\ClientMock;


class ControlChannelTest extends TestCase {
    public function testConstruct() {
        $client = new ClientMock;

        $channel = new ControlChannel($client, 'foo');
        $this->assertSame('foo', $channel->getChannel());

        $channel = new ControlChannel($client, 'foo_%s');
        $this->assertTrue(strlen($channel->getChannel()) > 6);   
    }

}