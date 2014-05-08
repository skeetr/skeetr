<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client;

class Socket
{
    private $path;
    private $socket;
    private $currentClient;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function connect()
    { 
        $this->throwExceptionIfPathExists();
        $this->buildServerAndListen();
        $this->waitForConnection();
    }

    private function throwExceptionIfPathExists()
    {
        if (file_exists($this->path)) {
            throw new RuntimeException('Temporary socket already exists.');
        }
    }

    private function buildServerAndListen()
    {
        $server = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if (!socket_bind($server, $this->path)) {
            throw new RuntimeException(sprintf('Unable to bind to %s', $this->path));
        }
         
        if (!socket_listen($server, 0)) {
            throw new RuntimeException('Unable to listen on socket');
        }
    }

    private function waitForConnection()
    {
        $this->currentClient = socket_accept($server);
        if (!$this->socket) {
            throw new RuntimeException('Unable to accept connection');
        }
    }

    public function get($maxSize = 1048576)
    {
        return socket_read($this->currentClient, $maxSize, PHP_NORMAL_READ);
    }

    public function put($data)
    {
        return socket_write($this->currentClient, $data);
    }

    public function disconnect()
    {
        socket_close($this->socket);
    }
}
