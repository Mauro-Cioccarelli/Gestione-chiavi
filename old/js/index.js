$(document).ready( function () {

// DataTables
    $('#tb1').DataTable({
  "order": [ 3, 'desc' ],
  "language": {"url":"./js/it.json"},
  "orderCellsTop": true,
  "pageLength": 25,
  "lengthChange": false,
  "select": true,
  "dom": 'rtip'
});

var table = $('#tb1').DataTable();
 
// Ricerca
$('#search').on( 'keyup', function () {
    table.search( this.value ).draw();
});

// selezione riga
$('#tb1 tbody').on( 'click', 'tr', function () {
        if ( $(this).hasClass('selected') ) {
            $(this).removeClass('selected');
			$("#divcons").css("display","none");
			$("#divrie").css("display","none");
        }
        else {
			$("#divcons").css("display","none");
			$("#divrie").css("display","none");
            table.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
			var rowIdx = table.row('.selected').index();
			var stato = table.cell( rowIdx, 3 ).data();
			$("#txtcons").val("Consegna a...");
			if(stato==("in Carico")) $("#divcons").css("display", "");
			else $("#divrie").css("display","");
			}
});

// Consegna chiave
$('#consegna').click( function () {
	var rowIdx = table.row('.selected').index();
	var id1 = table.cell( rowIdx, 0 ).data();
	id=$(id1).text(); id=parseInt(id);
	var txt=$.trim($("#txtcons").val());
	if(txt=="" || txt=="Consegna a...") return false;
	var cat=table.cell( rowIdx, 1 ).data();
	var name=table.cell( rowIdx, 2 ).data();	
	$.post("query/kcons.php", {id:id, txt:txt, action:'cons'}, function (data) {
	var row= table.row('.selected').data( [id, cat, name, data.stato] ).columns.adjust().draw(false);
	storia("Consegnata chiave n."+id+" Categoria:\""+cat+"\" Nome:\""+name+"\" a "+txt, id);
	$('.selected').removeClass('selected');
	$("#divcons").css("display","none");
	$("#divrie").css("display","none");
	}, "json");
	
	});
	
// Rientro Chiave
$('#rientro').click( function () {
	var rowIdx = table.row('.selected').index();
	var id1 = table.cell( rowIdx, 0 ).data();
	id=$(id1).text(); id=parseInt(id);
	if(!confirm("Confermi il rientro della chiave "+id+"?")) return false;
	var cat=table.cell( rowIdx, 1 ).data();
	var name=table.cell( rowIdx, 2 ).data();	
	$.post("query/kcons.php", {id:id, action:'rie'}, function (data) {
	var row= table.row('.selected').data( [id, cat, name, data.stato] ).columns.adjust().draw(false);
	storia("Rientrata chiave n."+id+" Categoria:\""+cat+"\" Nome:\""+name+"\" da "+data.txt, id);
	$('.selected').removeClass('selected');
	$("#divcons").css("display","none");
	$("#divrie").css("display","none");
	}, "json");
	});
	
/// fine  on document ready
});



// search reset	
function s_res() {
$("#search").val("Cerca...");
$('#tb1').DataTable() .search('').draw(false);
}


function storia(action, kid) {
	$.post("query/storia.php", {action:action, kid:kid});
}
	