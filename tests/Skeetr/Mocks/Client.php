<?php
namespace Skeetr\Mocks;
use Skeetr\Tests\TestCase;
use Skeetr\Client as ClientMocked;
use Skeetr\Mocks\Gearman\Worker;
use Skeetr\Mocks\Logger;

class Client extends ClientMocked {
    public function __construct(Worker $worker = null, $channel = 'default') {
        $worker = new Worker();
        $logger = new Logger();
        return parent::__construct($logger, $worker);
    }

    public function getTime() { 
        return $this->time;
    }
    
    public function notifyExecution($secs) { 
        $this->time = $secs;
    }
}