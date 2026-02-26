<?php
session_start();
ini_set('session.bug_compat_warn', 0);
ini_set('session.bug_compat_42', 0);
date_default_timezone_set("Europe/Rome");
$_now=time();

//HID UNICO
$_hid=10;

$chiavi=explode("|", $_COOKIE['AGNauth']);
$_user=		$chiavi[0];
$_pwd=		$chiavi[1];
$_hid=		$chiavi[2];
$_level=	$chiavi[3];

//HID UNICO
$_hid=10;

$hid=$_hid;

$sql=$mysqli->query("select * from keys_users where us_name='$_user' and us_pwd='$_pwd' and hid='$_hid'");
	if(mysqli_num_rows($sql)<1) {
	$logged=""; unset($logged); 
	session_unset();
	session_destroy();
	setcookie("AGNauth","",time()-3600);
	header("location:login.php");
	die("$_user $_pwd $_hid");
}
setcookie("AGNauth", $_user."|".$_pwd."|".$hid."|".$_level, $_now+3600, '/');



// force password change
$_row=mysqli_fetch_assoc($sql); 
if($_row['us_pw_cng']==1 && basename($_SERVER['PHP_SELF'])!="us_cambio_pwd.php") header("location:us_cambio_pwd.php?force=1"); 

?>