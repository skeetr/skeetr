<?php
namespace Skeetr\Tests;

use Skeetr\Runtime\Manager;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Manager::reset();

        $this->logs = array();

        $this->logger = $this->getMock(
            'Psr\Log\AbstractLogger', array('log'), array(), '', false
        );

        $this->logger
            ->expects($this->any())
            ->method('log')
            ->will($this->returnCallback(array($this, 'mockLog')));
    }

    public function mockLog($level, $message, $context)
    {
        $this->logs[] = array(
            'level' => $level,
            'message' => $message,
            'context' => $context,
        );
    }

    public static function getResource($path)
    {
        return file_get_contents(__DIR__ . '/../../Resources/' . $path);
    }
}
