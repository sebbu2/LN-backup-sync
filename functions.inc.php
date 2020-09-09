<?php
require_once('config.php');
function print_thead_key($key,$value,$prefix='') {
	if(is_string($value)||is_numeric($value)||is_bool($value)||is_null($value)) {
		if($prefix==0&&$key=='extra_metadata') {
			echo "\t\t".'<th>0->extra_metadata->is_yaoi</th>'."\r\n";
			echo "\t\t".'<th>0->extra_metadata->is_yuri</th>'."\r\n";
		}
		else echo "\t\t".'<th>'.(strlen($prefix)>0?$prefix.'->':'').$key.'</th>'."\r\n";
	}
	else if(is_array($value)) {
		foreach($value as $k2=>$v2)
		{
			print_thead_key($k2,$v2,(strlen($prefix>0)?$prefix.'->':'').$key);
		}
	}
	else if(is_object($value)) {
		foreach(get_object_vars($value) as $k2=>$v2)
		{
			print_thead_key($k2,$v2,(strlen($prefix)>0?$prefix.'->':'').$key);
		}
	}
	else {
		var_dump($key,$value,$prefix);die();
	}
}
function print_tbody_value($key,$value,$prefix='') {
	//var_dump($value, is_string($value), is_numeric($value), is_bool($value), is_null($value), is_array($value), is_object($value));
	if(is_string($value)||is_numeric($value)||is_bool($value)||is_null($value)) {
		if($prefix==0&&$key=='extra_metadata') {
			echo "\t\t".'<td></td>'."\r\n";
			echo "\t\t".'<td></td>'."\r\n";
		}
		else echo "\t\t".'<td>'.strval($value).'</td>'."\r\n";
	}
	else if(is_array($value)) {
		foreach($value as $k2=>$v2)
		{
			print_tbody_value($k2,$v2,(strlen($prefix>0)?$prefix.'->':'').$key);
		}
	}
	else if(is_object($value)) {
		foreach(get_object_vars($value) as $k2=>$v2)
		{
			print_tbody_value($k2,$v2,(strlen($prefix>0)?$prefix.'->':'').$key);
		}
	}
	else {
		var_dump($value);die();
	}
}
function print_table($ar)
{
	if(!is_array($ar)) return;
	if(count($ar)==0) return;
	
	$keys=NULL;
	//if(is_array($ar[0])) $keys=array_keys($ar[0]);
	//else if( is_object($ar[0]) && (get_class($ar[0])=='stdClass') ) $keys=array_keys(get_object_vars($ar[0]));
	if(is_array($ar[0])) $keys=$ar[0];
	else if( is_object($ar[0]) && (get_class($ar[0])=='stdClass') ) $keys=get_object_vars($ar[0]);
	
	echo '<table border="1">'."\r\n";
	//keys
	echo "\t".'<tr>'."\r\n";
	foreach($keys as $k2=>$v2) {
		print_thead_key($k2,$v2);
	}
	echo "\t".'</tr>'."\r\n";
	//values
	foreach($ar as $k=>$v)
	{
		$values=NULL;
		if(is_array($v)) $values=$v;
		else if( is_object($v) && (get_class($v)=='stdClass') ) $values=get_object_vars($v);
		
		echo "\t".'<tr>'."\r\n";
		foreach($values as $k2=>$v2)
		{
			print_tbody_value($k2,$v2);
		}
		echo "\t".'</tr>'."\r\n";
	}
	echo '</table>'."\r\n";
}
function name_simplify($name) {
	static $ar1=array('_','-', ':', ',', '  ');
	static $ar2=array('\'', '&#39;', '\u2019', "\u2019", '’', "\xE2\x80\x99", '&rsquo;', '&lsquo;', '?', '!', '(', ')', 'Retranslated Version');
	$name1=mb_convert_encoding($name, 'HTML-ENTITIES',  'UTF-8');
	$name=str_replace($ar1, ' ', $name);
	$name=str_replace($ar2, '', $name);
	$name1=trim($name1);
	$name1=strtolower($name1);
	return $name;
}
function name_compare($name1, $name2)
{
	static $ar1=array('_','-', ':', ',', '  ');
	static $ar2=array('\'', '&#39;', '\u2019', "\u2019", '’', "\xE2\x80\x99", '&rsquo;', '&lsquo;', '?', '!', '(', ')', 'Retranslated Version');
	$name1=mb_convert_encoding($name1, 'HTML-ENTITIES',  'UTF-8');
	$name2=mb_convert_encoding($name2, 'HTML-ENTITIES',  'UTF-8');
	$name1=str_replace($ar1, ' ', $name1);
	$name1=str_replace($ar2, '', $name1);
	$name2=str_replace($ar1, ' ', $name2);
	$name2=str_replace($ar2, '', $name2);
	$name1=trim($name1);
	$name2=trim($name2);
	$name1=strtolower($name1);
	$name2=strtolower($name2);
	//var_dump($name1,$name2);
	return (strcasecmp($name1, $name2)==0);
}
function startswith($haystack, $needle) {
	return (strncasecmp($haystack, $needle, strlen($needle))==0);
}
function endswith($haystack, $needle) {
	return (strncasecmp(substr($haystack, -strlen($needle)), $needle, strlen($needle))==0);
}
function millitime() {
	list($usec, $sec) = explode(' ', microtime());
	return $sec.substr($usec, 2, 3);
}
function simplexml_load_html($html) {
	libxml_use_internal_errors(true);
	$doc = new DOMDocument();
	$doc->strictErrorChecking = FALSE;
	$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES',  'UTF-8'));
	libxml_use_internal_errors(false);
	$xml = simplexml_import_dom($doc);
	return $xml;
}
class SitePlugin
{
	public $lastUrl='';
	public $lastCookies='';
	public $lastHeaders='';
	
