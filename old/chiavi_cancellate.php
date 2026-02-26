<?php
require_once("Connections/iconn.php");
require_once("guard.php");

$ksql=$mysqli->query("select * from keys_k  where k_canc = 'c' order by k_id DESC");
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Gestione chiavi - HOME</title>

<script type="text/javascript" src="js/jquery/jquery-2.1.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.12.1/jquery-ui.min.js"></script>
<script src="DataTables-1.10.12/media/js/jquery.dataTables.min.js"></script>

<script type="text/javascript" src="js/jquery.corner.js"></script>

<script type="text/javascript" src="js/inventario.js"></script>
<script type="text/javascript" >
$("#main_pr").corner();
$("#menu_cont").corner();
$("#search").corner();
</script>

<link href="DataTables-1.10.12/media/css/jquery.dataTables.css" rel="stylesheet" type="text/css">
<link href="bb.css" rel="stylesheet" type="text/css" />
</head>

<body>
<div id="container">
<?php include("./menu/menu.php"); ?>
<div id="main_pr">
<br>
<br />
<p align="center" style="display:table; margin:auto;">
  <input name="search" value="Cerca..." type="text" class="srcfld" id="search" style="vertical-align:middle; display:table-cell;" onfocus="if (this.value == 'Cerca...') this.value = ''"
onblur="if(this.value== '') this.value='Cerca...'" size="70" maxlength="250" > 
   &nbsp;
   <input name="imageField" type="image" onclick="s_res()" id="imageField" src="images/search.png" style="vertical-align:middle; display:table-cell;" >
<div style="width:95%; margin:auto; height:30px;" id="add" ></div>
<div style="width:95%; margin:auto; height:30px; display:none;" align="center" id="del">
  <br />  <br />
<input align="right" name="canc" type="submit" class="b2" style="background-color:#FF0000; color:#FFF; margin:auto;" id="canc" value="Ripristina" >
</div>
<p class="box3"> Chiavi cancellate</p>
<div  style="width:95%; margin:auto;" id="inv" >
  <br>
<table cellspacing="0" width="100%" id="tb1" class="display cell-border">
  <thead>
    <tr class="testo16">
      <th class="head" height="40" scope="col">Num.</th>
      <th class="head" height="40" scope="col">Categoria</th>
      <th class="headr" height="40" scope="col">Chiave</th>
      <th class="headr" height="40" nowrap="nowrap" scope="col">Stato</th>
      </tr>
   </thead>
   <tbody>
<?php while($row=mysqli_fetch_assoc($ksql)) { ?>
    <tr class="testo13" id="r_<?php echo $row['k_id']; ?>">
      <td title="Storia della chiave" align="center" class="storia"><a href="storia.php?id=<?php echo $row['k_id']; ?>">&nbsp;&nbsp;<?php echo $row['k_id']; ?>&nbsp;&nbsp;</a></td>
      <td><?php echo $row['k_cat']; ?></td>
      <td><?php echo $row['k_name']; ?></td>
      <td>Cancellata</td>
      </tr>
 <?php } ?>
</tbody>
</table>
</div>
<p>&nbsp;</p>
</div>
</div>
</body>
</html>