<?php
namespace Skeetr\Tests;
use Skeetr\Client\Channels\RequestChannel;

use Skeetr\Mocks\Client;
use Skeetr\Mocks\GearmanJob;
use Skeetr\Mocks\Gearman\Worker;

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