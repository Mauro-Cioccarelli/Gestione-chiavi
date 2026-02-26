<?php
require_once("Connections/iconn.php");
require_once("guard.php");

$ksql=$mysqli->query("select *,DATE_FORMAT(k_out, '%d.%m.%Y %k:%i') as kout from keys_k  where k_canc != 'c' order by k_out DESC, k_id DESC");
?>



<!doctype html>
<html><head>
<meta charset="utf-8">
<title>Gestione chiavi - HOME</title>

<script type="text/javascript" src="js/jquery/jquery-2.1.1.min.js"></script>
<script src="js/jquery-ui-1.12.1/jquery-ui.min.js"></script>
<script src="DataTables-1.10.12/media/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/index.js"></script>
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
</div>
<div style="height:120px; overflow:hidden;">
<div style="display:none; width:95%; margin:auto; overflow:hidden; height:120px" id="divcons">
<p class="box3">Consegna<br /><br /></p>
&nbsp;&nbsp; 
<input name="cons" type="text" value="Consegna a..." class="txtfld2" id="txtcons" onfocus="if (this.value == 'Consegna a...') this.value = ''"
onblur="if(this.value== '') this.value='Consegna a...'" style="width:70%;">
&nbsp;&nbsp;&nbsp;
<input name="submit" type="submit" class="b2" id="consegna" value="  Consegna  ">
</div>
<div style="display:none; width:95%; margin:auto; overflow:hidden; height:120px" id="divrie">
<p class="box3">Rientro<br />
  <br /></p>
<p align="center"> <input align="center" name="submit" type="submit" class="b2" id="rientro" value="  Rientro  " ></p>
</div>
</div>
<div  style="width:95%; margin:auto;" id="inv" >
<p class="box3">Chiavi in consegna / In carico</p>
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
      <td title="Storia della chiave" align="center" class="storia"><a href="storia.php?id=<?php echo $row['k_id']; ?>">&nbsp;<?php echo $row['k_id']; ?></a></td>
      <td><?php echo $row['k_cat']; ?></td>
      <td><?php echo $row['k_name']; ?></td>
      <td><?php if($row['k_out']==0) echo "in Carico"; 
	  
	  else echo "<span style='color:#c00'>In consegna a ".$row['k_cons_to']."<br />Da ".$row['k_cons_from']." il ".$row['kout']."</span>";
	  ?></td>
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