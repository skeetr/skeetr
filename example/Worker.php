<?php
use AppServer\Client\Client;
use AppServer\HTTP\Response;

require __DIR__ . '/../vendor/autoload.php';

$worker = new Client('default');
$worker->addServer('front-1.iunait.es', 4730);
$worker->setCallback(function($request) { 
    var_dump('www: ' . $request->getUrl());
    $response = new Response();
    $response->setBody('test');

    return (string)$response;
});

$worker->work();