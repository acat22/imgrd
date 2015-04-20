Image Remote Downloader for PHP
===============================

The class for remote downloading JPEG, GIF, PNG.  
Supports onProgress callback.  
Supports check without downloading.  
Can break through several anti-hotlinking protections (it's okay, since we don't do hotlinking), 
except expiration key.
Requires CURL.

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
		$filename = 'downloadedimage'; // your filename
		file_put_contents($filename.'.'.$data['imgtype'], $data['data']);
	}

returns associative array (  
  *status* - true or false  
  *imgtype* - jpg / gif / png / none if failed  
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
eg. array(&$this, 'loadProgress')  
It takes one parameter $percent, which is a numeric value between 0 and 1 (0.0132123, 0.80219239, etc.), eg. 

	function loadProgress($percent) {
		echo '<p>'.round($percent100).'</p>';
	}  
	
**rawprogress** - return raw output for the progress function, default: false  

Copyright 2015. Licensed under GPL v 2.
https://github.com/acat22/imgrd
