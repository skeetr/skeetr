<?php
namespace AppServer\Client\Channels;
use AppServer\Client\Channel;
use AppServer\HTTP\Request;

class RequestChannel extends Channel {
    private $callback;
    private $channel;

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

        return parent::register($worker);
    } 

    public function process(\GearmanJob $job) {
        $start = microtime(true);

        $request = new Request($job->workload());
        $result = call_user_func($this->callback, $request);

        $this->client->notifyExecution(microtime(true) - $start);
        return $result;
    }
}
