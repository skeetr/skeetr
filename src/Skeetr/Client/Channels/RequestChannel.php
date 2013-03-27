<?php
namespace Skeetr\Client\Channels;
use Skeetr\Client\Channel;
use Skeetr\HTTP\Request;
use Skeetr\Gearman\Worker;

class RequestChannel extends Channel {
    private $callback;

    public function setCallback($callback) {
        $this->callback = $callback;
    }

    public function register(Worker $worker) {
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

        $request = new Request();
        $request->fromJSON($job->workload());
        
        $result = call_user_func($this->callback, $request);

        $this->client->notify(Worker::STATUS_SUCCESS, microtime(true) - $start);
        return $result;
    }
}
