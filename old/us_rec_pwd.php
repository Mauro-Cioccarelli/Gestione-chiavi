<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Password recovery</title>
<link href="bb.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery/jquery-2.1.1.min.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>

<script type="text/javascript">
$("#main_pr").corner();
$("#menu_cont").corner();

//----------------------------
function cambio_pw() {
	var id=$.trim($("#h_id").val()); // hid
	var utente=$.trim($("#utente").val());
	var email=$.trim($("#email").val());
	if(!h_id) { alert("Manca l\'ID"); return false; }
	if(!utente) { alert("Manca il nome utente"); return false; }
	if(!email) { alert("Manca l\'indirizzo email"); return false; }
	
	$.post("query/rec_pwd.php", {id:id, utente:utente, email:email },  
  function (data) {
	  if(data.error) { alert(data.error); return false; }
	  else {
		  alert("La Nuova password ti è stata spedita via email");
		  document.form1.submit();
	  }
  },"json");
  
  return false;
}


</script>
</head>
<body>
<div id="container">
<p>&nbsp;</p>
<p>&nbsp;</p>
<div id="main_pr">
<br />
<div class="box3">Recupero Password</div>
<br />
<div class="box1">
  <form id="form1" onsubmit="return cambio_pw();" style="width:550px; margin:auto;" name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <p style="display:none;">
      <label for="h_id" class="testo18">ID</label>
      <br />
      <input name="h_id"type="text" class="txtfld1" id="h_id" value="10" style="width:550px;" />
    </p>
    <p>
      <label for="utente" class="testo18">Nome Utente</label>
      <br />
<input name="utente" style="width:550px;" type="text" class="txtfld1" id="utente" />
    </p>
    <p>
      <label for="email"><span class="testo18">Email</span></label>
      <br />
<input name="email" style="width:550px;" type="text" class="txtfld1" id="email" />
<br />
    </p>
    <p>
      <input name="ok" type="submit" class="b2" id="ok" value="Richiedi nuova Password" /><input type="hidden" name="Submit" value="OK" />
  </p>
</form>
<br />
    <p style="text-align:center" class="testo16"><a href="login.php">Login</a></p>
</div>
<br />
</div>
</div>
</body>
</html>