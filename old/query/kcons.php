<?php
require_once("../Connections/iconn.php");
require_once("../guard.php");

$now=date("d.m.Y H:i");
$txt=trim($_POST['txt']);

if($_POST['action']=="cons") {
$mysqli->query("update keys_k set k_out=NOW(), k_cons_to='$txt', k_cons_from='$_user' where k_id='{$_POST['id']}'") or die(mysqli_error());
echo json_encode(array("id"=>$_POST['id'], "stato"=>"<span style='color:#c00'>In consegna a ".$_POST['txt']."<br />Da ".$_user." il ".$now."</span>"));
}
if($_POST['action']=="rie") {
$sql=$mysqli->query("select * from keys_k where k_id='{$_POST['id']}'") or die(mysqli_error());
$row=mysqli_fetch_assoc($sql);
$txt=$row['k_cons_to'];

$mysqli->query("update keys_k set k_out=0, k_cons_to='', k_cons_from='' where k_id='{$_POST['id']}'") or die(mysqli_error());
echo json_encode(array("id"=>$_POST['id'], "txt"=>$txt, "stato"=>"in Carico"));
}

?> 