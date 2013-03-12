<?php
namespace AppServer\Client;
use AppServer\HTTP\Request;

interface Channel {
    public function register(\GearmanWorker $worker);
    public function process(\GearmanJob $job);
}