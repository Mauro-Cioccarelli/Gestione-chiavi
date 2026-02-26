<?php
require_once("../Connections/iconn.php");
require_once("../guard.php");
$cat=trim($_POST['cat']); $name=trim($_POST['name']);

$mysqli->query("insert into keys_k (k_cat, k_name) values('{$_POST['cat']}','{$_POST['name']}')");
$id=$mysqli->insert_id;



echo json_encode(array("id"=>$id, "error"=>$error));

?> 