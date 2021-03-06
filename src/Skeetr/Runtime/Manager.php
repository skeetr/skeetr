<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <mcuadros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Runtime;

class Manager
{
    private static $loaded = false;
    private static $registered = array();
    private static $functions = array();

    public static function auto($pattern = null)
    {
        if ( static::$loaded ) return true;

        if ( !$pattern ) $pattern = __DIR__ . '/Overrides/*.php';

        foreach ( glob($pattern) as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $file = str_replace(__DIR__, '', $file);

            $base = substr($file, 0, strlen($file) - strlen($ext) - 1);
            $class = '\\'. __NAMESPACE__ . str_replace(DIRECTORY_SEPARATOR, '\\', $base);
            static::register($class);
        }

        static::$loaded = true;
    }

    public static function register($class)
    {
        if ( static::registered($class) ) {
            throw new \InvalidArgumentException('Override already loaded');
        }

        $reflection = new \ReflectionClass($class);
        if ( !$reflection->isSubclassOf('\Skeetr\Runtime\Override') ) {
            throw new \InvalidArgumentException(
                sprintf('%s not implements OverrideInterface', $class)
            );
        }

        $functions = array();
        foreach ( $reflection->getMethods(\ReflectionMethod::IS_FINAL) as $method) {
            $function = $method->getName();
            if ( !function_exists($function) ) continue;

            $call = static::getCall($class, $function);
            skeetr_override_function($call['function'], $call['args'], $call['code']);

            $functions[$function] = 1;
        }

        if ( count($functions) == 0 ) {
            throw new \InvalidArgumentException(sprintf(
                'The class "%s" not contains any override function',
                $class
            ));
        }

        $class::reset();

        static::$functions = array_merge(static::$functions, $functions);
        static::$registered[$class] = 1;
    }

    public static function overridden($function)
    {
         if ( isset(static::$functions[$function]) ) return true;
        return false;
    }

    public static function registered($class)
    {
        if ( isset(static::$registered[$class]) ) return true;
        return false;
    }

    public static function loaded()
    {
        return static::$loaded;
    }

    public static function reset($class = null)
    {
        if ( !static::$loaded ) return false;

        if ( $class && isset(static::$registered[$class]) ) return $class::reset();
        else if ( $class ) return false;

        foreach(static::$registered as $class => $registered) $class::reset();

        return true;
    }

    public static function values($class = null)
    {
        if ( !static::$loaded ) return false;

        if ( $class && isset(static::$registered[$class]) ) {
            $tmp = explode('\\', $class);
            $key = strtolower(end($tmp));

            return array($key => $class::values());
        } elseif ( $class ) return false;

        $values = array();
        foreach (static::$registered as $class => $registered) {
            $tmp = explode('\\', $class);
            $key = strtolower(end($tmp));
            $values[$key] = $class::values();
        }

        return $values;
    }

    protected static function getCall($class, $method)
    {
        $call = array();
        $args = array();

        foreach (static::readMethod($class, $method) as $arg) {
            $call[] = sprintf('$%s', $arg['name']);
            if ( !isset($arg['default']) ) $args[] = sprintf('$%s', $arg['name']);
            else $args[] = sprintf('$%s = %s', $arg['name'], $arg['default']);
        }

        return array(
            'function' => $method,
            'args' => implode(', ', $args),
            'code' => sprintf('return %s::%s(%s);', $class, $method, implode(', ', $call))
        );
    }

    protected static function readMethod($class, $method)
    {
        $reflection = new \ReflectionClass($class);
        $method = $reflection->getMethod($method);

        $args = array();
        foreach ( $method->getParameters() as $param ) {
            $arg = array();
            $arg['name'] = $param->getName();
            if ( $param->isOptional() ) {
                $arg['default'] = var_export($param->getDefaultValue(), true);
            }

            $args[] = $arg;
        }

        return $args;
    }
}
