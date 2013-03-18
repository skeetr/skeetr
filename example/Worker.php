<?php
use Skeetr\Client;
use Skeetr\Gearman\Worker;
use Skeetr\HTTP\Response;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require __DIR__ . '/../vendor/autoload.php';

$worker = new Worker();
$worker->addServer('front-1.iunait.es', 4730);

$logger = new Logger('debugger');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

$client = new Client($logger, $worker);
$client->setCallback(function($request) { 
    var_dump('www: ' . $request->getUrl());
    $response = new Response();
    $response->setBody('test');

    return (string)$response;
});

$client->work();