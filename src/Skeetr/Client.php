<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr;
use Psr\Log\LoggerInterface;
use Skeetr\Gearman\Worker;
use Skeetr\Client\Journal;
use Skeetr\Client\Channel;
use Skeetr\Client\Channels\ControlChannel;
use Skeetr\Client\Channels\RequestChannel;
use Skeetr\Runtime\Manager;
    
class Client {
    protected $retry = 5;
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

        //TODO: Optional
        Manager::auto();
    }

    public function getWorker() { return $this->worker; }
    public function getJournal() { return $this->journal; }

    public function getChannel() { return $this->channel; }
    public function setChannel($channel) {
        $this->channel = $channel;
    }

    public function getId() { return $this->id; }
    public function setId($id) {
        $this->id = $id;
    }

    public function getCallback() { return $this->callback; }
    public function setCallback($callback) {
        if ( !is_callable($callback) ) {
            throw new \InvalidArgumentException(
                'Invalid argument $callback, must be callabe.'
            );
        }

        $this->callback = $callback;
    }

    public function getRetry() { return $this->retry; }
    public function setRetry($secs) {
        $this->retry = $secs;
    }

    public function getMemoryLimit() { return $this->memoryLimit; }
    public function setMemoryLimit($bytes) {
        $this->memoryLimit = $bytes;
    }

    public function getWorksLimit() { return $this->worksLimit; }
    public function setWorksLimit($times) {
        $this->worksLimit = $times;
    }

    public function work() {
        $this->logger->notice('Registering channels...');
        $this->register();

        $this->logger->notice('Waiting for job...');
        $this->loop();
    }

    public function notify($status, $value = null) {
        switch ($status) {
            case Worker::STATUS_SUCCESS: return $this->success((float)$value);
            case Worker::STATUS_DISCONNECTED: return $this->disconnected();
            case Worker::STATUS_TIMEOUT: return $this->timeout();
            case Worker::STATUS_ERROR: return $this->error();
            case Worker::STATUS_IDLE: return $this->idle();
        }      
    }
        
    protected function loop() {
        while (1) {
            if ( $status = $this->worker->work() ) {
                $this->notify($status);
            }
        }
    }

    protected function error() { 
        $msg = $this->worker->lastError();
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
        $this->logger->debug('Waiting for job...');
        $this->journal->addIdle(); 
    }

    protected function disconnected() {
        $this->logger->notice(sprintf('Connection lost, waiting %s seconds ...', $this->sleepTimeOnError));

        $this->journal->addLostConnection($this->disconnectedSleep);
        sleep($this->disconnectedSleep); 
        $this->idle();
    }

    protected function register() {
        $control = new ControlChannel($this, 'control_%s');
        $control->register($this->worker);

        $request = new RequestChannel($this);
        $request->setChannel($this->channel);
        $request->setCallback($this->callback);
        $request->register($this->worker);
    }
}