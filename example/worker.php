<?php
require '../Heartbeat.php';
$heartbeat = new HeartBeat("example_test");
while (true) {
    $heartbeat->pulseWorker();
    echo ".";
    sleep(10);
}
$heartbeat->workerEnd();
 ?>



