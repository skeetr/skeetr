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

}