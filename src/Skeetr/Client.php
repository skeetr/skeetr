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
    
class Client
{
    protected $memoryLimit = 67108864; //64mb
    protected $interationsLimit;
    protected $sleepTimeOnError = 5;

    protected $channels = array();
    protected $channelName = 'default';
    protected $logger;
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

    /**
     * Sets the client id
     *
     * @param string $id
     * @return self The current Process instance
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Sets the Gearman worker instance
     *
     * @param Worker $worker
     * @return self The current Process instance
     */
    public function setWorker(Worker $worker)
    {
        $this->worker = $worker;
        return $this;
    }

    /**
     * Sets the journal instance
     *
     * @param Journal $journal
     * @return self The current Process instance
     */
    public function setJournal(Journal $journal)
    {
        $this->journal = $journal;
        return $this;
    }

    /**
     * Sets the request channel name, where the request will be attended
     *
     * @param string $channelName
     * @return self The current Process instance
     */
    public function setChannelName($channelName)
    {
        $this->channelName = $channelName;
        return $this;
    }

    /**
     * Sets the callback, this callback generate the result of the request
     *
     * @param string $channelName
     * @return self The current Process instance
     */
    public function setCallback($callback)
    {
        if ( !is_callable($callback) ) {
            throw new \InvalidArgumentException(
                'Invalid argument $callback, must be callabe.'
            );
        }

        $this->callback = $callback;
        return $this;
    }

    /**
     * Sets the memory limit, when this limit is reached the loops ends
     *
     * @param integer $bytes
     * @return self The current Process instance
     */
    public function setMemoryLimit($bytes)
    {
        $this->memoryLimit = $bytes;
        return $this;
    }

    /**
     * Sets the iterations limit, when this limit is reached the loops ends
     *
     * @param integer $times
     * @return self The current Process instance
     */
    public function setInterationsLimit($times)
    {
        $this->interationsLimit = $times;
        return $this;
    }

    /**
     * Sets the number of seconds to wait when the client lost the connection with the server
     *
     * @param integer $secs
     * @return self The current Process instance
     */
    public function setSleepTimeOnError($secs)
    {
        $this->sleepTimeOnError = $secs;
        return $this;
    }

    /**
     * Sets the logger instance
     *
     * @param LoggerInterface $logger
     * @return self The current Process instance
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Returns the client id
     *
     * @return string
     */
    public function getId()
    { 
        return $this->id;
    }

    /**
     * Returns the Gearman worker instance
     *
     * @return Worker
     */
    public function getWorker()
    {
        return $this->worker; 
    }

    /**
     * Returns the journal instance
     *
     * @return Journal
     */
    public function getJournal()
    {
        return $this->journal;
    }

    /**
     * Returns the request channel name
     *
     * @return string
     */
    public function getChannelName()
    { 
        return $this->channelName;
    }

    /**
     * Returns the callback
     *
     * @return callback
     */
    public function getCallback()
    { 
        return $this->callback;
    }

    /**
     * Returns the memory limit
     *
     * @return integer
     */
    public function getMemoryLimit()
    { 
        return $this->memoryLimit; 
    }

    /**
     * Returns the iterations limit
     *
     * @return integer
     */
    public function getInterationsLimit()
    { 
        return $this->interationsLimit;
    }

    /**
     * Returns the number of seconds to wait when the client lost the connection with the server
     *
     * @return integer
     */
    public function getSleepTimeOnError()
    {
        return $this->sleepTimeOnError;
    }

    /**
     * Returns the logger instance
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Wait for and perform requests
     */
    public function work()
    {
        $this->log('notice', 'Registering channels...');
        $this->register();

        $this->log('notice', 'Waiting for job...');
        $this->loop();
    }

    /**
     * Stops the main loop and exits the work function
     *
     * @param string $message optional
     * @param boolean
     */
    public function shutdown($message = null)
    {
        if ( !$this->loop ) return false;

        if ($message) $this->log('notice', sprintf('Loop stopped: %s', $message));
        
        $this->loop = false;
        return true;
    }

    /**
     * Receives a notification from the channels and save it to the journal.
     *
     * @param integer $status Worker::STATUS_* conts
     * @param mixed $value optional
     */
    public function notify($status, $value = null)
    {
        switch ($status) {
            case Worker::STATUS_SUCCESS: return $this->success((float)$value);
            case Worker::STATUS_DISCONNECTED: return $this->disconnected();
            case Worker::STATUS_TIMEOUT: return $this->timeout();
            case Worker::STATUS_ERROR: return $this->error();
            case Worker::STATUS_IDLE: return $this->idle();
            default:
                throw new \UnexpectedValueException(sprintf('Unexpected status: "%"', $status));
        }      
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

        $this->journal->addLostConnection($this->sleepTimeOnError);
        sleep($this->sleepTimeOnError); 
        $this->idle();
    }

    protected function register()
    {
        $control = new ControlChannel($this, 'control_%s');
        $control->register($this->worker);
        $this->channels['control'] = $control;

        $request = new RequestChannel($this);
        $request->setChannel($this->channelName);
        $request->setCallback($this->callback);
        $request->register($this->worker);
        $this->channels['request'] = $request;
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
            if ( $iterations >= $this->interationsLimit ) {
                return $this->shutdown(sprintf(
                    'Iteration limit reached %d times (%d limit)',
                    $iterations, $this->interationsLimit
                ));
            }
        }

        return null;
    }

    protected function log($type, $message)
    {
        if ( !$this->logger ) return false;
        return $this->logger->$type($message);
    }
}