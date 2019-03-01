<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
</head>
<body>
	<form action="" method="POST" id="form">
		<input type="text" name="v" id="v" value="0,1,2,3,4">
		<input type="text" name="uuid" id="uuid" placeholder="meter uuid">
		<input type="text" name="t" id="t" value="12">
		<input type="submit">
	</form>
	<p id="status"></p>
<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
<script>
	var form = $('#form');
	form.on('submit', function(e) {
		e.preventDefault();
		var v = $('#v').val();
		var vals = v.split(",");
		for (var i = vals.length - 1; i >= 0; i--) {
			vals[i] = convertRange(vals[i], 0, 4, 0, 100);
		}
		var t = $('#t').val();
		var http = new XMLHttpRequest();
		var url = "backend.php";
		var params = "v="+JSON.stringify(vals)+"&t=" + t;
		console.log(params);
		http.open("POST", url, true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.onreadystatechange = function() {
	    if(http.readyState == 4 && http.status == 200) {
        console.log(http.responseText);
        $('#status').text('Orb done cycling; press submit to send another cycle');
	    }
		}
		http.send(params);
		$('#status').text('Orb currently cycling through relative values: ' + JSON.stringify(vals) + '; will be done in ' + (t*vals.length) + ' seconds.');
	});
	function convertRange(val, old_min, old_max, new_min, new_max) {
	  if (old_max == old_min) {
	    return 0;
	  }
	  return (((new_max - new_min) * (val - old_min)) / (old_max - old_min)) + new_min;
	}
</script>
</body>
</html>