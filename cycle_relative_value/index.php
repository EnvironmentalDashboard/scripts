<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
</head>
<body>
	<form action="" method="POST" id="form">
		<input type="text" name="v" id="v" value="10,30,50,70,90">
		<input type="text" name="t" id="t" value="10">
		<input type="submit">
	</form>
	<p id="status"></p>
<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
<script>
	var form = $('#form');
	form.on('submit', function(e) {
		e.preventDefault();
		var v = $('#v').val();
		var t = $('#t').val();
		var http = new XMLHttpRequest();
		var url = "backend.php";
		var params = "v="+v+"&t=" + t;
		http.open("POST", url, true);
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.onreadystatechange = function() {
	    if(http.readyState == 4 && http.status == 200) {
        console.log(http.responseText);
        $('#status').text('Orb done cycling; press submit to send another cycle');
	    }
		}
		http.send(params);
		$('#status').text('Orb currently cycling through relative values: ' + v + '; will be done in ' + (t*5) + ' seconds.');
	});
</script>
</body>
</html>