<?php
namespace AppServer;
use AppServer\Client\Journal;
use AppServer\Client\Channel;
use AppServer\Client\Channels\ControlChannel;
use AppServer\Client\Channels\RequestChannel;

class Client {
    protected $registered;
    protected $sleepOnError = 5;
    protected $memoryLimit = 67108864; //64mb
    protected $worksLimit;

    protected $channel;
    protected $gearman;
    protected $callback;
    protected $journal;

    protected $waitingSince;

    public function __construct(\GearmanWorker $worker, $channel = 'default', $id = null) {
        $this->journal = new Journal();
        $this->channel = $channel;

        if ( !$id ) $id = uniqid(null, true);
        $this->setId($id);
        
        $this->gearman = $worker;
        $this->gearman->addOptions(GEARMAN_WORKER_NON_BLOCKING); 
    }

    public function getId() { return $this->id; }
    public function setId($id) {
        $this->id = $id;
    }

    public function addServer($host = '127.0.0.1', $port = 4730) {
        return $this->gearman->addServer($host, $port);
    }

    public function getCallback() { return $this->callback; }
    public function setCallback($callback) {
        if ( !is_callable($callback) ) {
            throw new \InvalidArgumentException(
                'Invalid argument $callback, must be callabe.');
        }

        $this->callback = $callback;
    }

    public function getSleepTimeOnError() { return $this->sleepTimeOnError; }
    public function setSleepTimeOnError($secs) {
        $this->sleepTimeOnError = $secs;
    }

    public function getMemoryLimit() { return $this->memoryLimit; }
    public function setMemoryLimit($bytes) {
        $this->memoryLimit = $bytes;
    }

    public function getWorksLimit() { return $this->worksLimit; }
    public function setWorksLimit($times) {
        $this->worksLimit = $times;
    }

    public function getGearman() { return $this->gearman; }
    public function getChannel() { return $this->channel; }
    public function getJournal() { return $this->journal; }

    public function work() {
        print "Registering channels...\n";
        $this->register();

        print "Waiting for job...\n";
        $this->loop();
    }

    public function notifyExecution($secs) {
        $this->success($secs);
    }
        
    protected function loop() {
        while (1) {
            $this->gearman->work();
            $this->evaluate($this->gearman->returnCode());

            print $this->journal->getJson() . PHP_EOL;
        }
    }

    protected function evaluate($code) {
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

    protected function error() { 
        $msg = $this->gearman->error();
        printf('Gearman error: "%s"' . PHP_EOL, $msg);
        $this->journal->addError($msg); 
    }

    protected function timeout() { 
        printf('Timeout' . PHP_EOL);
        $this->journal->addTimeout(); 
    } 
    
    protected function success($secs) {
        printf('Executed job in %f sec(s)' . PHP_EOL, $secs);
        $this->journal->addSuccess($secs); 
    }

    protected function idle() { 
        printf('Waiting for next job ...' . PHP_EOL);

        if ( $this->waitingSince ) {
            $idle = $this->journal->addIdle(microtime(true) - $this->waitingSince); 
        }

        $this->waitingSince = microtime(true);
    }

    protected function lostConnection() {
        printf('Connection lost, waiting %s seconds ...' . PHP_EOL, $this->sleepTimeOnError);

        $this->journal->addLostConnection($this->sleepTimeOnError);
        sleep($this->sleepTimeOnError); 
        $this->idle();
    }

    protected function register() {
        $control = new ControlChannel($this, 'control_%s');
        $control->register($this->gearman);

        $request = new RequestChannel($this);
        $request->setChannel($this->channel);
        $request->setCallback($this->callback);
        $request->register($this->gearman);

        $this->registered = true;
    }

    public function __destruct() {
        if ( !$this->registered ) return;
        $this->gearman->unregisterAll();
    }
}