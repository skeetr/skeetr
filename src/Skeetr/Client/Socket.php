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

use RuntimeException;

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
    }

    private function throwExceptionIfPathExists()
    {
        if (file_exists($this->path)) {
            throw new RuntimeException(sprintf(
                'Temporary socket (%s) already exists.',
                $this->path
            ));
        }
    }

    private function buildServerAndListen()
    {
        $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if (!socket_bind($this->socket, $this->path)) {
            throw new RuntimeException(sprintf('Unable to bind to %s', $this->path));
        }

        if (!socket_listen($this->socket, 0)) {
            throw new RuntimeException('Unable to listen on socket');
        }
    }

    public function waitForConnection()
    {
        $this->currentClient = socket_accept($this->socket);
        if (!$this->currentClient) {
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
