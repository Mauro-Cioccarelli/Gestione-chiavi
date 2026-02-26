<?php
require_once("../Connections/iconn.php");

//$sql=$mysqli->query("select us_id from keys_users where us_name='{$_POST['nome']}' limit 1");
//if($sql->num_rows==0) $error="ID sconosciuto";
//else { $res=$sql->fetch_array(); }

//if(!$error) {
$sql=$mysqli->query("select us_id from keys_users where us_name='{$_POST['utente']}' and us_email='{$_POST['email']}'");
if($sql->num_rows==0) $error="Nome Utente errato o email non corretta";
else { $res=$sql->fetch_array(); $us_id=$res[0]; }
//}

if(!$error) {
$new_pwd=rand(10001,99999);
$email=$_POST['email'];
$md5_pwd=md5($new_pwd);
$mysqli->query("update keys_users set us_old_pwd=us_pwd, us_pwd='$md5_pwd', us_pw_cng='1' where us_id='$us_id'");


$testo='
Ciao,
questa email ti è stata spedita da KEYmanager perchè hai richiesto una nuova password.
Se non sei stato tu a richiedere la nuova password ti preghiamo di non considerare questa email e cancellarla.

Grazie
KEYmanager


La Nuova Password è:'.$new_pwd.'
Dovrai Cambiarla al primo accesso.';

mail($email,"Nuova password KEYmanager - Non rispondere a questo messaggio",$testo,'From: "KEYmanager" <info@agenzianegri.com> \r\n');
}

echo json_encode(array("error"=>$error));
?>