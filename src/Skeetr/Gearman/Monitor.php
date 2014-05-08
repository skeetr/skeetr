<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Gearman;

class Monitor
{
    protected $timeout = 10;
    protected $servers = array();
    protected $streams = array();

    protected $errno;
    protected $error;

    public function getServers() { return $this->servers; }
    public function addServer($host = '127.0.0.1', $port = 4730)
    {
        if ( !$host ) throw new \InvalidArgumentException('Invalid hostname');
        if ( (int) $port == 0 ) throw new \InvalidArgumentException('Invalid port');

        $this->servers[] = sprintf('%s:%d', $host, (int) $port);
        $this->servers = array_unique($this->servers);
    }

    public function getTimeout() { return $this->timeout; }
    public function setTimeout($timeout)
    {
        if ( (int) $timeout == 0 ) throw new \InvalidArgumentException('Invalid timeout');

        $this->timeout = (int) $timeout;
    }

    protected function getConnection($server)
    {
        if ( !isset($this->stream[$server]) ) {
            $this->stream[$server] = @stream_socket_client(
                $server, $this->errno, $this->error, $this->timeout
            );

            if (!$this->stream[$server]) {
                throw new \RuntimeException(sprintf(
                    'Unable to connect to %s (%s)',
                    $server, $this->error
                ));
            }
        }

        return $this->stream[$server];
    }

    protected function sendCommand($command, $multiline, $servers = null)
    {
        if ( !$servers ) $servers = &$this->servers;

        $output = array();
        foreach ($servers as $server) {
            $stream = $this->getConnection($server);
            $result = stream_socket_sendto($stream, $command . "\n");
            if ( !$multiline ) $data = $this->readCommandSingleLine($stream);
            else $data = $this->readCommandMultiLine($stream);

            $output[$server] = $data;
        }

        return $output;
    }

    protected function readCommandSingleLine($stream)
    {
        $result = stream_socket_recvfrom($stream, 9024);
        $this->checkForError($result);

        return $result;
    }

    protected function readCommandMultiLine($stream)
    {
        $result = '';
        while (!feof($stream)) {
            $data = fgets($stream, 4096);
            $this->checkForError($data);
            if ($data == ".\n") break;
            else $result .= $data;
        }

        return $result;
    }

    protected function checkForError($data)
    {
        $data = trim($data);
        if (preg_match('/^ERR/', $data)) {
            list($cmd, $code, $msg) = explode(' ', $data);
            throw new \RuntimeException(urldecode($msg));
        }
    }

    public function getStatus($empty = false)
    {
        $request = $this->sendCommand('status', true);

        $output = array();
        foreach ($request as $server => $data) {
            $status = array();
            foreach ( explode("\n", $data) as $line ) {
                $line = explode("\t", $line);
                if ( count($line) != 4 ) continue;

                $function = array(
                    'queued' => (int) $line[1],
                    'running' => (int) $line[2],
                    'workers' => (int) $line[3]
                );

                if ( !$empty && array_sum($function) == 0 ) continue;
                $output[$server][$line[0]] = $function;
            }
        }

        return $output;
    }

    public function getWorkers($empty = false)
    {
        $request = $this->sendCommand('workers', true);

        $output = array();
        foreach ($request as $server => $data) {
            foreach ( explode("\n", $data) as $line ) {
                if ( strlen($line) < 10 ) continue;

                list($info, $functions) = explode(':', $line);
                list($fd, $ip, $id)     = explode(' ', $info);

                $worker = array(
                    'fd' => $fd,
                    'ip' => $ip,
                    'id' => $id,
                    'functions' => array()
                );

                if ( trim($functions) ) $worker['functions'] = explode(' ', trim($functions));
                else if ( !$empty ) continue;

                $output[$server][] = $worker;
            }
        }

        return $output;
    }

    public function getVersion()
    {
        $request = $this->sendCommand('version', false);

        $output = array();
        foreach ($request as $server => $data) {
            list($ok, $version) = explode(' ', trim($data));
            $output[$server] = $version;
        }

        return $output;
    }
}
