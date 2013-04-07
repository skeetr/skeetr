<?php
namespace Skeetr\Mocks;
use Skeetr\Tests\TestCase;
use Skeetr\Client as ClientMocked;
use Skeetr\Mocks\Gearman\Worker;
use Skeetr\Mocks\Logger;

class Client extends ClientMocked {
    public function __construct(Worker $worker = null)
    {
        return parent::__construct(new Worker);
    }

    public function getTime()
    { 
        return $this->time;
    }
    
    public function notify($status, $value = null)
    { 
        $this->time = $value;
    }
}