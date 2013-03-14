<?php
namespace AppServer\Tests;
use AppServer\Client\Channels\RequestChannel;

use AppServer\Tests\Mocks\GearmanWorkerMock;
use AppServer\Tests\Mocks\GearmanJobMock;
use AppServer\Tests\Mocks\ClientMock;


class RequestChannelTest extends TestCase {
    public function testProcess() {
        $client = new ClientMock;

        $channel = new RequestChannel($client);
        $channel->setCallback(function($request) { 
            return $request->getUrl(); 
        });

        $job = new GearmanJobMock;
        $this->assertSame('/filename.html', $channel->process($job));
        $this->assertTrue(0 < $client->getTime());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterNoChannel() {
        $client = new ClientMock;

        $channel = new RequestChannel($client);
        $channel->setChannel('test');

        $worker = new GearmanWorkerMock;
        $channel->register($worker);
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterNoCallback() {
        $client = new ClientMock;

        $channel = new RequestChannel($client);
        $channel->setCallback(function($request) { 
            return $request->getUrl(); 
        });
        
        $worker = new GearmanWorkerMock;
        $channel->register($worker);
    }
}