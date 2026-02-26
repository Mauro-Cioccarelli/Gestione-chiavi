<?php
require_once("../Connections/iconn.php");
require_once("../guard.php");
$sql=$mysqli->query("select * from keys_users where us_name='{$_POST['user']}' and us_pwd='{$_POST['old_pw']}' and hid='{$_POST['hid']}'");
if($sql->num_rows==0) $error="Nome Utente o password errati";
$row=$sql->fetch_assoc();
if($row['us_old_pwd']==$_POST['new_pw'] || $row['us_pwd']==$_POST['new_pw']) $error="La nuova password non può essere uguale alle due precedenti";

echo json_encode(array("error"=>$error));
?>