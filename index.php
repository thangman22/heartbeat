<?php
require 'Heartbeat.php';
require 'inc/timeago.inc.php';
$heartbeat = new HeartBeat();
$heartBeatKey = $heartbeat->listHeartBeat("lifetime");
date_default_timezone_set("Asia/Bangkok");
if (isset($_GET['delete_worker'])) {
    $heartbeat->deletePulseWorker($_GET['delete_worker']);
    echo "<script>window.location = 'index.php';</script>";
}

if (isset($_GET['delete_process'])) {
    $heartbeat->deleteProcess($_GET['delete_process']);
    echo "<script>window.location = 'index.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Hearth Beat Monitor</title>
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="asset/bootstrap.min.css">
        <!-- Latest compiled and minified JavaScript -->
        <script src="asset/jquery-1.11.3.min.js"></script>
        <script src="asset/bootstrap.min.js"></script>
        <script>
        </script>
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <table class="table">
                        <tr>
                            <th>Bot name</th>
                            <th>Process time</th>
                            <th>Last signal</th>
                            <th>Value</th>
                            <th>Worker</th>
                            <th>Status</th>
                            <th>Delete</th>
                        </tr>
                        <?php
if (isset($heartBeatKey['lifetime'])):
    ksort($heartBeatKey['lifetime']);
    foreach ($heartBeatKey['lifetime'] as $key => $item):
        $keyName = $heartbeat->extractKeyname($key);
        $lifestatus = $heartbeat->getLifeStatus($keyName, 600);
        $fullDate = date("Y/m/d H:i:s", $lifestatus['lifeDetail']['lastPulse']);
        $timeAgo = new TimeAgo();
        $counter = $heartbeat->listCounter($keyName);
        ?>
		                        <tr>
		                            <td><?php echo $keyName;?> <small class="text-muted">[<?php echo $heartbeat->getPid($keyName); ?>]</small></td>
		                            <td>
		                                <small class="text-muted">
		                                <?php echo "<strong>Start at</strong> " . date("Y/m/d H:i:s", $lifestatus['lifeDetail']['startTime']);?>
		                                <?php
        if (isset($lifestatus['lifeDetail']['endTime'])) {
            echo "<br><strong>End at</strong> " . date("Y/m/d H:i:s", $lifestatus['lifeDetail']['endTime']);
        }
        ?>
		                            </small></td>
		                            <td><?php echo $fullDate?> <small class="text-muted">(<?php echo number_format($lifestatus['diffSec']);?> secounds ago)</small></td>
		                            <td>
		                                <?php
        foreach ($counter as $key_counter => $item_counter) {
            echo "<strong>" . $key_counter . "</strong> " . number_format($item_counter) . "<br>";
        }
        ?>
		                            </td>
		                            <td><?php $worker = $heartbeat->checkWorkerStatus($keyName, 600);
        if ($worker['count_worker'] > 0) {
            echo "<strong>" . ($worker['count_worker'] - $worker['count_die']) . "</strong> Worker(s) running <strong>" . $worker['count_die'] . "</strong> Die.";
        } else {
            echo "No worker running.";
        }

        if (isset($worker['detail'])) {
            echo "<hr>";
            echo "<ul class='list-unstyled'>";
            foreach ($worker['detail'] as $key_worker => $value_worker) {
                echo "<li><strong>" . $key_worker . "</strong></li>";
                echo "<ul>";
                foreach ($value_worker as $key_pid => $value_pid) {

                    echo "<li>" . $key_pid . " ";
                    if ($value_pid['status'] == "die") {
                        echo '<span class="label label-danger">Die</span>';
                    } else {
                        echo '<span class="label label-primary">Running</span>';
                    }
                    echo "</li>";
                }
                echo "</ul>";
            }
            echo "</ul>";
        }

        ?></td>
		                            <td>
		                                <?php if ($lifestatus['lifeStatus'] == "alive") {
            echo '<span class="label label-primary">Running</span>';
        } elseif ($lifestatus['lifeStatus'] == "die") {
        echo '<span class="label label-danger">Die</span>';
    } else {
        echo '<span class="label label-success">Complete</span>';
    }
    ?>
	                            </td>
	                            <td class="text-center"><a class="btn btn-default  btn-xs" style="margin-bottom:5px;" href="?delete_process=<?php echo $keyName;?>">Delete Process monitor</a><br><a class="btn btn-default  btn-xs" href="?delete_worker=<?php echo $keyName;?>">Delete Worker monitor</a></td>
	                        </tr>
	                        <?php endforeach;endif;?>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
