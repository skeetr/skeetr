<?php
use Skeetr\Client;
use Skeetr\Gearman\Worker;
use Skeetr\HTTP\Response;
use Skeetr\Overrides\Session;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require __DIR__ . '/../vendor/autoload.php';

$session = new Session();
$session->register();

$worker = new Worker();
$worker->addServer('front-1.iunait.es', 4730);

$logger = new Logger('debugger');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

$client = new Client($logger, $worker);
$client->setCallback(function($request) use ($logger) { 
    
    var_dump('www: ' . $request->getUrl());
    $response = new Response();
    $response->setBody('test');

    return (string)$response;
});

header_register_callback(function() {
    header_remove('Trst');
    header_remove("Content-type");
});


$client->work();