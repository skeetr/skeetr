<?php
namespace Skeetr;
use Skeetr\Debugger\Watchers\RecursiveIteratorWatcher;
use Symfony\Component\Process\Process;

class Debugger {
    const FORK_MODE = 'SK_FORK_MODE';
    const CONTROL_MODE = 'SK_CONTROL_MODE';

    private $process;

    public function run() {
        if ( $this->getMode() == self::FORK_MODE ) return;

        $this->watcher = new RecursiveIteratorWatcher();
        $this->watcher->addPattern(__DIR__ . '/../../../*.php');
        $this->watcher->track();

        $this->process = new Process($this->getCommand());
        $this->process->start();

        $this->process();
    }

    public function getMode() {
        if ( isset($_SERVER[self::FORK_MODE]) ) return self::FORK_MODE;
        return self::CONTROL_MODE;
    }

    public function isRunning() {
        if ( !$this->process ) return false;
        return $this->process->isRunning();
    }

    protected function process() {
        while ($this->isRunning()) { 
            if ( $error = $this->getIncrementalErrorOutput() ) {
                var_dump('----ERROR----', $error, '----ERROR----');
            } else if ( $output = $this->getIncrementalOutput() ) {
                var_dump($output);
            }

            if ( $this->watcher->watch() ) {
                $this->restart();
                break;
            }
        }
    }

    protected function restart() {
        $this->process->stop();

        $this->process = $this->process->restart();
        $this->process();
    }

    protected function getIncrementalOutput() {
        return $this->process->getIncrementalOutput();
    }

    protected function getIncrementalErrorOutput() {
        $error = '';
        while ( $string = $this->process->getIncrementalErrorOutput() ) {
            $error .= $string;
        }

        return $error;
    }

    protected function getCommand() {            
        $command = $_SERVER['_'];
        $path = $_SERVER['PWD'];
        $args = $_SERVER['argv'];

        if ( substr($args[0], 0, 1) != DIRECTORY_SEPARATOR ) {
            $args[0] = $path . DIRECTORY_SEPARATOR . $args[0];
        }

        if ( $command != $_SERVER['argv'][0] ) {
            array_unshift($args, $command);
        }

        return sprintf(
            '%s=1 %s',
            self::FORK_MODE, implode(' ',  $args)
        );
    }
}