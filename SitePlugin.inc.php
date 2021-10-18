<?php
require_once('config.php');
require_once('functions.inc.php');

require_once('CJSON.php');

class SitePlugin
{
	public $lastUrl='';
	public $lastCookies='';
	public $lastHeaders='';
	
	public $headersSent=array();
	public $headersRecv=array();
	
	public $msg=array();
	
	public function __construct()
	{
		
	}
	
	protected function get_cookies_for($url) {
		static $keys=array('domain','includeSubdomains','path','secureOnly','expires','name','value');
		$url_ar=parse_url($url);//scheme, host, path
		if(!array_key_exists('scheme', $url_ar)) { var_dump($url, $url_ar);var_dump(debug_backtrace());die(); }
		$ar=file(COOKIEFILE);
		$res=array();
		foreach($ar as $line) {
			if(substr($line, 0, 2)=='# '||strlen(trim($line))==0) continue;
			$ar2=explode("\t", $line);
			$ar2=array_combine($keys, $ar2);
			$domain=$ar2['domain'];
			if(substr($domain,0,10)=='#HttpOnly_') {
				// TODO : http-only cookie is not for XHR
				$domain=substr($domain,10);
			}
			if($ar2['secureOnly']=='TRUE' && $url_ar['scheme']=='http') continue; // secureOnly cookie on http url
			if(!endswith($url_ar['host'], $domain)) continue; // other domain
			if($url_ar['host'] != $domain && $ar2['includeSubdomains']=='FALSE') continue; // cookie only for a specific domain, without subdomains
			if(!startswith($url_ar['path'], $ar2['path'])) continue; // cookie for another path
			if($ar2['expires']!='0' && $ar2['expires']<time()) continue; // expired cookie
			$res[$ar2['name']]=trim($ar2['value']);
		}
		return $res;
	}
	
	protected function set_cookies_for($url, $cookie) {
		static $keys=array('domain','includeSubdomains','path','secureOnly','expires','name','value');
		$url_ar=parse_url($url);//scheme, host, path
		$ar=file(COOKIEFILE);
		$res=array();
		$found=false;
		//name, value, expires, path, domain, secure, httponly, samesite
		$line2=(isset($cookie['httponly'])?'#'.$cookie['httponly']:'').(!isset($cookie['samesite'])?'.':'').$cookie['domain']."\t".
			(!isset($cookie['samesite'])?'TRUE':'FALSE')."\t".
			$cookie['path']."\t".
			(isset($cookie['secure'])?'TRUE':'FALSE')."\t".
			(!empty($cookie['expires'])?strtotime($cookie['expires']):time()+31557600)."\t".
			$cookie['name']."\t".
			$cookie['value']."\r\n";
		foreach($ar as $i=>&$line) {
			if(substr($line, 0, 2)=='# '||strlen(trim($line))==0) continue;
			$ar2=explode("\t", $line);
			$ar2=array_combine($keys, $ar2);
			$domain=$ar2['domain'];
			if(substr($domain,0,10)=='#HttpOnly_') {
				if($url_ar['scheme']=='https') continue; // http-only cookie on https url
				$domain=substr($domain,10);
			}
			if($ar2['secureOnly']=='TRUE' && $url_ar['scheme']=='http') continue; // secureOnly cookie on http url
			if(!endswith($url_ar['host'], $domain)) continue; // other domain
			if($url_ar['host'] != $domain && $ar2['includeSubdomains']=='FALSE') continue; // cookie only for a specific domain, without subdomains
			if(!startswith($url_ar['path'], $ar2['path'])) continue; // cookie for another path
			//if($ar2['expires']>time()) continue; // expired cookie
			if($ar2['name']!=$cookie['name']) continue; // another cookie name
			//good cookie
			$found=true;
			if($ar2['expires']<time()) {
				unset($ar[$i]); // delete cookie
				if(DEBUG_COOKIE) var_dump('deleting '.$ar2['name']);
				continue;
			}
			//var_dump($line, $line2);//die();
			if(DEBUG_COOKIE) var_dump($line2);
			$line=$line2;
		}
		if(!$found) {
			if(empty($cookie['expires']) || strtotime($cookie['expires'])>time()) {
				if(DEBUG_COOKIE) var_dump($line2);
				$ar[]=$line2;
			}
		}
		file_put_contents(COOKIEFILE, implode('', $ar));
	}
	
