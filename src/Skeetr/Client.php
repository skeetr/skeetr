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
    protected $interationsLimit;

    protected $logger;
    protected $channel = 'default';
    protected $gearman;
    protected $callback;
    protected $journal;
    protected $loop;

    protected $waitingSince;

    public function __construct(Worker $worker)
    {
        $this->id = uniqid(null, true);
        $this->journal = new Journal();
        $this->worker = $worker;

        //TODO: Optional
        Manager::auto();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setWorker(Worker $worker)
    {
        $this->worker = $worker;
    }

    public function setJournal(Journal $journal)
    {
        $this->journal = $journal;
    }

    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    public function setCallback($callback)
    {
        if ( !is_callable($callback) ) {
            throw new \InvalidArgumentException(
                'Invalid argument $callback, must be callabe.'
            );
        }

        $this->callback = $callback;
    }

    public function setRetry($secs)
    {
        $this->retry = $secs;
    }

    public function setMemoryLimit($bytes)
    {
        $this->memoryLimit = $bytes;
    }

    public function setInterationsLimit($times)
    {
        $this->interationsLimit = $times;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getId()
    { 
        return $this->id;
    }

    public function getWorker()
    {
        return $this->worker; 
    }

    public function getJournal()
    {
        return $this->journal;
    }

    public function getChannel()
    { 
        return $this->channel;
    }

    public function getCallback()
    { 
        return $this->callback;
    }

    public function getRetry()
    { 
        return $this->retry; 
    }

    public function getMemoryLimit()
    { 
        return $this->memoryLimit; 
    }

    public function getInterationsLimit()
    { 
        return $this->interationsLimit;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function work()
    {
        $this->log('notice', 'Registering channels...');
        $this->register();

        $this->log('notice', 'Waiting for job...');
        $this->loop();
    }

    public function notify($status, $value = null)
    {
        switch ($status) {
            case Worker::STATUS_SUCCESS: return $this->success((float)$value);
            case Worker::STATUS_DISCONNECTED: return $this->disconnected();
            case Worker::STATUS_TIMEOUT: return $this->timeout();
            case Worker::STATUS_ERROR: return $this->error();
            case Worker::STATUS_IDLE: return $this->idle();
        }      
    }

    public function shutdown($message = null)
    {
        if ( !$this->loop ) return false;

        if ($message) $this->log('notice', sprintf('Loop stopped: %s', $message));
        
        $this->loop = false;
        return true;
    }
        
    protected function loop()
    {
        $this->loop = true;
        while ($this->loop) {
            if ( $status = $this->worker->work() ) {
                $this->notify($status);
            }

            $this->checkStatus();
        }
    }

    protected function error()
    { 
        $msg = $this->worker->lastError();
        $this->log('notice', sprintf('Gearman error: "%s"', $msg));

        $this->journal->addError($msg); 
    }

    protected function timeout()
    { 
        $this->log('notice', 'Timeout');
        $this->journal->addTimeout(); 
    } 
    
    protected function success($secs)
    {
        $this->log('notice', sprintf('Executed job in %f sec(s)', $secs));
        $this->journal->addSuccess($secs); 
    }

    protected function idle()
    {
        $this->log('debug', 'Waiting for job...');
        $this->journal->addIdle(); 
    }

    protected function disconnected()
    {
        $this->log('notice', sprintf('Connection lost, waiting %s seconds ...', $this->sleepTimeOnError));

        $this->journal->addLostConnection($this->disconnectedSleep);
        sleep($this->disconnectedSleep); 
        $this->idle();
    }

    protected function register()
    {
        $control = new ControlChannel($this, 'control_%s');
        $control->register($this->worker);

        $request = new RequestChannel($this);
        $request->setChannel($this->channel);
        $request->setCallback($this->callback);
        $request->register($this->worker);
    }

    protected function checkStatus()
    {
        if ( $this->memoryLimit ) {
            $memory = memory_get_usage(true);
            if ( $memory >= $this->memoryLimit ) {
                return $this->shutdown(sprintf(
                    'Memory limit reached %d bytes (%d bytes limit)',
                    $memory, $this->memoryLimit
                ));
            }
        }

        if ( $this->interationsLimit ) {
            $iterations = $this->journal->getWorks();
            if ( $iterations > $this->interationsLimit ) {
                return $this->shutdown(sprintf(
                    'Iteration limit reached %d times (%d limit)',
                    $iterations, $this->interationsLimit
                ));
            }
        }
    }

    protected function log($type, $message)
    {
        if ( !$this->logger ) return false;
        return $this->logger->$type($message);
    }
}