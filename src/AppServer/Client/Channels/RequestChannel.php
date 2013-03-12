<?php
namespace AppServer\Client\Channels;
use AppServer\Client\Channel;
use AppServer\HTTP\Request;

class RequestChannel implements Channel {
    private $channel;
    private $timeout;
    private $callback;

    public function setChannel($channel) {
        $this->channel = $channel;
    }

    public function setTimeout($timeout) {
        $this->timeout = (int)$timeout;
    }

    public function setCallback($callback) {

        $this->callback = $callback;
    }

    public function register(\GearmanWorker $worker) {
        if ( !strlen($this->channel) ) {
            throw new \InvalidArgumentException('Invalid channel name.');
        }

        if ( !is_callable($this->callback) ) {
            throw new \InvalidArgumentException('Invalid callback.');
        }

        $worker->addFunction(
            $this->channel, array($this, 'process'), $this, $this->timeout
        );
    } 

    public function process(\GearmanJob $job) {
        $request = new Request(trim($job->workload()));
        return call_user_func($this->callback, $request);
    }
}
