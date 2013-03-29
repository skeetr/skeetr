<?php
/*
 * This file is part of the Skeetr package.
 *
 * (c) Máximo Cuadros <maximo@yunait.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Skeetr\Debugger\Watchers;
use Skeetr\Debugger\Watcher;

class RecursiveIteratorWatcher extends Watcher {
    protected $files = array();

    public function watch() {
        foreach($this->files as $file => $time) {
            if ( $this->watchFile($file, $time) ) return true;
        }

        return false;
    }

    protected function watchFile($filename, $previous) {
        $current = filemtime($filename);
        //var_dump( $current > $previous,  $current, $previous);

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