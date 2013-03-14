<?php
namespace AppServer\Client\Channels;
use AppServer\Client;
use AppServer\Client\Channel;

class ControlChannel extends Channel {
    public function __construct(Client $client, $basename) {
        parent::__construct($client);
        $this->autoSetChannel($basename);
    }

    private function autoSetChannel($basename) {
        $channel = sprintf($basename, $this->client->getId());
        $this->setChannel($channel);
    }

    public function process(\GearmanJob $job) {
        return $this->client->getJournal()->getJson();
    }
}
