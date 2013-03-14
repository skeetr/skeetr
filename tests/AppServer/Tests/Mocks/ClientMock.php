<?php
namespace AppServer\Tests\Mocks;
use AppServer\Client;

class ClientMock extends Client {
    public function __construct(\GearmanWorker $worker = null, $channel = 'default') {
        $worker = new GearmanWorkerMock();
        return parent::__construct($worker, $channel);
    }

    public function getTime() { 
        return $this->time;
    }
    
    public function notifyExecution($secs) { 
        $this->time = $secs;
    }
}