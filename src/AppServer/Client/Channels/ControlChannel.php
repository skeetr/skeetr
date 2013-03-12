<?php
namespace AppServer\Client\Channels;
use AppServer\Client\Channel;

class ControlChannel implements Channel {
    private $channels;
    private $timeout;

    public function setTimeout($timeout) {
        $this->timeout = (int)$timeout;
    }

    public function register(\GearmanWorker $worker) {
        $worker->addFunction(
            'control', array($this, 'process'), $this, $this->timeout
        );
    } 

    public function process(\GearmanJob $job) {
        var_dump('nueva peticion control');
    }
}
