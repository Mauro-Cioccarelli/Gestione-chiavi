<?php
require_once("Connections/iconn.php");
require_once("guard.php");
if($_level <2) header("location:index.php");

date_default_timezone_set("Europe/Rome");

$sqls=$mysqli->query("select * from keys_users");
$lev_des=array("", "Utente", "Amministratore"); 
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Gestione Utenti</title>
<link href="bb.css" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="js/jquery/jquery-2.1.1.min.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/utenti.js"></script>

</head>
<body>

<div id="container">
<?php include("./menu/menu.php"); ?>
<div id="main_pr">
  <div class="box3"><span class="testo22">Utenti </span></div>
<div class="box1">
<form id="form1" name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<table width="100%" border="1" align="center" cellpadding="5" cellspacing="0" class="table">
  <tr>
      <th width="155" class="table testo16" scope="col">Utente</th>
      <th width="265" class="table testo16" scope="col">email</th>
      <th width="165" class="table testo16" scope="col">permessi</th>
      <th width="100" class="table testo16" scope="col">cambio pw
        <br />        
        <input type="checkbox" name="0_pwc" id="0_pwc" onclick="sel_tutto()" /></th>
      <th width="60" class="table testo16" scope="col">edit</th>
    </tr>
<?php while($row=mysqli_fetch_assoc($sqls)) { 
if($row['us_id']==1) continue;
?>
  <tr>
    <td class="table"><div id="<?php echo $row['us_id']; ?>_divnome_off" class="testo16" style="overflow:hidden; white-space:nowrap"><?php echo $row['us_name']; ?></div>
      <div id="<?php echo $row['us_id']; ?>_divnome_on" style="display:none;" ><input style="width:150px;" type="text" name="<?php echo $row['us_id']; ?>_nome" id="<?php echo $row['us_id']; ?>_nome" value="<?php echo $row['us_name']; ?>" /></div></td>
    <td class="table"><div id="<?php echo $row['us_id']; ?>_divemail_off" class="testo16" style="overflow:hidden; white-space:nowrap"><?php echo $row['us_email']; ?></div>
<div id="<?php echo $row['us_id']; ?>_divemail_on" style="display:none;" ><input style="width:260px;" type="text" name="<?php echo $row['us_id']; ?>_email" id="<?php echo $row['us_id']; ?>_email" value="<?php echo $row['us_email']; ?>" /></div></td>
    <td class="table"><div id="<?php echo $row['us_id']; ?>_divlevel_off" class="testo16" style="overflow:hidden; white-space:nowrap"><?php echo $lev_des[$row['us_level']]; ?></div>
<div id="<?php echo $row['us_id']; ?>_divlevel_on" style="display:none;" >
<select name="<?php echo $row['us_id']; ?>_level" id="<?php echo $row['us_id']; ?>_level" style="width:160px;">
        <option value="1" <?php if($row['us_level']==1) echo'selected="selected"'; ?> >Utente</option>
        <option value="2" <?php if($row['us_level']==2) echo'selected="selected"'; ?> >Amministratore</option>
</select>
</div>
    </td>
    <td class="table" style="text-align:center;">
    <div id="<?php echo $row['us_id']; ?>_divpwc_off" <?php if($row['us_pw_cng']==1) echo 'style="display:none;"' ?> ><input type="checkbox" onclick="cambio_pwd(this.id)" name="" class="pwc" id="<?php echo $row['us_id']; ?>_pwc_off" /></div>
    <div id="<?php echo $row['us_id']; ?>_divpwc_on" <?php if($row['us_pw_cng']==0) echo 'style="display:none;"' ?> ><img src="images/checked.png" width="20" height="20" alt="checked" style="cursor:pointer" onclick="cambio_pwd($(this).parent().attr('id'))" /></div>
    </td>
    <td class="table" style="text-align:center;"><div id="<?php echo $row['us_id']; ?>_divconf_off" class="testo16" style="overflow:hidden; white-space:nowrap"><img src="images/edit_sm.png" width="15" height="15" alt="Edit" title="Modifica" onclick="click_edit($(this).parent().attr('id'))" style="cursor:pointer;" />&nbsp;&nbsp;<img src="images/delete_sm.png" width="15" height="15" alt="Delete" title="Elimina" onclick="staff_del($(this).parent().attr('id'))" style="cursor:pointer;" /></div>
<div id="<?php echo $row['us_id']; ?>_divconf_on" style="display:none;" ><input onclick="staff_edit(this.id)" name="" type="button" class="b3" id="<?php echo $row['us_id']; ?>_edit" value="ok" /></div>
	</td>
  </tr>
  <?php } ?>
    <tr>
    <td class="table"><input style="width:150px;" type="text" name="new_nome" id="new_nome" /></td>
    <td class="table"><input style="width:260px;" type="text" name="new_email" id="new_email" /></td>
    <td class="table">
  <select name="new_level" id="new_level" style="width:160px;">
        <option value="1" selected="selected">Utente</option>
        <option value="2">Amministratore</option>      
      </select>
    </td>
    <td class="table" style="text-align:center;"><input type="checkbox" disabled="disabled" /></td>
    <td class="table" style="text-align:center;"><input name="new_ins" onclick="staff_ins()" type="button" class="b3" id="new_ins" value="ins" />
	</td>
  </tr>
</table>
<br style="clear:both;" />
<br />
</form>
<p><br style="clear:both;" />
</p>
</div>
<p>&nbsp;</p>
  <p><br />
  <br style="clear:both;" />
</p>
</div>
</div>
</body>
</html>