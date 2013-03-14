<?php

# Crea el cliente gearman
$gmc= new GearmanClient();

# Añade el servidor de trabajos por defecto
$gmc->addServer('front-1.iunait.es', 4730);

# Establece la llamada de retorno para cuando el trabajo ha terminado
$gmc->setCompleteCallback("reverse_complete");

# Añade tareas, una de ellas de baja prioridad
$task= $gmc->addTask("control", "Hello World!", null, "1");
$task= $gmc->addTaskLow("control", "!dlroW olleH", null, "2");
$task= $gmc->addTask("control", "Hello World!", null, "3");

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
