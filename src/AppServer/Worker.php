<?php
namespace AppServer;
use AppServer\HTTP\Request;

class Worker {
    protected $channel;
    protected $gearman;
    protected $callback;

    public function __construct($channel = 'default') {
        $this->channel = $channel;
        $this->gearman = new \GearmanWorker();

        $worker = $this;
        $this->gearman->addFunction($this->channel, function($job) use ($worker) {
            return $this->process($job);
        });
    }

    public function addServer($host = '127.0.0.1', $port = 4730) {
        $this->gearman->addServer($host, $port);
    }

    public function setFunction($callback) {
        if ( !is_callable($callback) ) {
            throw new \InvalidArgumentException(
                'Invalid argument $callback must be callabe.');
        }

        $this->callback = $callback;
    }

    public function work() {
        print "Waiting for job...\n";
        while ( 
            $this->gearman->work() ||
            $this->gearman->returnCode() == GEARMAN_IO_WAIT ||
            $this->gearman->returnCode() == GEARMAN_NO_JOBS
        ) {
            if ($this->gearman->returnCode() == GEARMAN_SUCCESS) continue;
            echo "Esperando al siguiente trabajo...\n";
            if ( !$this->gearman->wait() )  { 
                if ($this->gearman->returnCode() == GEARMAN_NO_ACTIVE_FDS)  { 
                    echo "No estamos conectados a ningún servidor..\n"; 
                    sleep(5); 
                    continue; 
                } 
                break; 
            } 

            echo "Error en el trabajador: " . $this->gearman->error() . "\n";
        }
    }

    private function process(\GearmanJob $job) {
        $request = new Request(trim($job->workload()));
        
        return call_user_func($this->callback, $request);
    }
}