	public function get($url, $parameters=array(), $headers=array(), $cookies=array()) {
		$arr=array(
			'http'=>array(
				'ignore_errors'=>true,
				//'user_agent'=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)',
				'header'=>array(
					//'X-Requested-With: JSONHttpRequest'
				),
			),
			'ssl'=>array(
				'verify_peer'=>true,
				'allow_self_signed'=>true,
				'ignore_errors'=>true,
			),
		);
		if(!is_null($headers)&&!empty($headers))
		{
			if(!is_array($headers)) $arr['http']['header'][]=$headers;
			else $arr['http']['header']=array_merge($arr['http']['header'], $headers);
			$this->lastHeaders=$headers;
		}
		if($cookies!==false && $cookies!==NULL) {
			$values=$this->get_cookies_for($url);
			if(is_string($cookies)) $cookies=array($cookies);
			$values=array_merge($values, $cookies);
			if(count($values)>0) {
				$values=http_build_query($values, '', '; ');
				$arr['http']['header'][]='Cookie: '.$values;
				$this->lastCookies=$values;
				if(DEBUG_COOKIE) var_dump($values);
			}
		}
		
		$ctx=stream_context_create($arr);
		if(is_array($parameters)&&count($parameters)>0) {
			$parameters=http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
			if(strpos($url, '?')===false) $url.='?'.$parameters;
			else $url.='&'.$parameters;
		}
		$this->lastUrl=$url;
		if(DEBUG_URL || DEBUG_HTTP) var_dump($url);
		$data=@file_get_contents($url,false,$ctx);
		if($data===false) return false;
		if(DEBUG_HTTP) var_dump($http_response_header);
		$this->headersRecv=$http_response_header;
		foreach ($http_response_header as $hdr) {
			if (preg_match('/^Set-Cookie:\s*(?:(?P<name>[^=]+)=(?P<value>[^;]*))(?:; expires=(?P<expires>\w+, \d+-\w+-\d+ \d+:\d+:\d+ [^;]+)|; path=(?P<path>[^;]+)|; domain=(?P<domain>[^;]+)|; (?P<secure>Secure)|; (?P<httponly>HttpOnly)|; SameSite=(?P<samesite>[^;]+))*$/', $hdr, $matches)) {
				$this->set_cookies_for($url, $matches);
			}
		}
		return $data;
	}
	
	private function print_headers($ch, $header) {
		//if(strlen(trim($header))==0) return strlen($header);
		$this->headersRecv[]=trim($header);
		return strlen($header); // mandatory (from API documentation)
	}
	
