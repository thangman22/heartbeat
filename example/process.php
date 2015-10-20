<?php
require '../Heartbeat.php';
$heartbeat = new HeartBeat("example_test");
$heartbeat->processStart();
$heartbeat->setCounterValue("updated", 0);
$v = 0;
while (true) {
    $heartbeat->pulse();
    echo ".";
    sleep(10);
    $heartbeat->increaseCounterValue("updated");
    $v++;
    if ($v == 20) {
        $heartbeat->processEnd();
        die();

    }
}
