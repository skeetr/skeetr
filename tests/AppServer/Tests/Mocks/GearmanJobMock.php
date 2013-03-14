<?php
namespace AppServer\Tests\Mocks;
use AppServer\Tests\TestCase;

class GearmanJobMock extends \GearmanJob {
    public function workload() {
        return TestCase::getResource('Request/GET');
    }
}
