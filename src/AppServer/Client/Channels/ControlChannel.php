<?php
namespace AppServer\Client\Channels;
use AppServer\Client\Channel;

class ControlChannel extends Channel {
    protected $channel = 'control';

    public function process(\GearmanJob $job) {
        return $this->client->getJournal()->getJson();
    }
}
