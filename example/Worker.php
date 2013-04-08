<?php
use Skeetr\Debugger;
use Skeetr\Client;
use Skeetr\Gearman\Worker;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Skeetr\Client\Handler\Error;

require __DIR__ . '/../vendor/autoload.php';

$logger = new Logger('debugger');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

Error::register();
Error::setLogger($logger);

/*
$debugger = new Debugger($logger);
$debugger->run();
*/

$worker = new Worker();
$worker->addServer('front-1.iunait.es', 4730);

$client = new Client($worker);
$client->setLogger($logger);
$client->setCallback(function($request, $response) use ($logger) { 
    session_start();
    if ( !isset($_SESSION['count']) ) $_SESSION['count'] = 0;
    $_SESSION['count']++;

    throw new \Exception("Error Processing Request", 1);
    

    header('Foo: boo');
    setcookie('foo', 'bar');
    setcookie('baz', 'qux');

    return 'test' . $_SESSION['count'];
});


$client->work();