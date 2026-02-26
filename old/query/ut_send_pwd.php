<?php
require_once("../Connections/iconn.php");
require_once("../guard.php");

$testo='
Ciao,
questa email ti è stata spedita da KEYmanager perchè sei stato aggiunto come nuovo utente.
Ecco i dati per l\'accesso:
Nome Utente: '.$_POST['nome'].'
Password: '.$_POST['pwd'].'

Pagina di accesso: http://www.agenzianegri.com/chiavi

La password dovrà essere cambiata al primo accesso.

Grazie
KEYmanager
';

mail($_POST['email'],"Nuova utenza KEYmanager - Non rispondere a questo messaggio",$testo,'From: "KEYmanager" <info@agenzianegri.com> \r\n');

?>