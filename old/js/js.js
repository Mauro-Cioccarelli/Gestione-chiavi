
function key_ins() {
	var cat=$("#kcat").val();
	var name=$("#kname").val();
	$.post("query/kins.php", {cat:cat, name:name});
		var tb1 = $('#tb1').DataTable();
		tb1.row.add( [cat, name, 'In carico', '<img src="images/edit_sm.png" width="15" height="15" alt=""/>&nbsp;&nbsp;<img src="images/delete2_sm.png" width="14" height="14" alt=""/>'
    ] ).draw();
}