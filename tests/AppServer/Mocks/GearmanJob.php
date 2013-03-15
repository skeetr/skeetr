<?php
namespace AppServer\Mocks;
use AppServer\Tests\TestCase;

class GearmanJob extends \GearmanJob {
    public function workload() {
        return TestCase::getResource('Request/GET');
    }
}
