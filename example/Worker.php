<?php
use AppServer\Worker;
use AppServer\HTTP\Response;

require __DIR__ . '/../vendor/autoload.php';

$worker = new Worker;
$worker->addServer('front-1.iunait.es', 4730);
$worker->setFunction(function() { 
    $response = new Response();
    $response->setBody('test');

    return (string)$response;
});

$worker->work();