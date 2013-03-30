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
use Skeetr\Client\HTTP\Response;

use Skeetr\Gearman\Worker;
use Skeetr\Runtime\Manager;

class RequestChannel extends Channel
{
    private $callback;
    private $runtime = true;

    /**
     * Set the callback, this will get called when a job is submitted 
     *
     * @param callback $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    public function enableRuntime($status = null)
    {
        if ( $status !== null ) $this->runtime = $status;
        return $this->runtime;
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
        $response = new Response;

        $result = call_user_func($this->callback, $request, $response);
        if ( $result ) $response->setBody($result);
        $this->prepareResponse($response);

        $this->client->notify(Worker::STATUS_SUCCESS, microtime(true) - $start);
        return $response->toJSON();
    }

    /**
     * Set the headers and the response code to a given $response, class is reset after.
     *
     * @param Response $response
     * @return boolean
     */ 
    private function prepareResponse(Response $response)
    {
        if ( !Manager::loaded() ) return false;
        session_write_close();

        $values = Manager::values();
        if ( isset($values['header']) ) {
            if ( isset($values['header']['code']) ) {
                $response->setResponseCode($values['header']['code']);
            } 

            if ( isset($values['header']['list']) ) {
                foreach( $values['header']['list'] as $headers ) {
                    foreach ($headers as $header) {
                        $response->addHeader($header, true);
                    }
                }
            }
        }

        Manager::reset();
    }
}
