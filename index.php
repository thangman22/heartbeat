<?php
require 'Heartbeat.php';
$heartbeat = new HeartBeat("127.0.0.1", "6379", 2);
$heartBeatKey = $heartbeat->listAllHeartBeat();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Hearth Beat Monitor</title>
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
        <!-- Optional theme -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
        <!-- Latest compiled and minified JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
            <?php foreach ($heartBeatKey as $item): ?>
                <div class="col-md-1">.col-md-1</div>
            <?php endforeach;?>
            </div>
        </div>
    </body>
</html>
