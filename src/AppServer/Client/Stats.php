<?php
namespace AppServer\Client;

class Stats {
    private $stats = array(
        'works' => 0, 'avg' => 0, 'errors' => 0, 'timeouts' => 0, 'idle' => 0,
        'disconnected' => 0, 'since' => 0, 'error' => null
    );

    public function __construct() {
        $this->stats['since'] = time();
    }

    public function addSuccess($time) {
        $this->stats['works']++;
        $this->stats['avg'] += $time/$this->stats['works'];

        return $this->stats['works'];
    }

    public function addError($msg) {
        $this->stats['error'] = $msg;
        return ++$this->stats['errors'];
    }

    public function addTimeout() {
        return ++$this->stats['timeouts'];
    }

    public function addLostConnection($time) {
        return $this->stats['disconnected'] += $time;
    }

    public function addIdle($time) {
        return $this->stats['idle'] += $time;
    }

    public function getWorks() { return $this->stats['works']; }
    public function getAvgTime() { return $this->stats['avg']; }
    public function getTimeouts() { return $this->stats['timeouts']; }
    public function getErrors() { return $this->stats['errors']; }
    public function getLastError() { return $this->stats['error']; }
    public function getIdle() { return $this->stats['idle']; }

    public function __toString() {
        return json_encode($this->stats);
    }
}