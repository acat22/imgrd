<?php

$url = urldecode($_REQUEST['url']);
$params = array(
	'status'=>'error',
	'error' => 'nourl',
	'url' => $url
);

if (!$url) exit(json_encode($params));



require '../ird/imageremotedownloader.class.php';

$idl = new IRD\ImageRemoteDownloader;

ob_implicit_flush(true);

$counter = 0;
function loadProgress($percent) {
	global $counter;
	$counter++;
	$params = array(
		'status'=>'inprogress',
		'p' => round($percent * 97)
	);
	echo json_encode($params)."\n";
	ob_flush();
    flush();
}

$opts = array(
	'progress' => 'loadProgress'
);

$img = $idl->load($url, $opts);

$params = array(
	'status'=>'error'
);

if ($img['status']) {
	$uplurl = 'progress.'.$img['imgtype'];
	$params = array(
		'status'=>'complete',
		'url'=>$uplurl,
		'counter' => $counter
	);
	file_put_contents($uplurl, $img['data']);
}

echo json_encode($params)."\n";
ob_flush();
flush();
