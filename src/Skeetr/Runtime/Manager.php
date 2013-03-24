<?php
namespace Skeetr\Runtime;

class Manager {
    static private $registered = array();
    static private $functions = array();

    static public function register($class) {
        if ( self::registered($class) ) {
            throw new \InvalidArgumentException('Override already loaded');
        }

        $reflection = new \ReflectionClass($class);
        if ( !$reflection->implementsInterface('\Skeetr\Runtime\OverrideInterface') ) {
            throw new \InvalidArgumentException(
                sprintf('%s not implements OverrideInterface', $class)
            );
        }

        foreach( $reflection->getMethods(\ReflectionMethod::IS_FINAL) as $method) {
            $function = $method->getName();
            if ( !function_exists($function) ) continue;

            $call = self::getCall($class, $function);
            if ( !skeetr_override_function(
                $call['function'], 
                $call['args'], 
                $call['code']
            )) {
                throw new \RuntimeException(
                    sprintf('Unable to override builtin function %s', $call['function'])
                );
            }

            $functions[$function] = 1;
        }
        
        if ( count($functions) == 0 ) {
            throw new \InvalidArgumentException(
                'This class not contains any override function'
            );
        }

        $class::reset();

        self::$functions = array_merge(self::$functions, $functions);
        self::$registered[$class] = 1;
    }

    static public function overrided($function) {
         if ( isset(self::$functions[$function]) ) return true;
        return false;       
    }

    static public function registered($class) {
        if ( isset(self::$registered[$class]) ) return true;
        return false;
    }

    static public function reset($class = null) {
        if ( $class && isset(self::$registered[$class]) ) return $class::reset();
        else if ( $class ) return false;

        foreach(self::$registered as $class => $registered) $class::reset();
        return true;
    }

    static protected function getCall($class, $method) {
        $call = array();
        $args = array();

        foreach(self::readMethod($class, $method) as $arg) {
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

    static protected function readMethod($class, $method) {
        $reflection = new \ReflectionClass($class);
        $method = $reflection->getMethod($method);

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