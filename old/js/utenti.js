// JavaScript Document

// corner
$("#main_pr").corner();
$("#menu_cont").corner();

function uploaded(data) {
	if(data.error)  { alert(data.error); $("#logo").html(''); }
	else $("#logo").html('&nbsp;<img src="logos/'+data.nome+'" alt="logo" style="vertical-align:middle" />&nbsp;');
}

//ucfirst x Jquery
(function($) {

    $.ucfirst = function(str) {
		if(!$.trim(str)) return;
        var text = str;
        var parts = text.split(' '),
            len = parts.length,
            i, words = [];
        for (i = 0; i < len; i++) {
            var part = parts[i];
            var first = part[0].toUpperCase();
            var rest = part.substring(1, part.length).toLowerCase();
            var word = first + rest;
            if(word.length==1) word=word.toLowerCase();
			words.push(word);
		}
        return words.join(' ');
    };

})(jQuery);

function chk_campo(id) {	// chk campo
	if($.trim($("#"+id).val())) {
	$("#"+id).css("background-color",""); 
	var val=$.ucfirst($("#"+id).val());
	$("#"+id).val(val);
	return true; } 
	else { $("#"+id).css("background-color","#FF8A84"); return false; }
}

function chk_email(id) {	//chk campo email
		var email=$("#"+id).val();
		var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		if (!filter.test(email) && email) { $("#"+id).css("background-color","#FF8A84"); return false; }
		else if(!$.trim(email)) { $("#"+id).css("background-color","#FF8A84"); return false; }
		else { $("#"+id).css("background-color",""); return true; }
	}

function staff_ins() {
$.ajaxSetup({async:false});
if(chk_campo("new_nome")==true) var nome=$("#new_nome").val(); else return false;
if(chk_email("new_email")==true) var email=$("#new_email").val(); else return false;
var level=$("#new_level").val();
$.post("query/ut_ins_edit.php", {nome:nome, email:email, level:level, action:"ins"},
function (data) {
	if(level<1) alert("Nuovo utente creato correttamente!\r\nPassword non assegnata perchè l'utente è senza accesso.");
	else   if(confirm("Nuovo utente creato correttamente!\r\nLa password iniziale è\r\n\r\n"+data.pwd+"\r\n\r\nPrendine nota.\r\n\r\nSpedisco la password via email all'utente?")) send_pwd(nome, email, data.pwd);
},"json");
$.ajaxSetup({async:true});
window.location.reload();
}

function send_pwd(nome, email, pwd) {
$.post("query/ut_send_pwd.php", {nome:nome, email:email, pwd:pwd})
alert("Password spedita correttamente!");
}


function click_edit(id) {
var st_id=id.split("_");
	$("#"+st_id[0]+"_divnome_on").css('display','');
	$("#"+st_id[0]+"_divnome_off").css('display','none');
	$("#"+st_id[0]+"_divemail_on").css('display','');
	$("#"+st_id[0]+"_divemail_off").css('display','none');
	$("#"+st_id[0]+"_divlevel_on").css('display','');
	$("#"+st_id[0]+"_divlevel_off").css('display','none');
	$("#"+st_id[0]+"_divconf_on").css('display','');
	$("#"+st_id[0]+"_divconf_off").css('display','none');
}

function staff_edit(id) {
var st_id=id.split("_");
if(chk_campo(st_id[0]+"_nome")==true) var nome=$("#"+st_id[0]+"_nome").val(); else return false;
if(chk_email(st_id[0]+"_email")==true) var email=$("#"+st_id[0]+"_email").val(); else return false;
var level=$("#"+st_id[0]+"_level").val();
$.post("query/ut_ins_edit.php", {id:st_id[0], nome:nome, email:email, level:level, action:"edit"},
function (data) {
	$("#"+st_id[0]+"_divnome_off").html(nome);
	$("#"+st_id[0]+"_divnome_off").css('display','');
	$("#"+st_id[0]+"_divnome_on").css('display','none');
	$("#"+st_id[0]+"_divemail_off").html(email);
	$("#"+st_id[0]+"_divemail_off").css('display','');
	$("#"+st_id[0]+"_divemail_on").css('display','none');
	$("#"+st_id[0]+"_divlevel_off").html(data.level);
	$("#"+st_id[0]+"_divlevel_off").css('display','');
	$("#"+st_id[0]+"_divlevel_on").css('display','none');
	$("#"+st_id[0]+"_divconf_off").css('display','');
	$("#"+st_id[0]+"_divconf_on").css('display','none');
},"json");
}

