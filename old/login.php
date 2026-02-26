<?php
session_start();

ini_set('session.bug_compat_warn', 0);
ini_set('session.bug_compat_42', 0);

require_once('Connections/iconn.php');
//require_once('funzioni.php');
date_default_timezone_set("Europe/Rome");

//include("include/jobs.php");

// legge cookie
if($_COOKIE['AGNauth']) {
$chiavi=explode("|", $_COOKIE['AGNauth']);
$_user=$chiavi[0];
$_pwd=$chiavi[1];
$_hid=$chiavi[2];
$_level=$chiavi[3];

$login=$mysqli->query("select * from keys_users where us_name='$_user' and us_pwd='$_pwd' and hid='$_hid'");
$mysqli->query("update keys_users set us_last_login=NOW() where us_name='$_user' and us_pwd='$_pwd' and hid='$_hid'");
if(mysqli_num_rows($login)==1) { header("location:index.php"); die; }
}

// submit form
if($_POST['Submit']) {
	setcookie("AGNauth","",-3600,"/");
	$_user=$_POST['user'];
	$_pwd=md5($_POST['pwd']);
	//HID UNICO
	$_hid=10;

$sql=$mysqli->query("select * from keys_users where hid='$_hid' and us_name='$_user' and us_pwd='$_pwd'");
if(mysqli_num_rows($sql)==0) $error="Login o password non validi - Riprovare!";
else {
$_res=mysqli_fetch_assoc($sql); $_level=$_res['us_level'];
setcookie("AGNauth","$_user|$_pwd|$_hid|$_level",time()+3600,"/");
$mysqli->query("update keys_users set us_last_login=NOW() where us_name='$_user' and us_pwd='$_pwd' and hid='$_hid'");
header("location:index.php");
}
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Login</title>
<link href="bb.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery/jquery-2.1.1.min.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" >
$("#main_pr").corner();
</script>
<script type="text/javascript">
$(document).ready(function() {
	
$("#msg:hidden:first").fadeIn("slow");

$('#id, #user, #pwd').focusin(function() {
$("#msg").fadeOut("slow");
});
});
</script>
</head>

<body>
<div id="container">
<div id="main">
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
<p>&nbsp;</p>
<div id="main_pr">
  <form id="form1" name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <p align="center" class="testo22" style="color:#D00;"><br />
      Registro gestione chiavi</p>
    <p>&nbsp;</p>
    <table width="80%" border="0" align="center" cellpadding="5" cellspacing="0">
      <tr>
        <td width="20%" rowspan="4" align="right"><img src="images/login.png" width="128" height="128" alt="Login" /></td>
        <td style="display:none;" width="20%" height="70" align="right"><h4>ID:</h4></td>
        <td style="display:none;" width="60%" align="left"><input name="id" type="text" class="txtfld2" id="id" value="10" />
          <input name="ricorda" type="checkbox" id="ricorda" value="si" <?php if(isset($_COOKIE['AGNid'])) echo 'checked="checked"'; ?> /> 
          <span class="testo18">Ricorda</span></td>
      </tr>
      <tr>
        <td height="50" align="right"><h4>User:</h4></td>
        <td align="left"><input name="user" type="text" class="txtfld2" style="width:305px;" id="user" autocomplete="off" /></td>
      </tr>
      <tr>
        <td height="50"  align="right"><h4>Password:</h4></td>
        <td align="left"><input name="pwd" type="password" class="txtfld2" id="pwd" style=" width:300px;" autocomplete="off" /></td>
      </tr>
      <tr>
        <td height="50"  align="center">&nbsp;</td>
        <td align="left"><input name="Submit" type="submit" class="b1" id="Submit" value="Login" /></td>
        </tr>
      <tr>
        <td height="60" colspan="3"  align="center" class="avviso1"><div id="msg" style="display:none"><?php echo $error; ?> </div></td>
      </tr>
</table>
  </form>
  <p style="text-align:center" class="testo16"><a href="us_rec_pwd.php">Dimenticato la password?</a></p>
  </div>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
</div>
</div>
</body>
</html>