	private $headersSent=array();
	private $headersRecv=array();
	
	public function __construct()
	{
		
	}
	
	protected function get_cookies_for($url)
	{
		static $keys=array('domain','includeSubdomains','path','secureOnly','expires','name','value');
		$url_ar=parse_url($url);//scheme, host, path
		$ar=file(COOKIEFILE);
		$res=array();
		foreach($ar as $line) {
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
			if($ar2['expires']<time()) continue; // expired cookie
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
	
	protected function get($url, $parameters=array(), $headers=array(), $cookies=array())
	{
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
		if(DEBUG_HTTP) var_dump($url);
		$data=@file_get_contents($url,false,$ctx);
		if($data===false) return false;
		if(DEBUG_HTTP) var_dump($http_response_header);
		foreach ($http_response_header as $hdr) {
			if (preg_match('/^Set-Cookie:\s*(?:(?P<name>[^=]+)=(?P<value>[^;]*))(?:; expires=(?P<expires>\w+, \d+-\w+-\d+ \d+:\d+:\d+ [^;]+)|; path=(?P<path>[^;]+)|; domain=(?P<domain>[^;]+)|; (?P<secure>Secure)|; (?P<httponly>HttpOnly)|; SameSite=(?P<samesite>[^;]+))*$/', $hdr, $matches)) {
				$this->set_cookies_for($url, $matches);
			}
		}
		return $data;
	}
	
	private function print_headers($ch, $header)
	{
		//if(strlen(trim($header))==0) return strlen($header);
		$this->headersRecv[]=trim($header);
		return strlen($header);
	}
	
	protected function send($url, $postdata=array(), $headers=array(), $cookies=array())
	{
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
		if(!is_null($headers)&&!empty($headers))
		{
			if(!is_array($headers)) $headers=array($headers);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if(DEBUG_HTTP) var_dump($url);
		
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1 );
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(&$this, 'print_headers'));
		
		$res = curl_exec($ch);
		
		$this->headersSent = curl_getinfo($ch, CURLINFO_HEADER_OUT ); // request headers
		$this->headersSent = explode("\r\n", $this->headersSent);
		if(DEBUG_HTTP) {
			foreach($this->headersSent as $h)
			{
				var_dump('>> '.trim($h));
			}
			foreach($this->headersRecv as $h)
			{
				var_dump('<< '.trim($h));
			}
		}
		
		return $res;
	}
	
	public function jsonp_to_json($jsonp)
	{
		$jsonp=trim($jsonp);
		//$res=preg_replace('#\w+\((.*)\)#iU','\1', $jsonp);
		if(!in_array($jsonp[0], array('[','{'))) {
			//$jsonp=explode('(', $jsonp); array_shift($jsonp); $jsonp=implode('(',$jsonp); // remove before first (
			$jsonp=substr($jsonp, strpos($jsonp, '(')+1);
			//$jsonp=explode(')',$jsonp); array_pop($jsonp); $jsonp=implode(')',$jsonp); // remove after last )
			$jsonp=substr($jsonp, 0, strrpos($jsonp, ')'));
		} //*/
		$jsonp=trim($jsonp);
		static $end_match=array('['=>']','{'=>'}');
		assert(in_array($jsonp[0], array('[','{'))) or die('invalid JSON 1.<br/>'.$jsonp);
		assert(substr($jsonp,-1)==$end_match[$jsonp[0]]) or die('invalid JSON 2.<br/>'.$jsonp);
		$jsonp=json_encode(json_decode($jsonp), JSON_PRETTY_PRINT);
		//$jsonp=str_replace('    ',"\t",$jsonp); // TAB are invalid in json
		$jsonp=str_replace('    ', '  ', $jsonp);
		return $jsonp;
	}
}
