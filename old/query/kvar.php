<?php

require_once("../Connections/iconn.php");
require_once("../guard.php");
$cat=trim($_POST['cat']); $name=trim($_POST['name']);

$sql=$mysqli->query("select * from keys_k where k_id='{$_POST['id']}'") or die(mysqli_error());
$row=mysqli_fetch_assoc($sql);
$old_cat=$row['k_cat'];
$old_name=$row['k_name'];

$mysqli->query("update keys_k set k_cat='$cat', k_name='$name' where k_id='{$_POST['id']}'");

echo json_encode(array("cat"=>$old_cat,"name"=>$old_name));
?> 