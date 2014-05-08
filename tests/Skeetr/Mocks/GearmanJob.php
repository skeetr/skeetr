<?php
namespace Skeetr\Mocks;

class GearmanJob extends \GearmanJob
{
    public function setWorkload($workload)
    {
        return $this->workload = $workload;
    }

    public function workload()
    {
        return $this->workload;
    }
}
