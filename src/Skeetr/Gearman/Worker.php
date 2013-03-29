<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Gearman;

class Worker {
    const STATUS_SUCCESS = 10;
    const STATUS_IDLE = 20;
    const STATUS_ERROR = 30;
    const STATUS_TIMEOUT = 40;
    const STATUS_DISCONNECTED = 50;

    const CONF_TIMEOUT = 5000;

    protected $registered;
    protected $instance;

    public function __construct() {
        $this->instance = new \GearmanWorker;
        $this->instance->addOptions(GEARMAN_WORKER_NON_BLOCKING);
        $this->instance->setTimeout(self::CONF_TIMEOUT);
    }

    public function getServers() { return $this->servers; }
    public function addServer($host = '127.0.0.1', $port = 4730) {
        if ( !$host ) throw new \InvalidArgumentException('Invalid hostname');
        if ( (int)$port == 0 ) throw new \InvalidArgumentException('Invalid port');

        $this->servers[] = sprintf('%s:%d', $host, (int)$port);
        $this->servers = array_unique($this->servers);

        return $this->instance->addServer($host, $port);
    }

    public function addFunction($function, $callback, &$context, $timeout) {
        $this->registered = true;
        return $this->instance->addFunction($function, $callback, $context, $timeout);
    }

    public function getLastError() {
        return $this->instance->error();
    }

    public function work() {
        $this->instance->work();
        return $this->evaluate($this->instance->returnCode());
    }

    protected function evaluate($code) {
        switch ($code) {
            case GEARMAN_IO_WAIT:
            case GEARMAN_NO_JOBS:
                $this->instance->wait();
                if ( $this->instance->returnCode() == GEARMAN_NO_ACTIVE_FDS ) {
                    return self::STATUS_DISCONNECTED;
                }
                
                return self::STATUS_IDLE;
            case GEARMAN_SUCCESS:
                return self::STATUS_IDLE;
            case GEARMAN_TIMEOUT: 
                return self::STATUS_TIMEOUT;
            default: 
                return self::STATUS_ERROR;
        }  
    }

    public function __destruct() {
        if ( !$this->registered ) return;
        $this->instance->unregisterAll();
    }
}