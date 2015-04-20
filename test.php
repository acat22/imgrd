<?php

require 'ird/imageremotedownloader.class.php';

$idl = new IRD\ImageRemoteDownloader;
/*
$opts = array(
	'referrer' => 'http://localhost/',
	'cookiefile' => 'ck.txt',
	'check' => 1,
	'load' => 0
);
$test = $idl->load('http://localhost/1287859150_37.jpg', $opts);
print_r($test);

*/

function loadProgress($percent) {
	echo '<p>'.($percent * 100).'</p>';
}
/*
$opts = array(
	'cookiefile' => 'C:/DevelopmentWWW/http/projects/imgrd/ck1.txt',
	'check' => 1,
	'progress' => 'loadProgress'
);
$test = $idl->load('http://s1.reafiny.com/imgs/1/32/6cfcdb149b5b7808.jpg', $opts);
*/
$opts = array(
	'cookiefile' => 'C:/DevelopmentWWW/http/projects/imgrd/tst/ck1.txt',
	'referrer' => 'http://www.dm5.com/m180392/',
	'check' => 0,
	'load' => 1
);

$test = $idl->load('http://manhua1023.95-211-197-186.cdndm5.com/14/13647/180392/1_2996.jpg?cid=180392&key=1fe7113ba485bcc257ad2143f62bd29d', $opts);

if ($test['status']) {
	echo $test['size'];
	file_put_contents('tst/dattest.'.$test['imgtype'], $test['data']);
} else print_r($test);