	public function send($url, $postdata=array(), $headers=array(), $cookies=array()) {
		$this->headersRecv=array();
		$this->headersSent=array();
		
		$ch = curl_init();
		$cookiesf = COOKIEFILE;
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			$cookiesf = str_replace('\\','/', getcwd().'/'.$cookiesf);
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		if(!is_null($cookies) && $cookies!==false) {
			if(is_array($cookies)) $cookies=http_build_query($cookies, '', '; ');
			curl_setopt($ch, CURLOPT_COOKIE, $cookies);
			
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiesf);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiesf);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if(!is_null($postdata)&&!empty($postdata)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			if(is_array($postdata)) $postdata=http_build_query($postdata, '', '&');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		}
		$this->lastUrl=$url;
		if(!is_null($headers)&&!empty($headers))
		{
			if(!is_array($headers)) $headers=array($headers);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if(DEBUG_URL || DEBUG_HTTP) var_dump($url);
		
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1 ); // headersSent
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(&$this, 'print_headers')); // headersRecv
		
		$res = curl_exec($ch);
		
		$this->headersSent = curl_getinfo($ch, CURLINFO_HEADER_OUT ); // request headers
		$this->headersSent = explode("\r\n", $this->headersSent);
		if(DEBUG_HTTP) {
			foreach($this->headersSent as $h)
			{
				var_dump('>> '.trim($h));
			}
		}
		if(DEBUG_POSTDATA) var_dump('postdata', $postdata);
		foreach($this->headersRecv as $h)
		{
			if(DEBUG_HTTP) {
				var_dump('<< '.trim($h));
			}
			else if(startswith($h, 'Location:')) {
				var_dump('Redirecting from \"'.$url.'\" to \"'.substr($h, 10).'\"<br/>'."\r\n");
			}
		}
		
		return $res;
	}
	
	public function jsonp_to_json($jsonp) {
		$jsonp=trim($jsonp);
		if(empty($jsonp)) throw new Exception('Invalid JSONP 0 : empty.<br/>');
		//$res=preg_replace('#\w+\((.*)\)#iU','\1', $jsonp);
		if(!in_array($jsonp[0], array('[','{'))) {
			//var_dump($jsonp);
			if(strpos($jsonp, '(')===false) throw new Exception('Invalid JSONP 0a: no (.<br/>');
			//$jsonp=explode('(', $jsonp); array_shift($jsonp); $jsonp=implode('(',$jsonp); // remove before first (
			$jsonp=substr($jsonp, strpos($jsonp, '(')+1);
			if(strpos($jsonp, ')')===false) throw new Exception('Invalid JSONP 0b: no ).<br/>');
			//$jsonp=explode(')',$jsonp); array_pop($jsonp); $jsonp=implode(')',$jsonp); // remove after last )
			$jsonp=substr($jsonp, 0, strrpos($jsonp, ')'));
			//var_dump($jsonp);
		} //*/
		$jsonp=trim($jsonp);
		if(! in_array($jsonp[0], array('[','{'))) throw new Exception('invalid JSONP 1.<br/>'.var_export(substr($jsonp,0,1),true));
		return $this->json_to_json($jsonp);
	}
	
	public function json_to_json($json) {
		static $end_match=array('['=>']','{'=>'}');
		if(empty($json)) throw new Eception('Invalid JSON 0 : empty.<br/>');
		if(! substr($json,-1)==$end_match[$json[0]]) throw new Exception('invalid JSON 1.<br/>'.var_export(substr($json,-1),true));
		//$json=json_encode(json_decode($json, false, 512, JSON_UNESCAPED_SLASHES), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK );
		$json=str_replace('\/','/',$json);
		$json=str_replace('\\\\','\\',$json);
		$json=str_replace('\ ', ' ', $json);
		$json=str_replace('\\\'', '\'', $json);
		$json=str_replace('\<', '<', $json);
		$json=str_replace('\>', '>', $json);
		$json=str_replace('\_', '_', $json);
		$json=str_replace('\n', "\n", $json);
		$json=str_replace('\r', '', $json);
		$json=str_replace('\&', '&', $json);
		$json=str_replace('[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]', '', $json);
		$json=str_replace('\\\'', '\'', $json);
		$json=str_replace('\:', '\\:', $json);
		$json=str_replace('\0', '\\0', $json);
		
		//setlocale(LC_ALL, 'en_US.UTF-8');
		//$json = preg_replace('/\\\\u([0-9a-f]+)/i', '&#x$1;', $json);
		//$json = html_entity_decode($json, ENT_QUOTES, 'UTF-8');
		
		//$json=transliterator_create('Hex-Any')->transliterate($json);
		$json=transliterator_create('Accents-Any')->transliterate($json);
		
		//var_dump($json);//die();
		$json1=$json;
		//$json1=iconv('UTF-8', 'UTF-8//TRANSLIT', $json1);
		//ini_set('mbstring.substitute_character', "none");
		//$json1= mb_convert_encoding($json1, 'UTF-8', 'UTF-8');
		$json1=mb_convert_encoding($json1, 'HTML-ENTITIES', 'UTF-8');
		//$json1=mb_convert_encoding($json1, 'HTML-ENTITIES', 'ISO-8859-1');
		$json1=str_replace('&larr;"&rarr;','&larr;\"&rarr;',$json1);
		try {
			$json2=json_decode($json1, false, 512, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR);
		}
		catch(JsonException $e) {
			$json1 = preg_replace('/[[:cntrl:]]/', '', $json1);
			$json1=str_replace('\:', '\\:', $json1);
			$json1=str_replace('\0', '\\0', $json1);
			file_put_contents('test2.json', $json1);
			//$json2=json_decode($json1, false, 512, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR);
			$json2=(new CJSON())->decode($json1);
		}
		//var_dump($json2);die();
		$json=json_encode($json2, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES|JSON_BIGINT_AS_STRING );
		//var_dump($json);die();
		//$json=str_replace('    ',"\t",$json); // TAB are invalid in json
		$json=str_replace('    ', '  ', $json);
		return $json;
	}
	
	public function get_watches() {
		throw new Exception('Not yet implemented.');
	}
	
	public function get_list() {
		throw new Exception('Not yet implemented.');
	}
	
	public function get_order() {
		throw new Exception('Not yet implemented.');
	}
	
	public function get_types() {
		$ar=array(
			'anime',
			'manga',
			'novel',
				'oel',
				'translated',
			'movie',
			'tvserie',
			'drama',
			'book',
			'bd',
			'comic',
			'videogame',
			'vn',
			'game',
			'music',
		);
		return $ar;
	}
	
	public function get_filter_for($type) {
		return function($watch) { return true; };
	}
}
?>