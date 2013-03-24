<?php
namespace Skeetr\Runtime;
use Skeetr\Runtime\OverrideInterface;

class Manager {
    static private $overrides = array();

    static public function load($className) {
        if ( self::loaded($className) ) {
            throw new \RuntimeException('Override already loaded');
        }

        $class = new \ReflectionClass($className);
        if ( !$class->implementsInterface('\Skeetr\Runtime\OverrideInterface') ) {
            throw new \RuntimeException(
                sprintf('%s not implements OverrideInterface', $className)
            );
        }

        foreach( $class->getMethods(\ReflectionMethod::IS_FINAL) as $method) {
            $function = $method->getName();
            if ( !function_exists($function) ) continue;

            $call = self::getCall($className, $function);
            if ( !skeetr_override_function(
                $call['function'], 
                $call['args'], 
                $call['code']
            )) {
                throw new \RuntimeException(
                    sprintf('Unable to ovveride builtin function %s', $call['function'])
                );
            }
        }
        
        self::$overrides[] = $className;
    }

    static public function loaded($className) {
        if ( isset(self::$overrides[$className]) ) return true;
        return false;
    }

    static protected function getCall($className, $method) {
        $call = array();
        $args = array();

        foreach(self::readMethod($className, $method) as $arg) {
            $call[] = sprintf('$%s', $arg['name']);
            if ( !isset($arg['default']) ) $args[] = sprintf('$%s', $arg['name']);
            else $args[] = sprintf('$%s = %s', $arg['name'], $arg['default']);
        }

        return array(
            'function' => $method,
            'args' => implode(', ', $args),
            'code' => sprintf('return %s::%s(%s);', $className, $method, implode(', ', $call))
        );
    }

    static protected function readMethod($className, $method) {
        $class = new \ReflectionClass($className);
        $method = $class->getMethod($method);

        $args = array();
        foreach( $method->getParameters() as $param ) {
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