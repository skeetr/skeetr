<?php
namespace Skeetr\Debugger\Watchers;
use Skeetr\Debugger\Watcher;

class RecursiveIteratorWatcher extends Watcher {
    public function watch() {
        foreach($this->files as $file => $time) {
            if ( $this->watchFile($file, $time) ) return true;
        }

        return false;
    }

    protected function watchFile($filename, $previous) {
        $current = filemtime($filename);
        if ( $current > $previous ) {
            $this->files[$filename] = $current;
            return true ;
        }

        return false;
    }

    protected function trackFile($filename) {
        $this->files[$filename] = filemtime($filename);
    }
}