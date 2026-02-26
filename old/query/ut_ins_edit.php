<?php
require_once('../Connections/iconn.php');
require_once("../guard.php");
date_default_timezone_set("Europe/Rome");

$sql=$mysqli->query("Select us_id from keys_users where us_level='2' and hid='$hid'");
$n_adm=mysqli_num_rows($sql);
$adm_=mysqli_fetch_array($mysql); $adm_id=$adm_[0]; 

// Inserimento
if($_POST['action']=="ins") {
	$pwd_chiaro=rand(10000,99999);
	$pwd=md5($pwd_chiaro);
	$mysqli->query("insert into keys_users(us_name, us_level, us_pwd,us_pw_cng, us_email,hid) values('{$_POST['nome']}', '{$_POST['level']}', '$pwd', '1', '{$_POST['email']}','$hid')");
	// qui invio email
	echo json_encode(array("pwd"=>$pwd_chiaro));
	}


// Modifica
if($_POST['action']=="edit") {
	if($n_adm==1 && $adm_id==$_POST['id']) $st_level=2; else $st_level=$_POST['level'];
	if($n_adm< 1) $st_level=2;
	$mysqli->query("update keys_users set us_name='{$_POST['nome']}', us_level='$st_level', us_email='{$_POST['email']}' where hid='$hid' and us_id='{$_POST['id']}'");
	$lev_des=array("", "Utente", "Amministratore"); 
	echo json_encode(array("level"=>$lev_des[$st_level]));
}


// Elimina
if($_POST['action']=="del") {
if(!($n_adm==1 && $adm_id==$_POST['id'])) 
$mysqli->query("delete from keys_users where hid='$hid' and us_id='{$_POST['id']}'");
}

If($_POST['action']=="pwc") {
if($_POST['id'] >0 && $_POST['stato']=="off") $mysqli->query("update keys_users set us_pw_cng='1' where hid='$hid' and us_id='{$_POST['id']}'");
if($_POST['id'] >0 && $_POST['stato']=="on") $mysqli->query("update keys_users set us_pw_cng='0' where hid='$hid' and us_id='{$_POST['id']}'");
if($_POST['id']==0) $mysqli->query("update keys_users set us_pw_cng='1' where hid='$hid'");
}
?> 