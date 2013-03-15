<?php
namespace AppServer\Tests;
use AppServer\Client\Channels\ControlChannel;
use AppServer\Mocks\Client;

class ControlChannelTest extends TestCase {
    public function testConstruct() {
        $client = new Client;

        $channel = new ControlChannel($client, 'foo');
        $this->assertSame('foo', $channel->getChannel());

        $channel = new ControlChannel($client, 'foo_%s');
        $this->assertTrue(strlen($channel->getChannel()) > 6);   
    }

}