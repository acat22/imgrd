Image Remote Downloader for PHP
===============================

The class for remote downloading JPEG, GIF, PNG.  
Supports onProgress callback.  
Supports check without downloading.  
Can break through several anti-hotlinking protections (it's okay, since we don't do hotlinking), 
except expiration key.  
Requires CURL.  
Execution in background see explained below.  

Why to use 
---------
When you want to add uploading image via a link on your website.


Usage:
------
	$imageUrl = 'https://www.google.com/images/srpr/logo11w.png'; // your image link
	
	$idl = new IRD\ImageRemoteDownloader;  
	$data = $idl->load($imageUrl);  
	
	if ($data['status']) {
		echo 'Filesize: '.$data['size'].' bytes<br/>';
		echo 'Image type: '.$data['imgtype'].'<br/>';
		echo 'Raw mime-type: '.$data['mimetype'].'<br/>';
		$filename = '/tmp/downloadedimage'; // your filename
		file_put_contents($filename.'.'.$data['imgtype'], $data['data']);
	}
	
will do:

	Filesize: 14022 bytes
	Image type: png
	Raw mime-type: image/png
	
and store downloadedimage.png in the folder "/tmp/"

returns associative array (  
  *status* - true or false  
  *imgtype* - jpg / gif / png / empty if failed  
  *mimetype* - raw mimetype output  
  *size* - size in bytes  
  *data* - image, you can save it on disc via file_put_contents or do something else  
)  

with options:
-------------
	$opts = array(  
		'referrer' => 'https://www.google.com/',
		'checkonly' => 1  
	);  
	$imageUrl = 'https://www.google.com/images/srpr/logo11w.png';
	$data = $idl->load($imageUrl, $opts); // only checking if the image can be downloaded and get its type and size  
	if ($data['status']) {
		echo 'Filesize: '.$data['size'].' bytes<br/>';
		echo 'Image type: '.$data['imgtype'].'<br/>';
		echo 'Raw mime-type: '.$data['mimetype'].'<br/>';
	}
	
output:

	Filesize: 14022 bytes
	Image type: png
	Raw mime-type: image/png

Options:  
**url** - first param, the full url of the image,  
options:  
**referrer** - url of the webpage where the image was found. recommended to use. if you know it, provide.  
**check** - check image before downloading, default: false.  
It returns image type, image size and raw mime-type output.  
If set to true and the check fails, then load operation won't be executed.  
**checkonly** - check the image without downloading, default: false.  
**cookies** - use cookies, default: true. Put to false for better performance, but some websites may refuse to give you the image.  
**cookiefile** - full path to your cookiefile  
**progress** - pass here the name of the function to receive current loading progress   
or, in the case you want to call a class method, pass the following: array(&$classInstance, 'classMethod')  
eg. array(&$this, 'loadProgress'). The method should be public.    
It takes one parameter $percent, which is a numeric value between 0 and 1 (0.0132123, 0.80219239, etc.), eg. 

	function loadProgress($percent) {
		echo '<p>'.round($percent * 100).'</p>';
	}  
	
or 

	class myProgressTestClass {
		public function __construct($url) {
			$opts = array(
				'progress' => array(&$this, 'loadProgress')
			);
			$idl1 = new IRD\ImageRemoteDownloader;
			$test = $idl1->load($url, $opts);
		}
		
		public function loadProgress($percent) {
			echo '<p>'.round($percent * 100).'</p>';
		}
	}

	$t = new myProgressTestClass('https://www.google.com/images/srpr/logo11w.png');
	
**rawprogress** - return raw output for the progress function, default: false.  
If set to true, your progress function should be:
	
	function loadProgress($totalBytesToDownload, $downloaded, $totalBytesToUpload, $uploaded) 
	
	
Run in background
-----------------
When you don't want to wait until the script finishes.  
For Linux systems, create a PHP script and put it into your script executable directory.  
In your main code where you call ImageRemoteDownloader, put these lines instead:
	
	$params = array{
		'url' => 'image url here',
		'dbID' => 'some db id to call'
		// etc., well, whatever params you need, just don't make it too long
	);
	$fullScriptPath = '/var/www/scripts/download.php'; // full path to your script
	exec('/usr/bin/php '.$fullScriptPath.' "'.serialize($params).'" > /dev/null 2>/dev/null &');

	
In the script ('download.php') add the following code:  
	
	<?php
	$params = unserialize($argv[1]);
	
	// and do your stuff here
	// example:
	// call here all require, autoload, etc.
	
	$idl = new IRD\ImageRemoteDownloader;
	$data = $idl->load($params['url']);
	
	if ($data['status']) {
		// save image
		file_put_contents($filename.'.'.$data['imgtype'], $data['data']);
		// call db and write there using your $params['dbID'], etc.
	}
	
Now it works in background.
	


Copyright 2015. Licensed under GPL v 2.
https://github.com/acat22/imgrd
