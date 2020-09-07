<?php
require_once('config.php');
class WLNUpdates extends SitePlugin
{
	public const FOLDER = 'wlnupdates/';
	
	public function __construct()
	{
		
	}
	
	public function login(string $user, string $pass)
	{
		$ar=array(
			'mode'=>'do-login',
			'username'=>$user,
			'password'=>$pass,
			'remember_me'=>true,
		);
		$res = $this->send( 'https://www.wlnupdates.com/api', json_encode($ar), array('Content-Type: Application/json') );
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'login.json', $res);
		return $res;
	}
	
	public function login2(string $user, string $pass)
	{
		$res = $this->send( 'https://www.wlnupdates.com/login' );
		file_put_contents($this::FOLDER.'login1.htm', $res);
		
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
		file_put_contents($this::FOLDER.'login2.htm', $res);
		
		preg_match('#<div class="alert alert-info">(.*)</div>#isU', $res, $matches2);
		if(count($matches2)==0) {
			return false;
		}
		$res2=trim(preg_replace("(<([a-z]+)([^>]*)>.*?</\\1>)is","",$matches2[1]));
		if($res2=='You have logged in successfully.') return true;
		
		return false;
	}
	
	public function watches()
	{
		$res = $this->send( 'https://www.wlnupdates.com/api', '{"mode":"get-watches"}', array('Content-Type: Application/json') );
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'watches.json', $res);
		return $res;
	}
	
	public function watches2()
	{
		$res = $this->send( 'https://www.wlnupdates.com/watches' );
		file_put_contents($this::FOLDER.'watches.htm', $res);
		return $res;
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
		$ar=array();
		if(array_key_exists('chp', $watch)) {
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
		}
		//else if(array_key_exists(0, $watch) && array_key_exists('id', $watch[0])) {
		else if(array_key_exists(0, $watch) && property_exists($watch[0], 'id')) {
			if((int)$watch[1]->vol <0) $watch[1]->vol =0;
			if((int)$watch[1]->chp <0) $watch[1]->chp =0;
			if((int)$watch[1]->frag<0) $watch[1]->frag=0;
			$ar=array(
				'mode'=>'read-update',
				'item-id'=>(int)$watch[0]->id,
				'vol' =>(int)$watch[1]->vol,
				'chp' =>(int)$chp,
				'frag'=>(int)$watch[1]->frag,
			);
		}
		$res = $this->send( 'https://www.wlnupdates.com/api', json_encode($ar), array('Content-Type:Application/json') );
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'read-update.json', $res);
		return $res;
	}
	
	public function search($name)
	{
		$ar=array(
			'mode'=>'search-title',
			'title'=>$name,
		);
		$res = $this->send( 'https://www.wlnupdates.com/api',json_encode($ar), array('Content-Type:Application/json') );
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'search-title.json', $res);
		return $res;
	}
	
	public function add($extract)
	{
		$ar=array(
			'name'=>$extract['title'],
			'type'=>'translated or eol',
		);
		//csrf_token
		$res = $this->send( 'https://www.wlnupdates.com/add/series', $ar);
		file_put_contents($this::FOLDER.'add-series.json', $res);
	}
};
?>