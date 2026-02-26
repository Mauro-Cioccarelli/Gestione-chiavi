<?php
require_once("../Connections/iconn.php");
require_once("../guard.php");

if($term=addslashes($_GET['term'])) {

$sql=$mysqli->query("select k_cat from keys_k where k_cat like '$term%' group by k_cat");

$cat=array();
while($res=mysqli_fetch_assoc($sql))  {
$cat[]=utf8_encode(trim(ucwords($res['k_cat'])));
}
$term=stripslashes($term);

echo json_encode(array("term"=>$term, "value"=>$cat));
}
?>