<?php
namespace Skeetr\Mocks;
use Skeetr\Tests\TestCase;

class GearmanJob extends \GearmanJob {
    public function setWorkload($workload)
    {
        return $this->workload = $workload;
    }
    
    public function workload()
    {
        return $this->workload;
    }
}