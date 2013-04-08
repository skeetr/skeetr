<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Tests\Client\Handler;
use Skeetr\Tests\TestCase;
use Skeetr\Client\Handler\Error;

class ErrorTest extends TestCase
{
    public function testSetLoggerAndGetChannel()
    {
        Error::setLogger($this->logger);
        $this->assertSame($this->logger, Error::getLogger());
    }

    public function testSetLevelAndGetLevel()
    {
        $error = new Error;
        $error->setLevel(E_NOTICE);
        $this->assertSame(E_NOTICE, $error->getLevel());
    }

    public function testHandleDisabled()
    {
        $error = new Error;
        $error->setLevel(0);
        $this->assertTrue($error->handle(E_NOTICE, 'Foo', __FILE__, __LINE__, array()));
    }

    public function testHandleDeprecated()
    {
        Error::setLogger($this->logger);
        $error = new Error;

        $this->assertTrue($error->handle(E_USER_DEPRECATED, 'Foo', __FILE__, __LINE__, array()));
        $this->assertSame('warning', $this->logs[0]['level']);
    }
    
    public function testHandle()
    {
        Error::setLogger($this->logger);
        $error = new Error;
        $error->setLevel(E_ERROR);

        $this->assertTrue($error->handle(E_ERROR, 'Foo', __FILE__, __LINE__, array()));
        $this->assertSame('error', $this->logs[0]['level']);
    }

    public function testHandleException()
    {
        Error::setLogger($this->logger);
        $error = new Error;
        $error->setLevel(E_ERROR);
        $error->handleException(new \Exception('Foo'));
        
        $this->assertSame('error', $this->logs[0]['level']);
    }
}