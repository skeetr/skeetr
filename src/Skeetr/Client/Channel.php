<?php
namespace Skeetr\Client;
use Skeetr\Client;
use Skeetr\HTTP\Request;

abstract class Channel {
    protected $client;
    protected $channel;
    protected $timeout;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function getChannel() { return $this->channel; }
    public function setChannel($channel) {
        $this->channel = $channel;
    }

    public function getTimeout() { return $this->timeout; }
    public function setTimeout($timeout) {
        $this->timeout = (int)$timeout;
    }

    public function register(\GearmanWorker $worker) {
        return $worker->addFunction(
            $this->channel, array($this, 'process'), $this, $this->timeout
        );
    } 

    abstract public function process(\GearmanJob $job);
}