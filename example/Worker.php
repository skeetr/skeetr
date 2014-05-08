<?php
use Skeetr\Debugger;
use Skeetr\Client;
use Skeetr\Client\Socket;
use Skeetr\Client\Handler\Error;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


require __DIR__ . '/../vendor/autoload.php';

$logger = new Logger('debugger');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::NOTICE));

Error::register();
Error::setLogger($logger);

/*
$debugger = new Debugger($logger);
$debugger->run();
*/

$socket = new Socket('/tmp/foo.sock');

$client = new Client($socket);
$client->setLogger($logger);
$client->setCallback(function($request, $response) use ($logger) {
    session_start();
    if ( !isset($_SESSION['count']) ) $_SESSION['count'] = 0;
    $_SESSION['count']++;

   // throw new \Exception("Error Processing Request", 1);

    header('Foo: boo');
    setcookie('foo', 'bar');
    setcookie('baz', 'qux');

    return 'test' . $_SESSION['count'];
});

$client->work();
