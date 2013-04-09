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
use Skeetr\Client\Channels\ControlChannel;
use Skeetr\Mocks\Client;
use Skeetr\Mocks\GearmanJob;

class ControlChannelTest extends TestCase
{
    public function testConstruct()
    {
        $client = new Client;

        $channel = new ControlChannel($client, 'foo');
        $this->assertSame('foo', $channel->getChannel());

        $channel = new ControlChannel($client, 'foo_%s');
        $this->assertTrue(strlen($channel->getChannel()) > 6);   
    }

    public function testJournalCommand()
    {
        $client = new Client;

        $channel = new ControlChannel($client, 'foo');

        $job = new GearmanJob;
        $job->setWorkload(json_encode(array('command' => 'journal')));

        $json = $channel->process($job);
        $data = json_decode($json, true);
        $this->assertTrue(isset($data['works']));   
    }

    public function testShutdownCommand()
    {
        $client = new Client;

        $channel = new ControlChannel($client, 'foo');

        $job = new GearmanJob;
        $job->setWorkload(json_encode(array('command' => 'shutdown')));

        $json = $channel->process($job);
        $data = json_decode($json, true);
        $this->assertTrue(isset($data['result']));   
    }

    public function testUnknownCommand()
    {
        $client = new Client;

        $channel = new ControlChannel($client, 'foo');

        $job = new GearmanJob;
        $job->setWorkload(json_encode(array('command' => 'unknown')));

        $json = $channel->process($job);
        $data = json_decode($json, true);

        $this->assertSame('Unknown command.', $data['error']);   
    }


    public function testJournalMalformedRequest()
    {
        $client = new Client;

        $channel = new ControlChannel($client, 'foo');

        $job = new GearmanJob;
        $job->setWorkload(json_encode(array('foo' => 'bar')));

        $json = $channel->process($job);
        $data = json_decode($json, true);
        
        $this->assertSame('Malformed command received.', $data['error']);     
    }
}