<?php
namespace Skeetr\Debugger;

abstract class Watcher {
    protected $patterns = array();

    public function addPattern($pattern) { $this->patterns[] = $pattern; }
    public function addPatterns(array $patterns) {
        foreach($patterns as $pattern) $this->addPattern($pattern);
    }

    public function getPatterns() {
        return $this->patterns;
    }

    abstract public function watch();

    public function track() {
        foreach($this->patterns as $pattern) {
            $this->trackPattern($pattern);
        }
    }

    protected function trackPattern($pattern) {
        $directory = pathinfo($pattern, PATHINFO_DIRNAME);
        $extension = pathinfo($pattern, PATHINFO_EXTENSION);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        while( $iterator->valid() ) {
            $file = $iterator->key();
            $iterator->next();
            if ( $iterator->isDot() ) continue;
                
            if ( $extension == pathinfo($file, PATHINFO_EXTENSION) ) {
                $this->trackFile($file);
            }
        } 
        
    }

    abstract protected function trackFile($filename);
}