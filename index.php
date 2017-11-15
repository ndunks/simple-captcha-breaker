<!DOCTYPE html>
<html>
<head>
	<title>Captcha Breaker</title>
</head>
<body>
<h1>Results</h1>
<p>Reload page to get different captchas</p>
<table border="1" style="border-collapse: collapse;" cellpadding="5px">
	<tr><th>No</th><th>Captcha</th><th>Text</th></tr>
<?php
$url	= "http://hj.bola88.com/Public/img.aspx?r=437035075";
function getImage( $url )
{
	if( ini_get('allow_url_fopen') ) {
	   return file_get_contents($url);
	}else{ //Use CURL
		$ch			= curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$stream	= curl_exec($ch);
		curl_close($ch);
		return $stream;
	}
}

include __DIR__ . '/captcha-breaker.php';
for($i = 1; $i <= 10; $i++){
	$stream	= getImage( $url );
	$img	= imagecreatefromstring($stream);
	if(!$img){
		echo '<h3>Fail read image</h3>';
		continue;
	}
	$text	= breakIt($img);
	printf(
		'<tr><td>%s</td><td><img src="data:image/png;base64,%s"/></td><td>%s</td></tr>',
		$i,
		urlencode(base64_encode($stream)),
		$text
	);
}
?>
</table>
</body>
</html>