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
use Skeetr\Client\Channels\RequestChannel;

use Skeetr\Mocks\Client;
use Skeetr\Mocks\GearmanJob;
use Skeetr\Mocks\Gearman\Worker;

class RequestChannelTest extends TestCase
{
    /**
     * @covers Skeetr\Client\Channel::process
     * @covers Skeetr\Client\Channels\RequestChannel::process
     */
    public function testProcess()
    {
        $client = new Client;

        $channel = new RequestChannel($client);
        $channel->setCallback(function($request) { 
            return $request->getRequestUrl(); 
        });

        $job = new GearmanJob;
        $this->assertSame('/filename.html', $channel->process($job));
        $this->assertTrue(0 < $client->getTime());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterNoChannel()
    {
        $client = new Client;

        $channel = new RequestChannel($client);
        $channel->setChannel('test');

        $worker = new Worker;
        $channel->register($worker);
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testRegisterNoCallback()
    {
        $client = new Client;

        $channel = new RequestChannel($client);
        $channel->setCallback(function($request) { 
            return $request->getUrl(); 
        });
        
        $worker = new Worker;
        $channel->register($worker);
    }
}