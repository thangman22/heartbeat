<?php
require 'Heartbeat.php';
$heartbeat = new HeartBeat("127.0.0.1", "6379", 0, "update_engagement_pantip");
while (true) {
    $heartbeat->pulseWorker();
    echo ".";
    sleep(10);
}
 ?>
