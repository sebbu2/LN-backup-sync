<?php
require_once('config.php');
class WLNUpdates
{
	public function __construct()
	{
		
	}
	
	private function get($url)
	{
		$arr=array(
			'http'=>array(
				'verify_peer'=>true,
				'allow_self_signed'=>true,
				'ignore_errors'=>true,
				//'user_agent'=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)',
				'header'=>'X-Requested-With: JSONHttpRequest',
			),
			'https'=>array(
				'verify_peer'=>true,
				'allow_self_signed'=>true,
				'ignore_errors'=>true,
				//'user_agent'=>'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)',
				'header'=>'X-Requested-With: JSONHttpRequest',
			),
		);
		$ctx=stream_context_create($arr);
		$data=@file_get_contents($url,false,$ctx);
		if($data===false) return false;
		var_dump($data);
		return $data;
	}
	
	private function send($url, $postdata=array(), $headers=array())
	{
		$ch = curl_init();
		$cookies = COOKIEFILE;
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			$cookies = str_replace('\\','/', getcwd().'/'.$cookies);
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
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
		$res = curl_exec($ch);
		return $res;
	}
	
	public function login(string $user, string $pass)
	{
		$res = $this->send( 'https://www.wlnupdates.com/login' );
		file_put_contents('login1.htm', $res);
		preg_match_all('#<form(?:\s+(?:id=["\'](?P<id>[^"\'<>]*)["\']|action=["\'](?P<action>[^"\'<>]*)["\']|\w+=["\'][^"\'<>]*["\']|))+>(?P<content>.*)</form>#isU', $res, $matches);
		if(count($matches['action'])==1) {
			preg_match('#<div class="alert alert-info">(.*)</div>#isU', $res, $matches2);
			$res2=trim(preg_replace("(<([a-z]+)([^>]*)>.*?</\\1>)is","",$matches2[1]));
			if($res2=='You are already logged in.') return true;
			var_dump($matches, $matches2);die();
		}
		$id=array_search('/login', $matches['action']);
		if($id===false) $id=array_search('', $matches['action']);
		assert($id!==false) or die(var_export($matches['action']));
		preg_match_all('#<input(?:\s+(?:name=["\'](?P<name>[^"\'<>]*)["\']|type=["\'](?P<type>[^"\'<>]*)["\']|value=["\'](?P<value>[^"\'<>]*)["\']|\w+=["\'][^"\'<>]*["\']|\w+))+>#is', $matches['content'][$id], $matches2);
		$postdata=array();
		foreach($matches2['name'] as $id2=>$name) {
			if(in_array($name, array('login','user','username','email'))) $postdata[$name]=$user;
			else if(in_array($name, array('pass','password'))) $postdata[$name]=$pass;
			else if(!empty($name)) $postdata[$name]=$matches2['value'][$id2];
		}
		$res = $this->send( 'https://www.wlnupdates.com/login', $postdata );
		file_put_contents('login2.htm', $res);
		preg_match('#<div class="alert alert-info">(.*)</div>#isU', $res, $matches2);
		if(count($matches2)==0) return false;
		$res2=trim(preg_replace("(<([a-z]+)([^>]*)>.*?</\\1>)is","",$matches2[1]));
		if($res2=='You have logged in successfully.') return true;
		var_dump($matches);
		return false;
	}
	
	public function watches()
	{
		$data = $this->send( 'https://www.wlnupdates.com/api', '{"mode":"get-watches"}', array('Content-Type:Application/json') );
		file_put_contents('watches.json', $data);
		return $data;
	}
	
	public function watches2()
	{
		$data = $this->send( 'https://www.wlnupdates.com/watches' );
		file_put_contents('watches.htm', $data);
		return $data;
	}
	
	public function watches2_lists($data)
	{
		$lists=array();
		preg_match_all('#<h4>List: ([^<]+)</h4>\s*<table(.*)</table>#isU', $data, $matches);
		array_shift($matches);//remove original full match
		foreach($matches[0] as $k=>$v)
		{
			$lists[$v]=array();
			preg_match_all('#<tr(.*)</tr>#isU', $matches[1][$k], $matches2);
			array_shift($matches2);array_shift($matches2[0]);//remove original full match + table header
			$matches2=$matches2[0];
			foreach($matches2 as $k2=>$v2)
			{
				$ar=array();
				preg_match('#<a href=\'/series-id/(?P<id>\d+)/\'>\s*?(?P<title>.*)\s*</a>#isU', $v2, $matches3);
				$matches3=array_diff_key($matches3, range(0,2)); // remove non-assoc keys
				$ar=array_merge($ar, $matches3);
				$resx=preg_match_all('#(?:data-availvol=\'(?P<availvol>-?[0-9.]+)\'|data-availchp=\'(?P<availchp>-?[0-9.]+)\'|data-availfrag=\'(?P<availfrag>-?[0-9.]+)\'|data-vol=\'(?P<vol>-?[0-9.]+)\'|data-chp=\'(?P<chp>-?[0-9.]+)\'|data-frg=\'(?P<frg>-?[0-9.]+)\'|)#isU', $v2, $matches3);
				foreach($matches3 as $k3=>$v3) {
					$tmp=array_values(array_filter($v3));
					if(count($tmp)==0||!is_array($tmp)) { $tmp=NULL; }
					if(is_array($tmp)) $matches3[$k3]=$tmp[0];
					else $matches3[$k3]=$tmp;
				}
				$matches3=array_diff_key($matches3, range(0,6)); // remove non-assoc keys
				$ar=array_merge($ar, $matches3);
				$lists[$v][]=$ar;
			}
		}
		return $lists;
	}
	
	public function read_update($watch, $chp)
	{
		if((int)$watch['vol']<0) $watch['vol']=0;
		if((int)$watch['chp']<0) $watch['chp']=0;
		if((int)$watch['frg']<0) $watch['frg']=0;
		$ar=array(
			'mode'=>'read-update',
			'item-id'=>(int)$watch['id'],
			'vol'=>(int)$watch['vol'],
			'chp'=>(int)$chp,
			'frag'=>(int)$watch['frg'],
		);
		$data = $this->send( 'https://www.wlnupdates.com/api', json_encode($ar), array('Content-Type:Application/json') );
		file_put_contents('read-update.json', $data);
		$data=trim($data);
		return $data;
	}
};
?>