<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client\Channels;
use Skeetr\Client\Channel;
use Skeetr\Client\HTTP\Request;
use Skeetr\Gearman\Worker;

class RequestChannel extends Channel
{
    private $callback;

    /**
     * Set the callback, this will get called when a job is submitted 
     *
     * @param callback $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Worker $worker)
    {
        if ( !strlen($this->channel) ) {
            throw new \InvalidArgumentException('Invalid channel name.');
        }
        
        if ( !is_callable($this->callback) ) {
            throw new \InvalidArgumentException('Invalid callback.');
        }

        return parent::register($worker);
    } 
    
    /**
     * {@inheritdoc}
     */
    public function process(\GearmanJob $job) 
    {
        $start = microtime(true);

        $request = Request::fromJSON($job->workload());
        $result = call_user_func($this->callback, $request);

        $this->client->notify(Worker::STATUS_SUCCESS, microtime(true) - $start);
        return $result;
    }
}
