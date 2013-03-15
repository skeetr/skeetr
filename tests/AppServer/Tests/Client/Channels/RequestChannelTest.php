<?php
namespace AppServer\Tests;
use AppServer\Client\Channels\RequestChannel;

use AppServer\Mocks\Client;
use AppServer\Mocks\GearmanJob;
use AppServer\Mocks\Gearman\Worker;

class RequestChannelTest extends TestCase {
    public function testProcess() {
        $client = new Client;

        $channel = new RequestChannel($client);
        $channel->setCallback(function($request) { 
            return $request->getUrl(); 
        });

        $job = new GearmanJob;
        $this->assertSame('/filename.html', $channel->process($job));
        $this->assertTrue(0 < $client->getTime());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterNoChannel() {
        $client = new Client;

        $channel = new RequestChannel($client);
        $channel->setChannel('test');

        $worker = new Worker;
        $channel->register($worker);
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterNoCallback() {
        $client = new Client;

        $channel = new RequestChannel($client);
        $channel->setCallback(function($request) { 
            return $request->getUrl(); 
        });
        
        $worker = new Worker;
        $channel->register($worker);
    }
}