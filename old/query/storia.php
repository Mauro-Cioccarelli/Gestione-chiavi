<?php
require_once("../Connections/iconn.php");
require_once("../guard.php");

$now=date("Y-m-d H:i:s");

$sql=$mysqli->query("insert into keys_log (log_action, log_user, log_date, log_kid) values('{$_POST['action']}','$_user','$now','{$_POST['kid']}')") or die(mysqli_error());
?> 