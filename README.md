Image Remote Downloader for PHP
===============================

Usage:
------
	$idl = new IRD\ImageRemoteDownloader;  
	$data = $idl->load($imageUrl);  

returns associative array (  
 status - true or false  
 imgtype - jpg / gif / png / none  
 mimetype - raw mimetype output  
 size - size in bytes  
 data - image, you can save it on disc via file_put_contents or do something else  
)  

with options:
-------------
	$opts = array(  
		'check' => 1,  
		 'load' => 0  
	);  
	$data = $idl->load($imageUrl, $opts); // only checking if the image can be downloaded and get its type and size  

Options:  
**url** - first param, the full url of the image,  
options:  
**referrer** - the webpage where the image was seen. if you know it, provide  
**load** - if to download the image or not, default: true.   
Put it to false only with 'check: true' when you want only to check the image.  
**check** - check image before downloading, default: false.  
If set to true and the check fails, then load operation won't be executed.  
**cookies** - use cookies, default: true. Put to false for better performance, but some websites may refuse to give you the image.  
**cookiefile** - full path to your cookiefile  
**progress** - pass here the name of the function to receive current loading progress   
or, in the case you want to call a class method, pass the following: array(&$classInstance, 'classMethod')  
eg. array(&$this, 'loadProgress')  
It takes one parameter $percent, which is a numeric value between 0 and 1 (0.0132123, 0.80219239, etc.), eg. 

---------------------
	function loadProgress($percent) {
		echo '<p>'.round($percent100).'</p>';
	}  
	
**rawprogress** - return raw output for the progress function, default: false  

Copyright 2015. Licensed under GPL v 2.
https://github.com/acat22/imgrd
