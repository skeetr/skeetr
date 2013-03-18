<?php
namespace Skeetr\Mocks\Gearman;
use Skeetr\Gearman\Worker as WorkerMocked;
use Skeetr\Client\Channel;

class Worker extends WorkerMocked {
    public function addServer($host = null, $port = null) {
        return array($host, $port);
    }

    public function addFunction($function, Callable $callback, &$context, $timeout) {
        if ( $function != 'test' ) return false;
        if ( !$context instanceOf Channel ) return false;
        if ( $timeout !== 3 ) return false;
        return true;
    }

    public function returnCode() {
        return GEARMAN_NO_ACTIVE_FDS;
    }

    public function error() {
        return 'mocked error';
    }
}