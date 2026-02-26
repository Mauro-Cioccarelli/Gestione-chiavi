$(document).ready( function () {

// DataTables
    $('#tb1').DataTable({
 columnDefs: [
		{ targets: [0,3], searchable: false}
    ],
  "order": [ 0, 'desc' ],
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
} );

// Autocomplete
$( "#kcat" ).autocomplete({
  autoFocus: true,
  delay: 200,
  minLength: 2,
      source: function( request, response ) {
        $.ajax( {
          url: "query/autocompl_place.php", dataType: "json", data: {term: request.term}, success: function( data ) {
            response( data.value );
          }
        });
      },
});


// selezione riga
$('#tb1 tbody').on( 'click', 'tr', function () {
        if ( $(this).hasClass('selected') ) {
            $(this).removeClass('selected');
			$("#del").css("display", "none");
			$("#add").css("display","");
        }
        else {
            table.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
			$("#add").css("display", "none");
			$("#del").css("display","");
			var rowIdx = table.row('.selected').index();
			$("#kcat_v").val(table.cell( rowIdx, 1 ).data());
			$("#kname_v").val(table.cell( rowIdx, 2 ).data());
        }
    } );

//Elimina / Ripristina chiave
    	$('#canc').click( function () {
		var rec;
		if($('#canc').val()=="Ripristina") var rec="r";
		var rowIdx = table.row('.selected').index();
		var id1 =table.cell( rowIdx, 0 ).data();
		id=$(id1).text(); id=parseInt(id);
		key_del(id, rec);
    } );

// modifica riga
$('#kmod').click( function () {
	var rowIdx = table.row('.selected').index();
	var id1 = table.cell( rowIdx, 0 ).data();
	id=$(id1).text(); id=parseInt(id);
	var cat=$.trim($("#kcat_v").val());
	var name=$.trim($("#kname_v").val());
	var stato = table.cell( rowIdx, 3 ).data();
	if(cat=="" || name=="") return false;
	if(cat=="Categoria..." || name=="Chiave...") return false;
		
	$.post("query/kvar.php", {id:id, cat:cat, name:name}, function (data) {
	var row= table.row('.selected').data( [id, cat, name, stato] ).columns.adjust().draw(false);
	storia("Modificata chiave n."+id+" - Da Categoria:\""+cat+"\" Nome:\""+name+" - A Categoria:\""+data.cat+"\" Nome:\""+data.name+"\"", id);
	$('.selected').removeClass('selected');
	$("#del").css("display", "none");
	$("#add").css("display","");
		}, "json");
	});


/// fine  on document ready
});

// inserimento riga
function key_ins() {
	var cat=$.trim($("#kcat").val());
	var name=$.trim($("#kname").val());
	if(cat=="" || name=="") return false;
	if(cat=="Categoria..." || name=="Chiave...") return false;
	
	$.post("query/kins.php", {cat:cat, name:name}, function (data) { 
		if(data.error==1) alert("Esiste già una chiave con questo nome!");
		else {
		var tb1 = $('#tb1').DataTable();
		var row= tb1.row.add( ['<a href="storia.php?id='+data.id+'">&nbsp;&nbsp;'+data.id+'&nbsp;&nbsp;</a>', cat, name, 'in Carico'] ).order([ 0, 'desc' ]).draw().node();

		$(row).attr( "id","r_"+data.id);
		tb1.column( 0 ).nodes().to$().attr({align: "center", title: "Storia della chiave", class: "storia" });
		tb1.columns.adjust().draw();
		storia("Caricata nuova chiave n."+data.id+" Categoria:\""+cat+"\" Nome:\""+name+"\"", data.id);
		}

	$("#kcat").val("Categoria...");
	$("#kname").val("Chiave...");

	}, "json");
}


// cancella riga
function key_del(id, r) {
	if(r=='r') var rr="ripristinare"; else rr="cancellare";
	if(confirm("Sei sicuro di voler "+rr+" dall'inventario questa chiave? "+id)) {
		$.post("query/kdel.php", {id:id, r:r}, function (data) {
		if(data.error==1) alert("impossibile cancellare questa chiave perchè non risulta in carico");
		else { 
		$('#tb1').DataTable() .row( $("#r_"+id).closest('tr') ) .remove().columns.adjust().draw(false);
		storia(data.action+" chiave n."+id+" Categoria:\""+data.cat+"\" Nome:\""+data.name+"\"", id);
		}
		$("#del").css("display", "none"); $("#add").css("display","");
}, "json");
	}
}

// search reset	
function s_res() {
$("#search").val("Cerca...");
$('#tb1').DataTable() .search('').draw(false);
}

function storia(action, kid) {
	$.post("query/storia.php", {action:action, kid:kid});
}