<!DOCTYPE html>
	<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
	<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
	<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
	<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title>MAP</title>
		<meta name="description" content="Destiny 2 clan EnotWhyNot" />
		<meta name="keywords" content="destiny 2, clan, enotwhynot, enot why not" />
		<meta name="author" content="Hoth & grayjaco " />
		<meta name="viewport" content="width=device-width, initial-scale=1">


		<!-- LOAD JQUERY LIBRARY -->
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.js"></script>

        <script src='https://api.mapbox.com/mapbox-gl-js/v0.47.0/mapbox-gl.js'></script>
        <link href='https://api.mapbox.com/mapbox-gl-js/v0.47.0/mapbox-gl.css' rel='stylesheet' />

	</head>
	
	<body>

    <div id='map' style='width: 900px; height: 300px;'></div>
    <script>
        mapboxgl.accessToken = 'pk.eyJ1IjoiYWxleGFuZGVyaHVudGVyIiwiYSI6ImNqbHhpcjlhZzFla2YzcG8xNWlheHgzdmUifQ.ZoxyL9e0uSq23OYN6y27_g';
        var map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v10'
        });
    </script>

	</body>
</html>
