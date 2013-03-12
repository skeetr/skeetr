<?php
namespace AppServer\Client;
use AppServer\Client\Channels\ControlChannel;
use AppServer\Client\Channels\RequestChannel;

class Client {
    protected $sleepOnError = 5;
    protected $memoryLimit = 67108864; //64mb
    protected $worksLimit;

    protected $channel;
    protected $gearman;
    protected $callback;
    protected $stats;

    protected $waitingSince;

    public function __construct($channel = 'default') {
        $this->stats = new Stats();

        $this->channel = $channel;
        
        $this->gearman = new \GearmanWorker();
        $this->gearman->addOptions(GEARMAN_WORKER_NON_BLOCKING); 
    }

    public function addServer($host = '127.0.0.1', $port = 4730) {
        $this->gearman->addServer($host, $port);
    }

    public function getCallback() { return $this->callback; }
    public function setCallback($callback) {
        if ( !is_callable($callback) ) {
            throw new \InvalidArgumentException(
                'Invalid argument $callback, must be callabe.');
        }

        $this->callback = $callback;
    }

    public function getMemoryLimit() { return $this->memoryLimit; }
    public function setMemoryLimit($bytes) {
        $this->memoryLimit = $bytes;
    }

    public function getSleepTimeOnError() { return $this->sleepTimeOnError; }
    public function setSleepTimeOnError($secs) {
        $this->sleepTimeOnError = $secs;
    }

    public function getWorksLimit() { return $this->worksLimit; }
    public function setWorksLimit($times) {
        $this->worksLimit = $times;
    }

    public function work() {
        print "Registering channels...\n";
        $this->register();

        print "Waiting for job...\n";
        $this->loop();
    }
        
    private function loop() {
        while (1) {
            $this->gearman->work();
            $this->evaluate($this->gearman->returnCode());

            print (string)$this->stats . PHP_EOL;
        }
    }

    private function evaluate($code) {
        var_dump($code);
        switch ($code) {
            case GEARMAN_IO_WAIT:
            case GEARMAN_NO_JOBS:
                @$this->gearman->wait();
                if ( $this->gearman->returnCode() == GEARMAN_NO_ACTIVE_FDS ) {
                    $this->lostConnection();
                }
                
                continue;
            case GEARMAN_TIMEOUT: 
                $this->timeout(); 
                break;
            case GEARMAN_SUCCESS:
                $this->idle();
                break;
            default: 
                $this->error(); 
                break;
        }  
    }

    private function error() { 
        $msg = $this->gearman->error();
        printf('Gearman error: "%s"' . PHP_EOL, $msg);
        $this->stats->addError($msg); 
    }

    private function timeout() { 
        printf('Timeout' . PHP_EOL);
        $this->stats->addTimeout(); 
    } 
    
    private function success() { $this->stats->addSuccess(); }

    private function idle() { 
        printf('Waiting for next job ...' . PHP_EOL);

        if ( $this->waitingSince ) {
            $idle = $this->stats->addIdle(time() - $this->waitingSince); 
        }

        $this->waitingSince = time();
    }

    private function lostConnection() {
        printf('Connection lost, waiting %s seconds ...' . PHP_EOL, $this->sleepTimeOnError);

        $this->stats->addLostConnection($this->sleepTimeOnError);
        sleep($this->sleepTimeOnError); 
        $this->idle();
    }

    private function register() {
        $control = new ControlChannel();
        $control->register($this->gearman);

        $request = new RequestChannel();
        $request->setChannel($this->channel);
        $request->setCallback($this->callback);
        $request->register($this->gearman);
    }

    public function __destruct() {
        $this->gearman->unregisterAll();
    }
}