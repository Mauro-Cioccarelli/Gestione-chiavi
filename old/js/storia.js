$(document).ready( function () {

// DataTables
    $('#tb1').DataTable({
"columnDefs": [
{ "orderData": [0],    "targets": [1, "desc"]},
{ targets: [0], visible: false}
  ],
  "language": {"url":"./js/it.json"},
  "orderCellsTop": true,
  "pageLength": 40,
  "lengthChange": false,
  "select": true,
  "dom": 'rtip'
});

var table = $('#tb1').DataTable();
 
// Ricerca
$('#search').on( 'keyup', function () {
    table.search( this.value ).draw();
});

/// fine  on document ready
});



// search reset	
function s_res() {
$("#search").val("Cerca...");
$('#tb1').DataTable() .search('').draw(false);
}