function staff_del(id) {
var st_id=id.split("_");
var nome=$("#"+st_id[0]+"_nome").val();
if(!confirm("Sei Sicuro di voler eliminare l'utente\r\n"+nome+"?")) return false; 
$.ajaxSetup({async:false});
$.post("query/ut_ins_edit.php", {action:"del", id:st_id[0]},"json");
$.ajaxSetup({async:true});
window.location.reload();
}

function sel_tutto() {
if($("#0_pwc").attr('checked')=='checked') $(".pwc").attr('checked', 'checked');
else $(".pwc").removeAttr('checked');
if(!confirm("Vuoi forzare il cambio della password a tutti gli utenti?")) {
	$(".pwc, #0_pwc").removeAttr('checked');
	return false;
}
$.ajaxSetup({async:false});
$.post("query/ut_ins_edit.php", {id:0, action:"pwc"});
$.ajaxSetup({async:true});
window.location.reload();
}


function cambio_pwd(id) {
var ar=id.split("_")
st_id=ar[0]; stato=ar[2];
if(stato=="off" && !confirm("Vuoi forzare il cambio della password a questo utente?")) { $(".pwc").removeAttr('checked'); return false; }
if(stato=="on"  && !confirm("Vuoi togliere l'obbligo di cambio password a questo utente?")) return false;
$.post("query/ut_ins_edit.php", {id:st_id, stato:stato, action:"pwc"});
if(stato=="off") {
	$("#"+st_id+"_divpwc_on").css('display','');
	$("#"+st_id+"_divpwc_off").css('display','none');
}
if(stato=="on") {
	$("#"+st_id+"_divpwc_off").css('display','');
	$("#"+st_id+"_divpwc_on").css('display','none');
}
$(".pwc").removeAttr('checked');
}


function sw(sw,id) {
var des=id;
var field1=sw;
$.post("query/conf_upd.php", {des:des, field1:field1});
if(sw=="0") $("#"+id).html('<img onclick="sw(\'1\',\''+id+'\')" src="images/off.png" width="60" height="25" alt="off" style="vertical-align:middle; margin-right:5px; cursor:pointer;" />');
if(sw=="1") $("#"+id).html('<img onclick="sw(\'0\',\''+id+'\')" src="images/on.png" width="60" height="25" alt="off" style="vertical-align:middle; margin-right:5px; cursor:pointer;" />');
action2(sw,id);
}

function action2(sw ,id) {

	if(id=='ftp') {
	if(sw==0) $('#form3').find('input, checkbox, button').attr('disabled','disabled');
	if(sw==1) $('#form3').find('input, checkbox, button').removeAttr('disabled');
	}
	
	if(id=='cntore') {
	if(sw==0) $('#auth').css('display','none');
	if(sw==1) $('#auth').css('display','');
	}
		
	if(id=='pc_auth') {
		$.post("query/conf_pcauth.php", {sw:sw});
	}
		
	if(id=='fatt') {
	if(sw==0) $('#cfiva').css('display','none');
	if(sw==1) $('#cfiva').css('display','');
	}
}

var _timer=0;
function defiva(val) {
    if (_timer)
        window.clearTimeout(_timer);
    _timer = window.setTimeout(function() {
	 var des="iva";
	 val=val.replace(",",".");
	 val = +val || 0;
	 var field1=parseFloat(val);
	 $("#w1").css('display','');
     $.post("query/conf_upd.php", {des:des, field1:field1});
     $("#w1").delay(500).fadeOut(0);
	}, 900);
	
}

function aggemail() {
	$("#w1").css('display','');
	var des="email";
	var field1=$("#nome").val();
	var field2=$("#email").val();
	$.post("query/conf_upd.php", {des:des, field1:field1, field2:field2 });
	$("#w1").css('display','none');
}


function cnflnk() {
	var des="conf_link";
	var field2=$("#bkconf_link").val();
	$.post("query/conf_upd.php", {des:des, field2:field2});
}