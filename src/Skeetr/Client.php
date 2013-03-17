<?php
namespace Skeetr;
use Psr\Log\LoggerInterface;
use Skeetr\Gearman\Worker;
use Skeetr\Client\Journal;
use Skeetr\Client\Channel;
use Skeetr\Client\Channels\ControlChannel;
use Skeetr\Client\Channels\RequestChannel;

class Client {
    protected $registered;
    protected $sleepOnError = 5;
    protected $memoryLimit = 67108864; //64mb
    protected $worksLimit;

    protected $logger;
    protected $channel = 'default';
    protected $gearman;
    protected $callback;
    protected $journal;

    protected $waitingSince;

    public function __construct(LoggerInterface $logger, Worker $worker) {
        $this->id = uniqid(null, true);

        $this->journal = new Journal();
        
        $this->logger = $logger;
        
        $this->worker = $worker;
        $this->worker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
    }

    public function getChannel() { return $this->channel; }
    public function setChannel($channel) {
        $this->channel = $channel;
    }

    public function getId() { return $this->id; }
    public function setId($id) {
        $this->id = $id;
    }

    public function addServer($host = '127.0.0.1', $port = 4730) {
        return $this->worker->addServer($host, $port);
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

    public function getGearman() { return $this->worker; }
    public function getJournal() { return $this->journal; }

    public function work() {
        $this->logger->notice('Registering channels...');
        $this->register();

        $this->logger->notice('Waiting for job...');
        $this->loop();
    }

    public function notifyExecution($secs) {
        $this->success($secs);
    }
        
    protected function loop() {
        while (1) {
            $this->worker->work();
            $this->evaluate($this->worker->returnCode());

            print $this->journal->getJson() . PHP_EOL;
        }
    }

    protected function evaluate($code) {
        //var_dump($code);
        switch ($code) {
            case GEARMAN_IO_WAIT:
            case GEARMAN_NO_JOBS:
                @$this->worker->wait();
                if ( $this->worker->returnCode() == GEARMAN_NO_ACTIVE_FDS ) {
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
        $msg = $this->worker->error();
        $this->logger->notice(sprintf('Gearman error: "%s"', $msg));

        $this->journal->addError($msg); 
    }

    protected function timeout() { 
        $this->logger->notice('Timeout');
        $this->journal->addTimeout(); 
    } 
    
    protected function success($secs) {
        $this->logger->notice(sprintf('Executed job in %f sec(s)', $secs));
        $this->journal->addSuccess($secs); 
    }

    protected function idle() { 
        $this->logger->notice('Waiting for next job...');

        if ( $this->waitingSince ) {
            $idle = $this->journal->addIdle(microtime(true) - $this->waitingSince); 
        }

        $this->waitingSince = microtime(true);
    }

    protected function lostConnection() {
        $this->logger->notice(sprintf('Connection lost, waiting %s seconds ...', $this->sleepTimeOnError));

        $this->journal->addLostConnection($this->sleepTimeOnError);
        sleep($this->sleepTimeOnError); 
        $this->idle();
    }

    protected function register() {
        $control = new ControlChannel($this, 'control_%s');
        $control->register($this->worker);

        $request = new RequestChannel($this);
        $request->setChannel($this->channel);
        $request->setCallback($this->callback);
        $request->register($this->worker);

        $this->registered = true;
    }

    public function __destruct() {
        if ( !$this->registered ) return;
        $this->worker->unregisterAll();
    }
}