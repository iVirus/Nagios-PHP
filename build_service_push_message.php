#!/usr/bin/php
<?php
$baseUrl = 'https://your.external.url/noauth';
$apiToken = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
$shortopts = 'h:d:s:l:o:c:a:t:v:';
$longopts = array('hostname:', 'servicedesc:', 'servicestate:', 'datetime:', 'serviceoutput:', 'notificationcomment:', 'notificationauthor:', 'contactalias:', 'includelinks:');
$options = getopt($shortopts, $longopts);

foreach ($options as $k => $v) {
	switch ($k) {
		case 'h':
		case 'hostname':
			$hostname = $v;
			break;
		case 'd':
		case 'servicedesc':
			$servicedesc = $v;
			break;
		case 's':
		case 'servicestate':
			$servicestate = $v;
			break;
		case 'l':
		case 'datetime':
			$datetime = $v;
			break;
		case 'o':
		case 'serviceoutput':
			$serviceoutput = $v;
			break;
		case 'c':
		case 'notificationcomment':
			$notificationcomment = $v;
			break;
		case 'a':
		case 'notificationauthor':
			$notificationauthor = $v;
			break;
		case 't':
		case 'contactalias':
			$contactalias = $v;
			break;
		case 'v':
		case 'includelinks':
			$includelinks = $v;
			break;
	}
}

function shortenUrl($longUrl) {
	global $apiToken;

	$apiUrl = 'http://api.isus.cc';
	$longUrl = preg_replace('/ /','+', $longUrl);

	$ch = curl_init();

	$fields = array(
		'token' => $apiToken,
		'url' => $longUrl
	);

	$curlopts = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_CONNECTTIMEOUT => 1,
		CURLOPT_POSTFIELDS => http_build_query($fields),
		CURLOPT_TIMEOUT => 2,
		CURLOPT_POST => true,
		CURLOPT_URL => $apiUrl
	);
	curl_setopt_array($ch, $curlopts);

	$shortUrl = curl_exec($ch);

	curl_close($ch);

	if ($shortUrl !== FALSE) {
		$shortUrl = json_decode($shortUrl);

		if ($shortUrl->status_code == 200) {
			return $shortUrl->data->url;
		}
	}

	return $longUrl;
}

if ($notificationcomment && $notificationauthor) {
	$additional = <<<EOM
Comment: {$notificationcomment}
Author: {$notificationauthor}
EOM;
} elseif ($includelinks) {
	$acknowledgeUrl = shortenUrl(sprintf('%s/?cmd=40&host_name=%s&sticky=0&service_description=%s&author=%s&comment=Problem acknowledged', $baseUrl, $hostname, $servicedesc, $contactalias));
	$availableUrl = shortenUrl(sprintf('%s/?cmd=135&host_name=%s&service_description=%s&options=1&author=%s&comment=Available to help', $baseUrl, $hostname, $servicedesc, $contactalias));
	$unavailableUrl = shortenUrl(sprintf('%s/?cmd=135&host_name=%s&service_description=%s&options=1&author=%s&comment=Currently unavailable', $baseUrl, $hostname, $servicedesc, $contactalias));
	$helpUrl = shortenUrl(sprintf('%s/?cmd=135&host_name=%s&service_description=%s&options=1&author=%s&comment=Need help', $baseUrl, $hostname, $servicedesc, $contactalias));

	$additional = <<<EOM
Acknowledge: {$acknowledgeUrl}

Available: {$availableUrl}

Unavailable: {$unavailableUrl}

Need Help: {$helpUrl}
EOM;
}

$message = <<<EOM
{$hostname}/{$servicedesc} is {$servicestate}
Time: {$datetime}

Info: {$serviceoutput}

{$additional}
EOM;

echo $message;
?>
