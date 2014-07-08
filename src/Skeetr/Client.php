<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr;

use Psr\Log\LoggerInterface;
use Skeetr\Gearman\Worker;
use Skeetr\Client\Socket;
use Skeetr\Client\Journal;
use Skeetr\Client\Channel;
use Skeetr\Client\RPC;
use Skeetr\Runtime\Manager;
use RuntimeException;

class Client
{
    protected $memoryLimit = 67108864; //64mb
    protected $interationsLimit;
    protected $methods = [];
    protected $logger;
    protected $socket;
    protected $callback;
    protected $loop;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;

        //TODO: Optional
        Manager::auto();
    }

    /**
     * Sets the callback, this callback generate the result of the request
     *
     * @param  string $channelName
     * @return self   The current Process instance
     */
    public function setCallback(Callable $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Sets the memory limit, when this limit is reached the loops ends
     *
     * @param  integer $bytes
     * @return self    The current Process instance
     */
    public function setMemoryLimit($bytes)
    {
        $this->memoryLimit = $bytes;

        return $this;
    }

    /**
     * Sets the iterations limit, when this limit is reached the loops ends
     *
     * @param  integer $times
     * @return self    The current Process instance
     */
    public function setInterationsLimit($times)
    {
        $this->interationsLimit = $times;

        return $this;
    }

    /**
     * Sets the logger instance
     *
     * @param  LoggerInterface $logger
     * @return self            The current Process instance
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Sets the methods
     *
     * @param  Array $methods
     * @return self  The current Process instance
     */
    public function setMethods(Array $methods)
    {
        $this->methods = [];
        foreach ($methods as $method) {
            $this->addMethod($method);
        }

        return $this;
    }

    /**
     * Adds a method instance
     *
     * @param  RPC\Method $method
     * @return self       The current Process instance
     */
    public function addMethod(RPC\Method $method)
    {
        $tmp = explode('\\', get_class($method));
        $this->methods[end($tmp)] = $method;
    }

    /**
     * Returns the callback
     *
     * @return callable
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
     * Returns the logger instance
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Returns all the methods
     *
     * @return Array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Returns the method with the given name
     *
     * @param  string $name
     * @return RPC\Method
     */
    public function getMethod($name)
    {
        var_dump(array_keys($this->methods));

        if (!isset($this->methods[$name])) {
            throw new RuntimeException(sprintf(
                'Unable to find RPC method %s', $name
            ));
        }

        return $this->methods[$name];
    }

    /**
     * Wait for and perform requests
     */
    public function work()
    {
        $this->initializeMethods();
        $this->initializeSocket();
        $this->loop();
    }

    private function initializeMethods()
    {
        $process = new RPC\Method\Process($this->callback);
        $this->addMethod($process);
    }

    private function initializeSocket()
    {
        $this->socket->connect();
        $this->log('notice', 'Waiting for client...');
        $this->socket->waitForConnection();
        $this->log('notice', 'Waiting for job...');
    }

    protected function loop()
    {
        $this->loop = true;
        while ($this->loop) {
            $request = RPC\Request::fromJSON($this->socket->get());

            $result = $this->getMethod($request->getMethod())->execute($request);

            $response = new RPC\Response();
            $response->setId($request->getId());
            $response->setResult($result);

            $this->socket->put($response->toJSON());

            //$this->checkStatus();*/
            var_dump('request');
        }
    }

    /**
     * Stops the main loop and exits the work function
     *
     * @param string $message optional
     * @param boolean
     */
    public function shutdown($message = null)
    {
        if (!$this->loop) {
            return false;
        }

        if ($message) {
            $this->log('notice', sprintf('Loop stopped: %s', $message));
        }

        $this->loop = false;

        return true;
    }

    protected function checkStatus()
    {
        if ($this->memoryLimit) {
            $memory = memory_get_usage(true);
            if ($memory >= $this->memoryLimit) {
                return $this->shutdown(sprintf(
                    'Memory limit reached %d bytes (%d bytes limit)',
                    $memory, $this->memoryLimit
                ));
            }
        }

        if ($this->interationsLimit) {
            $iterations = $this->journal->getWorks();
            if ($iterations >= $this->interationsLimit) {
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
        var_dump($type);

        if (!$this->logger) {
            return false;
        }

        return $this->logger->$type($message);
    }
}
