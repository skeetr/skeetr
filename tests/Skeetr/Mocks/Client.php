<?php
namespace Skeetr\Mocks;
use Skeetr\Client as ClientMocked;
use Skeetr\Mocks\Gearman\Worker;

class Client extends ClientMocked {
    public function __construct(Worker $worker = null, $channel = 'default') {
        $worker = new Worker();
        return parent::__construct($worker, $channel);
    }

    public function getTime() { 
        return $this->time;
    }
    
    public function notifyExecution($secs) { 
        $this->time = $secs;
    }
}