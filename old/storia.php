<?php
require_once("Connections/iconn.php");
require_once("guard.php");

if($_GET['id']) $query=" Where log_kid='".$_GET['id']."' ";
$ksql=$mysqli->query("select *,DATE_FORMAT(log_date, '%d.%m.%Y %k:%i') as date from keys_log".$query." order by log_date DESC");
?>

<!doctype html>
<html><head>
<meta charset="utf-8">
<title>Gestione chiavi - logs</title>

<script type="text/javascript" src="js/jquery/jquery-2.1.1.min.js"></script>
<script src="js/jquery-ui-1.12.1/jquery-ui.min.js"></script>
<script src="DataTables-1.10.12/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/storia.js"></script>
<script type="text/javascript" >
$("#main_pr").corner();
$("#menu_cont").corner();
$("#search").corner();
</script>

<link href="bb.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="js/jquery-ui-1.12.1/jquery-ui.min.css">
<link href="DataTables-1.10.12/media/css/jquery.dataTables.css" rel="stylesheet" type="text/css">

</head>

<body>
<div id="container">
<?php include("./menu/menu.php"); ?>
<div id="main_pr">
<br>
<br />
<div align="center">
  <input name="search" value="Cerca..." type="text" class="srcfld" id="search" style="vertical-align:middle;" onfocus="if (this.value == 'Cerca...') this.value = ''"
onblur="if(this.value== '') this.value='Cerca...'" size="70" maxlength="250" >
  &nbsp;&nbsp;
<input name="imageField" type="image" onclick="s_res()" id="imageField" src="images/search.png" style="vertical-align:middle; display: inline" >
  <input type="hidden" name="_user" value="<?php echo $_user; ?>" id="_user">
</div>
<div  style="width:95%; margin:auto;" id="inv" >
  <p class="box3">Storico Movimenti</p>
<table cellspacing="0" width="100%" id="tb1" class="display cell-border">
  <thead>
    <tr class="testo16">
      <th class="head" scope="col">&nbsp;</th>
      <th class="head" height="40" scope="col">Data e Ora</th>
      <th height="40" class="head" scope="col">Azione</th>
      <th class="headr" height="40" nowrap="nowrap" scope="col">Utente</th>
      </tr>
   </thead>
   <tbody>
<?php while($row=mysqli_fetch_assoc($ksql)) { ?>
    <tr class="testo13" id="r_<?php echo $row['k_id']; ?>">
      <td><?php echo $row['log_id']; ?></td>
      <td align="center"><?php echo $row['date']; ?></span></td>
      <td><?php echo $row['log_action']; ?></td>
      <td><?php echo $row['log_user']; ?></td>
      </tr>
 <?php } ?>
</tbody>
</table>
</div>
<p>&nbsp;</p>
<p>&nbsp;</p>
</div>
</div>
</body>
</html>