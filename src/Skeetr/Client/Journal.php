<?php
namespace Skeetr\Client;

class Journal {
    private $idleSince;
    private $data = array(
        'works' => 0, 'avg' => 0, 'errors' => 0, 'timeouts' => 0, 'idle' => 0,
        'disconnected' => 0, 'since' => 0, 'error' => null
    );

    public function __construct() {
        $this->data['since'] = time();
        $this->idleSince = $this->data['since'];
    }

    public function addSuccess($time) {
        $total = ( $this->data['avg'] * $this->data['works'] )  + $time;
        $this->data['works']++;
        $this->data['avg'] = $total/$this->data['works'];

        return $this->data['works'];
    }

    public function addError($msg) {
        $this->data['error'] = $msg;
        return ++$this->data['errors'];
    }

    public function addTimeout() {
        return ++$this->data['timeouts'];
    }

    public function addLostConnection($time) {
        return $this->data['disconnected'] += $time;
    }

    public function addIdle() {
        $time = microtime(true) - $this->idleSince; 
        $this->idleSince = microtime(true);

        return $this->data['idle'] += $time;
    }

    public function getWorks() { return $this->data['works']; }
    public function getAvgTime() { return $this->data['avg']; }
    public function getErrors() { return $this->data['errors']; }
    public function getLastError() { return $this->data['error']; }
    public function getTimeouts() { return $this->data['timeouts']; }
    public function getLostConnection() { return $this->data['disconnected']; }
    public function getIdle() { return $this->data['idle']; }

    public function getData() { return $this->data; }
    public function getJson() { return json_encode($this->data); }
}