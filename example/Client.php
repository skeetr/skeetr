<?php

/* Creamos el objeto */
$gmclient= new GearmanClient();
$gmclient->addServer('front-1.iunait.es', 4730);

/* Ejecuta un cliente "reverse" */
$job_handle = $gmclient->doBackground("control", "this is a test");

if ($gmclient->returnCode() != GEARMAN_SUCCESS)
{
  echo "bad return code\n";
  exit;
}

$done = false;
do
{
   sleep(3);
   $stat = $gmclient->jobStatus($job_handle);
   if (!$stat[0]) // the job is known so it is not done
      $done = true;
   echo "Running: " . ($stat[1] ? "true" : "false") . ", numerator: " . $stat[2] . ", denomintor: " . $stat[3] . "\n";
}
while(!$done);

echo "done!\n";

?>