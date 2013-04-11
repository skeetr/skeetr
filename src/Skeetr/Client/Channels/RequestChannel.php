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
use Skeetr\Client\Handler\Error;

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

        try {
            $result = $this->runCallback($request, $response);
            $response->setBody($result);
        } catch (\Exception $e) {
            //TODO: Maybe implement something more complex, with better error reporting?
            Error::printException($e, false);

            $response->setBody(sprintf('Error: %s', $e->getMessage()));
            $response->setResponseCode(500);
        }

        $this->prepareResponse($response);

        $this->client->notify(Worker::STATUS_SUCCESS, microtime(true) - $start);
        return $response->toJSON();
    }


    private function runCallback(Request $request, Response $response)
    {
        return call_user_func($this->callback, $request, $response);
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
            if ( isset($values['header']['code']) && $values['header']['code'] ) {
                $response->setResponseCode($values['header']['code']);
            } 

            if ( isset($values['header']['list']) ) {
                foreach( $values['header']['list'] as $headers ) {
                    foreach ($headers as $header) {
                        $response->addHeader($header, false);
                    }
                }
            }
        }

        Manager::reset();
    }
}
