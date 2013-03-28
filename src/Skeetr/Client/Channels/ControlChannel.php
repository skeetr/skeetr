<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client\Channels;
use Skeetr\Client;
use Skeetr\Client\Channel;

class ControlChannel extends Channel 
{
    /**
     * Constructor
     *
     * @param Client $client
     * @param string $basename printf format, used in ControlChannel::autoSetChannel
     */
    public function __construct(Client $client, $basename) 
    {
        parent::__construct($client);
        $this->autoSetChannel($basename);
    }

    /**
     * Set the channel name based on the basename given in the constructor 
     *
     * @param string $channel
     */
    private function autoSetChannel($basename) 
    {
        $channel = sprintf($basename, $this->client->getId());
        $this->setChannel($channel);
    }

    /**
     * {@inheritdoc}
     */
    public function process(\GearmanJob $job) 
    {
        return $this->client->getJournal()->getJson();
    }
}
