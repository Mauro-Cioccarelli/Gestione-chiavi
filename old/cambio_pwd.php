<?php
require_once("Connections/iconn.php");
require_once("guard.php");
date_default_timezone_set("Europe/Rome");

if($_POST['Submit']) {

	$pw=md5($_POST['new_pw1']);
	$mysqli->query("update keys_users set us_old_pwd=us_pwd where hid='$hid' and us_name='{$_POST['user']}'");
	$mysqli->query("update keys_users set us_pwd='$pw', us_pw_cng='0' where hid='$hid' and us_name='{$_POST['user']}'");
	
	setcookie("AGNauth","",1,"/");
	header("location:login.php");
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Cambio password</title>
<link href="bb.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery/jquery-2.1.1.min.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/jquery_md5.js"></script>
<script type="text/javascript">
$("#main_pr").corner();
$("#menu_cont").corner();

//----------------------------
function cambio_pw() {
	var user=$("#user").val();
	var old_pw=$.md5($("#old_pw").val());
	var new_pw1=$.md5($("#new_pw1").val());
	var new_pw2=$.md5($("#new_pw2").val());
	if(new_pw1 != new_pw2) { alert("Le password non coincidono"); return false; }
	if($("#new_pw1").val().length < 6) { alert("La password deve essere di almeno 6 caratteri"); return false; }
	$.post("query/us_cambio_pwd.php", {user:user, old_pw:old_pw, new_pw:new_pw1,hid:'<?php echo $hid; ?>' },  
  function (data) {
	  if(data.error) { alert(data.error); return false; }
	  else {
		  alert("Password cambiata con successo");
		  document.form1.submit();
	  }
  },"json");
  
  return false;
}


</script>
</head>
<body>
<div id="container">
<?php include("./menu/menu.php"); ?>
<div id="main_pr">
<div class="box3"><span class="testo22">Cambio Password</span></div>
<br />
<div class="box1">
	<?php if($_GET['force']==1) { ?>
  <p class="box3" style="text-align:center;">E' necessario cambiare la password</p>
  	<?php } ?>
  <form id="form1" onsubmit="return cambio_pw();" style="width:550px; margin:auto;" name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <p>
      <label for="user" class="testo18">Utente</label>
      <br />
      <input name="user"type="text" class="txtfld1" id="user" style="width:550px;" readonly="readonly" value=<?php echo ucfirst(strtolower($_user)); ?> />
    </p>
    <p>
      <label for="old_pw" class="testo18">Vecchia Password</label>
      <br />
<input name="old_pw" style="width:550px;" type="password" class="txtfld1" id="old_pw" />
    </p>
    <p>
      <label for="new_pw1"><span class="testo18">Nuova Password</span></label>
      <span class="testo18">      (minimo 6 caratteri)</span><br />
<input name="new_pw1" style="width:550px;" type="password" class="txtfld1" id="new_pw1" />
    </p>
    <p>
      <label for="new_pw2" class="testo18">Ripeti Nuova Password</label><br />
<input type="password" style="width:550px;" name="new_pw2" class="txtfld1" id="new_pw2" />
    </p>
    <p>
      <input name="ok" type="submit" class="b2" id="ok" value="Esegui cambio Password" /><input type="hidden" name="Submit" value="OK" />
    </p>
  </form>
  <p class="testo22" id="error" style="text-align:center; height:30px;"></p>
</div>
<br />
</div>
</div>
</body>
</html>