<?php
namespace AppServer\Mocks;
use AppServer\Client as ClientMocked;
use AppServer\Mocks\Gearman\Worker;

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