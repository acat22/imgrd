<?php
/**
 * ImageRemoteDownloader
 * 
 * Usage:
 * $idl = new IRD\ImageRemoteDownloader;
 * $data = $idl->load($imageUrl);
 * 
 * returns associative array (
 *  status - true or false
 *  imgtype - jpg / gif / png / none
 *  mimetype - raw mimetype output
 *  size - size in bytes
 *  data - image, you can save it on disc via file_put_contents or do something else
 * )
 * 
 * with options:
 * 
 * $opts = array(
 *  	'checkonly' => 1
 * );
 * $data = $idl->load($imageUrl, $opts); // only checking if the image can be downloaded and get its type and size
 * 
 * Options:
 * url - first param, the full url of the image,
 * options:
 * 'referrer' - the webpage where the image was seen. if you know it, provide
 * 'check' - check image before downloading, default: false. 
 * If set to true and the check fails, then load operation won't be executed.
 * 'checkonly' - default: false. Put it to true when you want only to check the image without downloading.
 * 'cookies' - use cookies, default: true. Put to false for better performance, but some websites may refuse to give you the image.
 * 'cookiefile' - full path to your cookiefile
 * 'progress' - pass here the name of the function to receive current loading progress 
 * or, in the case you want to call a class method, pass the following: array(&$classInstance, 'classMethod')
 * eg. array(&$this, 'loadProgress')
 * It takes one parameter $percent, which is a numeric value between 0 and 1 (0.0132123, 0.80219239, etc.), 
 * eg. function loadProgress($percent) { echo '<p>'.round($percent * 100).'</p>'; }
 * 'rawprogress' - return raw output for the progress function, default: false
 * 
 * Copyright 2015. Licensed under GPL v 2.
 * https://github.com/acat22/imgrd
 */ 
namespace IRD;
class ImageRemoteDownloader 
{
	
