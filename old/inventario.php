<?php
require_once("Connections/iconn.php");
require_once("guard.php");

$ksql=$mysqli->query("select * from keys_k  where k_canc != 'c' order by k_id DESC");
?>



<!doctype html>
<html><head>
<meta charset="utf-8">
<title>Gestione chiavi - Inventario</title>

<script type="text/javascript" src="js/jquery/jquery-2.1.1.min.js"></script>
<script src="js/jquery-ui-1.12.1/jquery-ui.min.js"></script>
<script src="DataTables-1.10.12/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/inventario.js"></script>
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
<p align="center" style="margin:auto;">
  <input name="search" value="Cerca..." type="text" class="srcfld" id="search" style="vertical-align:middle; display:inline;" onfocus="if (this.value == 'Cerca...') this.value = ''"
onblur="if(this.value== '') this.value='Cerca...'" size="70" maxlength="250" > 
   &nbsp;
   <input name="imageField" type="image" onclick="s_res()" id="imageField" src="images/search.png" style="vertical-align:middle; display:inline;" >
</p>
<div style="height:120px;">
<div style="width:95%; margin:auto; overflow:auto; height:120px" id="add">
<p style="margin-top:0px;" class="box3">Aggiungi<br /><br /></p>

  <input name="q" type="text" value="Categoria..." class="txtfld2" id="kcat" onfocus="if (this.value == 'Categoria...') this.value = ''"
onblur="if(this.value== '') this.value='Categoria...'" >
&nbsp;&nbsp; 
<input name="kname" type="text" value="Chiave..." class="txtfld2" id="kname" onfocus="if (this.value == 'Chiave...') this.value = ''"
onblur="if(this.value== '') this.value='Chiave...'" style="width:40%;">
&nbsp;&nbsp;&nbsp;
<input name="submit" type="submit" class="b2" id="kins" value="  Aggiungi  " onclick="key_ins()">
</div>
<div style="width:95%; margin:auto; height:120px; display:none;" id="del">
<p style="margin-top:0px;" class="box3">Modifica / Cancella chiave</p>
  <input name="kcat_v" type="text"  class="txtfld2" id="kcat_v" style="width:40%;">
&nbsp;&nbsp; 
<input name="kname_v" type="text"  class="txtfld2" id="kname_v"  style="width:40%;">
&nbsp;&nbsp;&nbsp;
<input name="kmod" type="submit" class="b2" id="kmod" value="  Modifica  " ><br /><br />
<input align="center" name="canc" type="submit" class="b2" style="background-color:#FF0000; color:#FFF; margin:auto; text-align:center;" id="canc" value="Cancella" >
</div>
</div>
<div  style="width:95%; margin:auto;" id="inv" >
<p class="box3">Inventario</p>
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
      <td><?php if($row['k_out']==0) echo "in Carico"; else echo "<span style='color:#c00'>In consegna a ".$row['k_cons_to']."</span>"; ?></td>
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