<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Client\Handler;
use Psr\Log\LoggerInterface;

/**
 * @author Máximo Cuadros <maximo@yunait.com>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Error
{
    const TYPE_DEPRECATION = -100;

    private $levels = array(
        E_WARNING           => 'Warning',
        E_NOTICE            => 'Notice',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        E_STRICT            => 'Runtime Notice',
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
        E_DEPRECATED        => 'Deprecated',
        E_USER_DEPRECATED   => 'User Deprecated',
        E_ERROR             => 'Error',
        E_CORE_ERROR        => 'Core Error',
        E_COMPILE_ERROR     => 'Compile Error',
        E_PARSE             => 'Parse',
    );

    private $level;
    private $reservedMemory;
    private static $logger;

    /**
     * Register the error handler. The level at which the conversion to Exception is done 
     * (null to use the error_reporting() value and 0 to disable)
     *
     * @param integer $level 
     * @return The registered error handler
     */
    public static function register($level = null)
    {
        $handler = new static();
        $handler->setLevel($level);

        ini_set('display_errors', 0);
        set_exception_handler(array($handler, 'handleException'));
        set_error_handler(array($handler, 'handle'));
        register_shutdown_function(array($handler, 'handleFatal'));
        $handler->reservedMemory = str_repeat('x', 10240);

        return $handler;
    }

    /**
     * Return the logger instance
     *
     * @return LoggerInterface 
     */
    public static function getLogger()
    {
        return self::$logger;
    }

    /**
     * Configure the logger used by the error handler
     *
     * @param LoggerInterface $logger 
     */
    public static function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    /**
     * Returns the minimum error level
     *
     * @return integer 
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Configure the minimum error level
     *
     * @param integer $level 
     */
    public function setLevel($level)
    {
        if ( $level === null ) $level = error_reporting();
        $this->level = $level;
    }

    /**
     * This method will be used by set_error_handler
     *
     * @throws \ErrorException When error_reporting returns error
     */
    public function handle($level, $message, $file, $line, $context)
    {
        if ( $this->level === 0 ) return true;

        if ( $level & (E_USER_DEPRECATED | E_DEPRECATED) ) {
            if (self::$logger !== null) {
                if ( version_compare(PHP_VERSION, '5.4', '<') ) {
                    $stack = array_slice(debug_backtrace(false), 0, 10);
                } else {
                    $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
                }
                
                self::$logger->warning($message, array(
                    'type' => self::TYPE_DEPRECATION, 
                    'stack' => $stack
                ));
            }

            return true;
        }

        if ( error_reporting() & $level && $this->level & $level ) {
            $this->generateErrorException($level, $message, $file, $line, false);
        }

        return true;
    }

    /**
     * This method will be used by register_shutdown_function
     */
    public function handleFatal()
    {
        $error = error_get_last();
        if ($error === null) return;

        unset($this->reservedMemory);
        $type = $error['type'];
        if ( $this->level === 0 || !in_array($type, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE))) {
            return;
        }

        // get current exception handler
        $exceptionHandler = set_exception_handler(function() {});
        restore_exception_handler();

        if ( 
            is_array($exceptionHandler) && 
            $exceptionHandler[0] instanceof Error
        ) { 
            $this->generateErrorException($type, $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * This handler will get all the Excpetion, out of a try/catch
     *
     * @param \Exception $exception
     */
    public function handleException(\Exception $exception)
    {
        self::printException($exception);
    }

    private function generateErrorException($type, $message, $file, $line, $fatal = true)
    {
        $level = isset($this->levels[$type]) ? $this->levels[$type] : $type;
        $text = sprintf('%s: %s in %s line %d', $level, $message, $file, $line);

        $exception = new \ErrorException($text, 0, $type, $file, $line);
        self::printException($exception, $fatal);
        return $exception;
    }


    /**
     * This handler will get all the Excpetion, out of a try/catch
     *
     * @param \Exception $exception
     */
    public static function printException(\Exception $exception, $fatal = true)
    {
        self::$logger->error($exception->getMessage(), get_object_vars($exception));
        if ( $fatal ) self::$logger->notice('Execution will stop due to the previous exception');
    }
}