	protected $ckfile = ''; // cookie file
	protected $timeoutFC = 5; // timeout for filling cookies in seconds
	protected $timeoutLI = 40; // timeout for downloading in seconds
	protected $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)'; // user agent line
	protected $progressCallback = null; // progress callback
	protected $progressRaw = false; // raw progress callback
	protected $cookies = array();
	
	/**
	 * just a filler CURL for common usage
	 */
	protected function baseCURLConfig(&$ch) 
	{
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_AUTOREFERER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_FAILONERROR => true,
			CURLOPT_USERAGENT => $this->userAgent,
			CURLOPT_TIMEOUT => 120, // timeout on response
			CURLOPT_MAXREDIRS => 10, // stop after 10 redirects 
			CURLOPT_SSL_VERIFYHOST => 0, // don't verify ssl
			CURLOPT_SSL_VERIFYPEER => false
		));
		
		if ($this->ckfile) {
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->ckfile);
		} else 
		// fill cookies
		if (count($this->cookies) > 0){
			$ckline = array();
			foreach ($this->cookies as $n=>$v) {
				$ckline[] = $n.'='.$v;
			}
			curl_setopt($ch, CURLOPT_COOKIE, implode("; ",$ckline));
		}
	}
	
	/**
	 * Filling cookies if some.
	 * 
	 * @param string $url url of the page with the image
	 * 
	 */ 
	protected function fillCookies($url) 
	{
		$ch = curl_init($url);
		
		$this->baseCURLConfig($ch);
		curl_setopt_array($ch, array(
			CURLOPT_CONNECTTIMEOUT => $this->timeoutFC,
			CURLOPT_NOBODY => true, 
			CURLOPT_HEADER => true
		));
		
		if ($this->ckfile) {
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->ckfile);
		}
		
		$output = curl_exec($ch);
		
		if (!$this->ckfile) {
			// fill cookies
			$cookies = array();
			preg_match_all("/^Set-cookie: (.*?);/ism", $output, $matches);
			foreach ($matches[1] as $cookie){
				$pos = strpos($cookie, "=");
				$name = substr($cookie, 0, $pos);
				$value = substr($cookie, $pos + 1);
				$cookies[$name] = $value;
			}
			$this->cookies = $cookies;
		}
		
		curl_close($ch);
	}
	
	/**
	 * Check if the image can be downloaded and get its size and mime.
	 * Notice that some websites may return wrong mime or size or not return them at all.
	 * If the image is not jpg / gif / png, it fails.
	 * 
	 * @param string $url url of the image
	 * @param string $referrer parent url
	 * @return associative array(status, mimetype, filesize)
	 */ 
	protected function _checkImage($url, $referrer) 
	{
		$ch = curl_init($url);
		
		$this->baseCURLConfig($ch);
		curl_setopt_array($ch, array(
			CURLOPT_NOBODY => true, // it's a check, we don't need the actual thing
			CURLOPT_REFERER => $referrer, // referrer
			CURLOPT_CONNECTTIMEOUT => $this->timeoutLI
		));
		
		$data = curl_exec($ch);
		$mimetype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		
		curl_close($ch);
		
		$res = array('status' => false);
		
		$imgtype = $this->getImgType($mimetype);
		
		$res['mimetype'] = $mimetype;
		$res['imgtype'] = $imgtype;
		$res['size'] = $size;
		
		if ($imgtype) {
			$res['status'] = true;
		}
		
		return $res;
	}
	
	/**
	 * Download image
	 * 
	 * @param string $url url of the image
	 * @param string $referrer parent url
	 * @return associative array(status, rawdata)
	 */ 
	protected function _loadImage($url, $referrer) 
	{
		$ch = curl_init($url);
		
		$this->baseCURLConfig($ch);
		curl_setopt_array($ch, array(
			CURLOPT_REFERER => $referrer, // referrer
			CURLOPT_CONNECTTIMEOUT => $this->timeoutLI
		));
		
		if ($this->progressCallback) {
			if ($this->progressRaw) $func = $this->progressCallback; 
			else $func = array(&$this, 'setLoadProgress');
			curl_setopt_array($ch, array(
				CURLOPT_NOPROGRESS => false,
				CURLOPT_PROGRESSFUNCTION => $func
			));
		}
		
		$data = curl_exec($ch);
		$mimetype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		curl_close($ch);
		
		$res = array('status' => false);
		
		$imgtype = $this->getImgType($mimetype);
		
		$res['data'] = $data;
		$res['imgtype'] = $imgtype;
		$res['size'] = strlen($data);
		$res['mimetype'] = $mimetype;
		
		if ($imgtype) {
			$res['status'] = true;
		}
		
		return $res;
	}
	
	/**
	 * loading progress
	 * http://php.net/manual/en/function.curl-setopt.php search for CURLOPT_PROGRESSFUNCTION
	 * 
	 * calls users callback with 0-1 downloading progress state (eg. 0.05, 0.1, 0.39, etc.)
	 */ 
	//public function setLoadProgress($rs, $total, $downloaded, $uplTotal, $uploaded) // older version
	public function setLoadProgress($total, $downloaded, $uplTotal, $uploaded) 
	{
		$percent = 0;
		if ($total > 0) $percent = $downloaded / $total;
		call_user_func($this->progressCallback, $percent);
	}
	
	/**
	 * Process
	 * 
	 * @param string $url url of the page with the image
	 * @param array $opts options
	 * @return associative array(status, data (img body), imgtype (jpg, gif, png), size (in bytes), mimetype (raw mimetype as the site returned))
	 */ 
	public function load($url, array $opts = null) 
	{
		if (!$url) return false;
		$referrerP = parse_url($url);
		
		if (!$opts) $opts = array();
		
		$this->ckfile = '';
		$this->cookies = array();
		$useCookies = true;
		if (isset($opts['cookies']) && !$opts['cookies']) $useCookies = false;
		
		if (!$opts['cookiefile']) {
			/*if ($useCookies) {
				$tempFile = true;
				$this->ckfile = tempnam("./", "IMGRDC");
			}*/
		} else {
			$this->ckfile = $opts['cookiefile'];
		}
		
		if (!$opts['referrer']) {
			$domain = $referrerP['host'];
			$domain2 = explode('.', $domain);
			$c = count($domain2);
			if ($c > 2) {
				$domain = ($domain2[$c - 2]).'.'.($domain2[$c - 1]);
			}
			$referrer = $referrerP['scheme'].'://'.$domain.'/';
		} else {
			$referrer = $opts['referrer'];
		}
		
		$this->progressCallback = null;
		$this->progressRaw = false;
		if ($opts['progress']) {
			$this->progressCallback = $opts['progress'];
			if ($opts['rawprogress']) $this->progressRaw = true;
		}
		
		if ($useCookies) {
			$this->fillCookies($referrer);
		}
		
		$res = false;
		if ($opts['check'] || $opts['checkonly']) {
			$check = $this->_checkImage($url, $referrer);
			$res = $check;
		}
		if ($opts['checkonly']) {
		} else {
			if ($check && !$check['status']) {
			} else {
				$res = $this->_loadImage($url, $referrer);
			}
		}
		
		//if ($this->ckfile && $tempFile) unlink($this->ckfile);
		
		return $res;
	}
	
	/**
	 * Crude determining of the image type.
	 * It is CRUDE for a good reason: there is number of websites 
	 * that return corrupted mimetypes, which doesn't look like 
	 * normal 'image/png', etc.
	 * @param string $mimetype
	 * @return string extension : jpg / gif / png / nothing
	 */
	public function getImgType($mimetype) 
	{
		$ext = '';
		if (strpos($mimetype, 'jpeg') !== false) $ext = 'jpg';
		else if (strpos($mimetype, 'jpg') !== false) $ext = 'jpg';
		else if (strpos($mimetype, 'gif') !== false) $ext = 'gif';
		else if (strpos($mimetype, 'png') !== false) $ext = 'png';
		return $ext;
	}
}
