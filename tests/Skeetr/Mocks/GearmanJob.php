<?php
namespace Skeetr\Mocks;
use Skeetr\Tests\TestCase;

class GearmanJob extends \GearmanJob {
    public function workload() {
        return TestCase::getResource('Request/GET');
    }
}
