<?php
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

    /** @var LoggerInterface */
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

    public function setLevel($level)
    {
        if ( $level === null ) $level = error_reporting();
        $this->level = $level;
    }

    public static function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    /**
     * @throws \ErrorException When error_reporting returns error
     */
    public function handle($level, $message, $file, $line, $context)
    {
        if ( $this->level === 0 ) return false;

        if ( $level & (E_USER_DEPRECATED | E_DEPRECATED) ) {
            if (null !== self::$logger) {
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
            $warning = isset($this->levels[$level]) ? $this->levels[$level] : $level;

            $string = sprintf('%s: %s in %s line %d', $warning, $message, $file, $line);
            self::$logger->warning($warning);

            throw new \ErrorException($string, 0, $level, $file, $line);
        }

        return false;
    }

    public function handleFatal()
    {
        if (null === $error = error_get_last()) {
            return;
        }

        unset($this->reservedMemory);
        $type = $error['type'];
        if (0 === $this->level || !in_array($type, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE))) {
            return;
        }

        // get current exception handler
        $exceptionHandler = set_exception_handler(function() {});
        restore_exception_handler();

        if ( 
            is_array($exceptionHandler) && 
            $exceptionHandler[0] instanceof Error
        ) { 
            $level = isset($this->levels[$type]) ? $this->levels[$type] : $type;
            $message = sprintf('%s: %s in %s line %d', $level, $error['message'], $error['file'], $error['line']);
            throw new \ErrorException($message, 0, $type, $error['file'], $error['line']);
           // $exceptionHandler[0]->handle($exception);
        }
    }

    /**
     * Sends a Response for the given Exception.
     *
     * @param \Exception $exception An \Exception instance
     */
    public function handleException(\Exception $exception)
    {
               self::$logger->warning($exception->getMessage());

        //$this->createResponse($exception)->send();
    }

}
