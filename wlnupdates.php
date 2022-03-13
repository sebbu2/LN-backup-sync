<?php
require_once('config.php');
require_once('SitePlugin.inc.php');

class WLNUpdates extends SitePlugin
{
	public const FOLDER = 'wlnupdates/';
	public $LOGIN_TRIED = false;
	
	public function __construct() {
		
	}
	
	public function login(string $user, string $pass) {
		$ar=array(
			'mode'=>'do-login',
			'username'=>$user,
			'password'=>$pass,
			//'remember_me'=>true,
		);
		$res = $this->send( 'https://www.wlnupdates.com/api', json_encode($ar), array('Content-Type: application/json') );
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'login.json', $res);
		$res=json_decode($res);
		return $res;
	}
	
	public function login2(string $user, string $pass) {
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
	
	public function watches() {
		global $accounts;
		$res = $this->send( 'https://www.wlnupdates.com/api', '{"mode":"get-watches"}', array('Content-Type: application/json') );
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'watches.json', $res);
		$res=json_decode($res);
		
		if( !$this->LOGIN_TRIED && $res->error==true ) {
			$res=$this->login( $accounts['WLNUpdates']['user'], $accounts['WLNUpdates']['pass'] );
			$this->LOGIN_TRIED=true;
			if($res===false) die('you need to log in.');
			try {
				$res=json_decode($res);
				if(is_object($res) && $res->error==true) die($res->message);
			}
			catch(TypeError) {
				assert($res=='Logged in successfully');
			}
			$res = $this->watches();
		}
		$res2=$res->data[0];
		
		$res=array(); // id => details
		$list=array(); // id => list
		$order=array(); // list => [id]
		
		foreach($res2 as $l => $ar) {
			$lists[$l]=array();
			foreach($ar as $it) {
				$id = $it[0]->id;
				$res[$id]=$it;
				$order[$l][]=$id;
				$list[$id]=$l;
			}
		}
		ksort($res);
		ksort($list);
		
		file_put_contents($this::FOLDER.'_books.json', $this->jsonp_to_json(json_encode($res)) );
		file_put_contents($this::FOLDER.'_order.json', $this->jsonp_to_json(json_encode($order)) );
		file_put_contents($this::FOLDER.'_list.json', $this->jsonp_to_json(json_encode($list)) );
		
		return $res;
	}
	
	public function watches2() {
		$res = $this->send( 'https://www.wlnupdates.com/watches' );
		file_put_contents($this::FOLDER.'watches.htm', $res);
		return $res;
	}
	
	public function watches2_lists($data) {
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
	
	public function get_watches() {
		$res=json_decode(file_get_contents($this::FOLDER.'_books.json'), false, 512, JSON_THROW_ON_ERROR);
		if(is_object($res)) $res=get_object_vars($res);
		return $res;
	}
	
	public function get_list() {
		$res=json_decode(file_get_contents($this::FOLDER.'_list.json'), false, 512, JSON_THROW_ON_ERROR);
		if(is_object($res)) $res=get_object_vars($res);
		return $res;
	}
	
	public function get_order() {
		$res=json_decode(file_get_contents($this::FOLDER.'_order.json'), false, 512, JSON_THROW_ON_ERROR);
		if(is_object($res)) $res=get_object_vars($res);
		return $res;
	}
	
	public function read_update($watch, $chp) {
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
		$res = $this->send( 'https://www.wlnupdates.com/api', json_encode($ar), array('Content-Type: application/json') );
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'read-update.json', $res);
		$res=json_decode($res);
		return $res;
	}
	
	public function search($name) {
		$ar=array(
			'mode'=>'search-title',
			'title'=>$name,
		);
		$res = $this->send( 'https://www.wlnupdates.com/api',json_encode($ar), array('Content-Type: application/json') );
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'search-title.json', $res);
		$res=json_decode($res);
		return $res;
	}
	
	public function add($name, $tl) {
		if(!in_array($tl, array('translated', 'oel'))) return false;
		
		$referer = 'https://www.wlnupdates.com/add/series/';
		$res = $this->send( 'https://www.wlnupdates.com/add/series/' );
		preg_match_all('#<form(?:\s+(?:id=["\'](?P<id>[^"\'<>]*)["\']|action=["\'](?P<action>[^"\'<>]*)["\']|\w+=["\'][^"\'<>]*["\']|))+>(?P<content>.*)</form>#isU', $res, $matches);
		
		$id=array_search('/add/series/', $matches['action']);
		if($id===false) $id=array_search('', $matches['action']);
		assert($id!==false) or die(var_export($matches['action']));
		
		preg_match_all('#<input(?:\s+(?:name=["\'](?P<name>[^"\'<>]*)["\']|type=["\'](?P<type>[^"\'<>]*)["\']|value=["\'](?P<value>[^"\'<>]*)["\']|\w+=["\'][^"\'<>]*["\']|\w+))+>#is', $matches['content'][$id], $matches2);
		$postdata=array();
		foreach($matches2['name'] as $id2=>$name2) {
			if($name2=='name') $postdata[$name2]=$name;
			else if($name2=='type') $postdata[$name2]=$tl;
			else if(!empty($name2)) $postdata[$name2]=$matches2['value'][$id2];
		}
		var_dump($postdata);
		/*$cookies=$this->get_cookies_for('https://www.wlnupdates.com/');
		$ar=array(
			'name'=>$name,
			'type'=>$tl,
			'csrf_token'=>$cookies['_csrfToken'],
		);//*/
		//csrf_token
		$headers=array(
			'Referer: '.$referer,
			'Content-Type: application/x-www-form-urlencoded',
			'Origin: https://www.wlnupdates.com',
		);
		$res = $this->send( 'https://www.wlnupdates.com/add/series/', $postdata, $headers);
		file_put_contents($this::FOLDER.'add-series.json', $res);
		//$res=json_decode($res);
		
		preg_match_all('#<meta name="manga-id" content="(\d+)"/?>#i', $res, $matches);
		if(count($matches[1])==1) {
			//success
			return $matches[1][0];
		}
		else {
			var_dump($res);
			die('error');
		}
		return $res;
	}
	
	public function edit($json) {
		$ar=$json;
		if(count($ar)==0||count($ar['entries'])==0) return false;
		$res = $this->send( 'https://www.wlnupdates.com/api', json_encode($ar, JSON_UNESCAPED_SLASHES), array('Content-Type: application/json')); // no cookies
		$res2=explode(' ',$this->headersRecv[0]);
		if($res2[1]!=200) {
			var_dump($this->headersRecv[0]);
			//die();
			return false;
		}
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'edit.json', $res);
		$res=json_decode($res);
		return $res;
	}
	
	public function info($id) {
		$ar=array(
			'id'=>$id,
			'mode'=>'get-series-id',
		);
		$time=$_SERVER['REQUEST_TIME'];
		$headers=array(
			'Content-Type: application/json',
			'X-Forwarded-For: sebbu2/LN-backup-sync',
			//'X-Forwarded-For: sebbu2/LN-backup-sync'.' '.$time,
			//'X-CSRFToken: IjdmYWQ3NzhjMjU2NmYwYWRlNWE5ODNkNzFkZjdlNGFiYjk1ZmE1MWQi.YT0lQg.VYZvn9ZATduUJBlY98AGHW_dSL0',
		);
		$res = $this->send( 'https://www.wlnupdates.com/api', json_encode($ar), $headers, false); // no cookies
		//$res = $this->send( 'https://www.wlnupdates.com/api', json_encode($ar), $headers );
		sleep(1);
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'get-series-id'.$id.'.json', $res);
		$res=json_decode($res);
		var_dump('get_info: '.$id);
		return $res;
	}
	
	public function get_info_cached($id, $duration=604800) {
		$fn=$this::FOLDER.'get-series-id'.$id.'.json';
		if(!file_exists($fn) || (time()-filemtime($fn))>$duration ) {
			return $this->info($id);
		}
		return json_decode(file_get_contents($this::FOLDER.'get-series-id'.$id.'.json'),false, 512, JSON_THROW_ON_ERROR);
	}
	
	public function get_names($id) {
		$res2=$this->get_info_cached($id);
		$names=array_filter(array_unique(array_merge(
			$res2->data->alternatenames,
			array_map('normalize', $res2->data->alternatenames),
			array_map(fn($e) => json_decode('"'.$e.'"'), array_map('normalize', $res2->data->alternatenames)),
			//array_map('normalize2', $res2->data->alternatenames),
			array_map('normalize2', array_map('normalize', $res2->data->alternatenames)),
			array_map('normalize2', array_map('normalize', array_map(fn($e) => name_simplify($e, 1), $res2->data->alternatenames))),
			array() // NOTE : keep
		)));
		$names=preg_grep('#\\\\u[[:xdigit:]]{4}#', $names, PREG_GREP_INVERT);//*/
		$names=array_map('trim2', $names);
		$names=array_values(array_unique(array_filter($names)));
		natcasesort($names);
		return $names;
	}
	
	public function add_novel($id, $list='QIDIAN') {
		$ar=array(
			'mode'=>'set-watch',
			'item-id'=>strval($id),
			'list'=>$list,
			'watch'=>true,
		);
		$res = $this->send( 'https://www.wlnupdates.com/api', json_encode($ar), array('Content-Type: application/json'));
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'set-watch.json', $res);
		$res=json_decode($res);
		return $res;
	}
};
?>