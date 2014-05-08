<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Tests\Client\Handler;

use Skeetr\Tests\TestCase;
use Skeetr\Client\Handler\Error;

class ErrorTest extends TestCase
{
    public function tearDown()
    {
        ini_set('display_errors', 1);
        set_exception_handler(null);
        restore_error_handler();
    }

    public function testRegister()
    {
        Error::register();
        $exceptionHandler = set_exception_handler(function() {});
        $this->assertInstanceOf('Skeetr\Client\Handler\Error', $exceptionHandler[0]);
        $this->assertSame('handleException', $exceptionHandler[1]);

        $errorHandler = set_error_handler(function() {});
        $this->assertInstanceOf('Skeetr\Client\Handler\Error', $errorHandler[0]);
        $this->assertSame('handle', $errorHandler[1]);
    }

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

    public function testHandleFatalError()
    {
        MockErrorError::register();
        MockErrorError::setLogger($this->logger);

        $error = new MockErrorError;
        $error->setLevel(E_ERROR);

        $error->handleFatal();
        $this->assertSame('error', $this->logs[0]['level']);
    }

    public function testHandleFatalWarning()
    {
        MockErrorWarning::register();
        MockErrorWarning::setLogger($this->logger);

        $error = new MockErrorWarning;
        $error->setLevel(E_ERROR);

        $error->handleFatal();
        $this->assertCount(0, $this->logs);
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

class MockErrorError extends Error
{
    protected function getLastError()
    {
        if ( $e = parent::getLastError() ) return $e;
        return array(
            'type' => E_ERROR,
            'message' => 'Foo Bar',
            'file' => '/tmp/foo',
            'line' => 2,
        );
    }
}

class MockErrorWarning extends Error
{
    protected function getLastError()
    {
        if ( $e = parent::getLastError() ) return $e;
        return array(
            'type' => E_WARNING,
            'message' => 'Foo Bar',
            'file' => '/tmp/foo',
            'line' => 2,
        );
    }
}
