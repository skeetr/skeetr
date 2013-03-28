<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client;
use Skeetr\Client;
use Skeetr\HTTP\Request;
use Skeetr\Gearman\Worker;

abstract class Channel
{
    protected $client;
    protected $channel;
    protected $timeout;

    /**
     * Constructor
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Set the channel name, this will be used as $function_name in the GearmanWorker::addFunction
     *
     * @param string $channel
     */
    public function setChannel($channel) 
    {
        $this->channel = $channel;
    }

    /**
     * Set the timeout, this will be used as timeout in the GearmanWorker::addFunction
     *
     * @param integer $timeout An interval of time in seconds
     */
    public function setTimeout($timeout) 
    {
        $this->timeout = (int)$timeout;
    }

    /**
     * Get channel name
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Get timeout
     *
     * @return integer
     */
    public function getTimeout()
    { 
        return $this->timeout;
    }

    /**
     * Registers this channel in the given worker
     * 
     * @param Worker $worker
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function register(Worker $worker)
    {
        return $worker->addFunction(
            $this->getChannel(), 
            array($this, 'process'), 
            $this, 
            $this->getTimeout()
        );
    } 

    /**
     * Gets called when a job for the registered channel name is submitted
     * 
     * @param GearmanJob $job
     * @return mixed
     */
    abstract public function process(\GearmanJob $job);
}