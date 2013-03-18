<?php
use Skeetr\Gearman\Monitor;

require __DIR__ . '/../vendor/autoload.php';

$monitor = new Monitor;
$monitor->addServer('front-1.iunait.es', 4730);

$status = $monitor->getStatus();
foreach ($status as $host => $functions) {
    foreach ($functions as $function => $count) {
        if ( preg_match('/control_(.*)/', $function) !== 0 ) {
            if ( $count['workers'] ) $control[] = $function;
        }
    }
}


# Crea el cliente gearman
$gmc= new GearmanClient();
# Añade el servidor de trabajos por defecto
$gmc->addServer('front-1.iunait.es', 4730);

# Establece la llamada de retorno para cuando el trabajo ha terminado
$gmc->setCompleteCallback("reverse_complete");

foreach ($control as $function) {
    $task= $gmc->addTask($function, "Hello World!", null, "1");
}
# Añade tareas, una de ellas de baja prioridad

if (! $gmc->runTasks())
{   
    echo "ERROR " . $gmc->error() . "\n";
    exit;
}
echo "DONE\n";

function reverse_complete($task)
{
    echo "COMPLETE: " . $task->unique() . ", " . $task->data() . "\n";
}
