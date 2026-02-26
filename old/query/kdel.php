<?php
require_once("../Connections/iconn.php");
require_once("../guard.php");

$sql=$mysqli->query("select * from keys_k where k_id='{$_POST['id']}'") or die(mysqli_error());
$row=mysqli_fetch_assoc($sql);
$cat=$row['k_cat'];
$name=$row['k_name'];
if($row['k_out']=="0000-00-00 00:00:00") {
	if($_POST['r']!='r') { 
	$mysqli->query("update keys_k set k_canc='c' where k_id='{$_POST['id']}'") or die(mysqli_error());
	$action="Cancellata";
	}else{ 
	$mysqli->query("update keys_k set k_canc='' where k_id='{$_POST['id']}'") or die(mysqli_error());
	$action="Ripristinata";
	}
}else	$error=1;
	
	
echo json_encode(array("error"=>$error, "cat"=>$cat,"name"=>$name,"action"=>$action));
?> 