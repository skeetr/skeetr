<?php
use Skeetr\Client;
use Skeetr\Gearman\Worker;
use Skeetr\HTTP\Response;

require __DIR__ . '/../vendor/autoload.php';

$worker = new Worker();

$client = new Client($worker, 'default');
$client->addServer('front-1.iunait.es', 4730);
$client->setCallback(function($request) { 
    var_dump('www: ' . $request->getUrl());
    $response = new Response();
    $response->setBody('test');

    return (string)$response;
});

$client->work();