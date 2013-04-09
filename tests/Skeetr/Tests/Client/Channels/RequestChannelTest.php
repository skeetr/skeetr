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
    public function testProcess()
    {
        $client = new Client;

        $channel = new RequestChannel($client);
        $channel->setCallback(function($request) { 
            header('Foo: bar', true, 404);
            return $request->getRequestUrl(); 
        });

        $job = new GearmanJob;
        $job->setWorkload(TestCase::getResource('Request/GET'));

        $json = $channel->process($job);
        $data = json_decode($json, true);

        $this->assertSame('/filename.html', $data['body']);
        $this->assertSame('bar', $data['headers']['Foo']);
        $this->assertSame(404, $data['responseCode']);

        $this->assertTrue(0 < $client->getTime());
    }

    public function testProcessWrongCallback()
    {
        $client = new Client;

        $channel = new RequestChannel($client);
        $channel->setCallback(function($request) { 
            throw new \Exception("Error Processing Request", 1);
        });

        $job = new GearmanJob;
        $job->setWorkload(TestCase::getResource('Request/GET'));

        $json = $channel->process($job);
        $data = json_decode($json, true);

        $this->assertSame('Error: Error Processing Request', $data['body']);
        $this->assertSame(500, $data['responseCode']);

    }

    public function testRegister()
    {
        $client = new Client;
        $channel = new RequestChannel($client);
        $channel->setChannel('test');
        $channel->setTimeout(3);
        $channel->setCallback(function($request) { });

        $worker = new Worker;
        $this->assertTrue($channel->register($worker));
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