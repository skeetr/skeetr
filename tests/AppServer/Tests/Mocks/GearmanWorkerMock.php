<?php
namespace AppServer\Tests\Mocks;
use AppServer\Client\Channel;

class GearmanWorkerMock extends \GearmanWorker {
    public function addServer($host = null, $port = null) {
        return array($host, $port);
    }

    public function addFunction ($functionName, $function, $data = null, $timeout = null) {
        if ( $functionName != 'test' ) return false;
        if ( !is_callable($function) ) return false;
        if ( !$data instanceOf Channel ) return false;
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