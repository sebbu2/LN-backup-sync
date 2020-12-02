<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('wlnupdates.php');
require_once('webnovel.php');

if(!defined('MOONREADER_DID')) define('MOONREADER_DID', '1454083831785');
if(!defined('MOONREADER_DID2')) define('MOONREADER_DID2', '9999999999999');
if(!defined('MOONREADER_LOCAL_PATH')) define('MOONREADER_LOCAL_PATH', 'dropbox/');
if(!defined('MOONREADER_REMOTE_PATH')) define('MOONREADER_REMOTE_PATH', '/Apps/Books/.Moon+/Cache/');

if(!isset($key)) $key='';
if(!isset($secret)) $secret='';

include('header.php');

class Dropbox {
	//req
	private $opts=array();
	private $postdata=array();
	private $domains=array();
	private $urls=array();
	private $token=array();
	public $errors=NULL;
	//auth
	private $key='';
	private $secret='';
	//public
	private function init() {
		$this->opts=array();
		$this->opts['http']=array(
			'ignore_errors'=>true,
			'protocol_version'=>'1.1',
		);
		$this->postdata=array();
	}

	private function check_errors($data=NULL) {
		if($data===NULL) return false;
		if(property_exists($data, 'error')) {
			return true;
		}
		else return false;
	}

	public function __construct() {
		$this->init();
		$this->domains=array();
		$this->domains[]='https://api.dropboxapi.com';
		$this->domains[]='https://content.dropboxapi.com';
		$this->domains[]='https://notify.dropboxapi.com';
		$domain=$this->domains[0];
		$this->urls=array();
		$this->urls[]='https://www.dropbox.com'.'/oauth2/authorize';
		$this->urls[]=$domain.'/oauth2/token';
		$this->urls[]=$domain.'/2/check/app';
		$this->urls[]=$domain.'/2/check/user';
		$this->urls[]=$domain.'/2/files/list_folder';
		$this->urls[]=$domain.'/2/files/list_folder/continue';
	}

	public function authcode($client_id='', $client_secret='') {
		$this->key=$client_id;
		$this->secret=$client_secret;
	}

	public function verify_app() {
		if($this->key==='' || $this->secret==='') {
			return false;
		}
		$this->postdata=array();
		$this->postdata['query']=date('c');
		
		$this->opts['http']['method']='POST';
		$this->opts['http']['header']=array(
			'Authorization: Basic '.base64_encode($this->key.':'.$this->secret),//app auth
			'Content-Type: application/json'
		);
		$this->opts['http']['content']=json_encode($this->postdata);
		$url=$this->urls[2];

		$ctx=stream_context_create($this->opts);

		$data=file_get_contents($url, false, $ctx);
		file_put_contents('app.json', $data);
		$data=json_decode($data);
		
		if(
			!( is_object($data) && property_exists($data, 'result')) &&
			!( is_array($data) && array_key_exists('result', $data))
		) {
			var_dump($data);
			return false;
		}
		assert((time()-strtotime($data->result))<10) or die('ERROR: time mismatch.');
		return $data;
	}

	public function get_token() {
		$data='';
		if(file_exists('token2.json')) {
			$data=file_get_contents('token2.json');
			$data=json_decode($data);
			if(property_exists($data, 'error')) {
				assert(file_exists('token1.json')) or die('You need to be auth.');
				unlink('token2.json');
				$data=file_get_contents('token1.json');
				$data=json_decode($data);
			}
		}
		else {
			assert(file_exists('token1.json')) or die('You need to be auth.');
			$data=file_get_contents('token1.json');
			$data=json_decode($data);
		}
		$this->token=$data;
		return $this->token;
	}

	public function verify_user() {
		$data=$this->get_token();

		$this->opts['http']['header']=array(
			'Authorization: Bearer '.$data->access_token,//user auth
			'Content-Type: application/json'
		);
		$this->postdata=array();
		$this->postdata['query']=date('c');
		$this->opts['http']['content']=json_encode($this->postdata);

		$url=$this->urls[3];

		$ctx=stream_context_create($this->opts);

		$data=file_get_contents($url, false, $ctx);
		file_put_contents('user.json', $data);
		$data=json_decode($data);

		if(
			( is_object($data) && property_exists($data, 'error')) ||
			( is_array($data) && array_key_exists('error', $data))
		) {
			$this->errors=$data;
			return false;
			//return $data;
		}
		assert((time()-strtotime($data->result))<10) or die('ERROR: time mismatch.');
		return $data;
	}

	public function authorize_code() {
		$url=$this->urls[0];
		$url.='?';
		$url.='&client_id='.$key;
		$url.='&token_access_type=offline';
		$url.='&response_type=code';
		echo '<a href="'.$url.'" target="_blank">Authorize</a>';
		echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\r\n";
		echo '<label for="code">Recopy code from previous link'."\r\n";
		echo '<input type="text" id="code" name="code"/>'."\r\n";
		echo '</label>'."\r\n";
		echo '</form>'."\r\n";
	}
	
	public function validate_code() {
		$code=$_POST['code'];

		$url=$this->urls[1];

		$this->postdata=array();
		$this->postdata['code']=$code;
		$this->postdata['grant_type']='authorization_code';
		/*$this->postdata['client_id']=$this->key;
		$this->postdata['client_secret']=$this->secret;//*/

		$this->opts['http']['method']='POST';
		$this->opts['http']['header']=array(
			$this->opts['http']['header'][0],//auth
			'Content-type: application/x-www-form-urlencoded'
		);
		//$this->opts['http']['content']=http_build_query($this->postdata, '', '&', PHP_QUERY_RFC3986);//%20
		$this->opts['http']['content']=http_build_query($this->postdata, '', '&', PHP_QUERY_RFC1738);//+

		$ctx=stream_context_create($this->opts);

		$data=file_get_contents($url, false, $ctx);
		file_put_contents('token1.json', $data);

		return $data;
	}

	public function refresh_code() {
		//refresh
		$url=$this->urls[1];
		
		$this->postdata=array();
		$this->postdata['grant_type']='refresh_token';
		$this->postdata['refresh_token']=$this->token->refresh_token;
		/*$this->postdata['client_id']=$key;
		$this->postdata['client_secret']=$secret;//*/

		$this->opts['http']['header']=array(
			//$this->opts['http']['header'][0],
			'Authorization: Basic '.base64_encode($this->key.':'.$this->secret),//app auth
			'Content-type: application/x-www-form-urlencoded',
		);
		//this->opts['http']['content']=http_build_query($this->postdata, '', '&', PHP_QUERY_RFC3986);//%20
		$this->opts['http']['content']=http_build_query($this->postdata, '', '&', PHP_QUERY_RFC1738);//+

		$ctx=stream_context_create($this->opts);

		$data=file_get_contents($url, false, $ctx);
		file_put_contents('token2.json', $data);
		
		$this->token=json_decode($data);
		if($this->check_errors($this->token)) {
			$this->errors=$this->token;
			return false;
		}
		$this->opts['http']['header']=array(
			'Authorization: Bearer '.$this->token->access_token,//user auth
			'Content-Type: application/json'
		);
		return $this->token;
	}

	public function login(string $user, string $pass) {
		return false;//TODO !
	}
	
	public function list_folder() {
		$url=$this->urls[4];

		$this->postdata=array(
			'path'=>MOONREADER_REMOTE_PATH, // for root, it's not "/", it's ""
			'recursive'=>false,
			'limit'=>1000,//1-2000
			'include_non_downloadable_files'=>true,
		);

		$this->opts['http']['content']=json_encode($this->postdata);

		$ctx=stream_context_create($this->opts);

		$data=file_get_contents($url, false, $ctx);
		file_put_contents('list0.json', $data);
		$data=json_decode($data);
		
		if(property_exists($data, 'error')) { var_dump($data); die(); }
		$files=$data->entries;
		
		if($data->has_more) {
			$i=1;
			$url=$this->urls[5];
			while($data->has_more) {
				$this->postdata=array(
					'cursor'=>$data->cursor,
				);
				$this->opts['http']['content']=json_encode($this->postdata);
				$ctx=stream_context_create($this->opts);
				$data=file_get_contents($url, false, $ctx);
				file_put_contents('list'.$i.'.json', $data);
				$data=json_decode($data);
				if(property_exists($data, 'error')) { var_dump($data); die(); }
				$files=array_merge($files, $data->entries);
			}
		}
		return $files;
	}
}

$dpb=new Dropbox();
$dpb->authcode($key, $secret);

$data=$dpb->verify_app();
assert($data!==false) or die('ERROR: App not verified.');

$data=$dpb->verify_user();
//assert($data!==false) or die('ERROR: User not verified.');

if($data===false && $data=$dpb->errors && property_exists($data, 'error')) {
	if(property_exists($data->error, '.tag')) {
		if($data->error->{'.tag'}==='expired_access_token') {
			$data=$dpb->refresh_code();
			if($data===false) {
				$data=$dpb->errors;
				var_dump($data);
				die();
			}
		}
		else { var_dump(1,$data); die(); }
	}
	else { var_dump(2,$data); die(); }
}
//var_dump($data);//die();

$files=$dpb->list_folder();

/*var_dump($files);
$e=$files[0]->server_modified;
var_dump(strtotime($e));
//date timezone formats : O +0200 P +02:00 T CEST Z 7200(in seconds)
var_dump(date('Y-m-d H:i:s P',strtotime($e)));
die();//*/
$ar=array();
foreach($files as $entry) {
	//var_dump($entry->name);die();
	assert(abs(strtotime($entry->server_modified)-strtotime($entry->client_modified))<10) or die(var_export($entry));
	$ar[]=[$entry->name, $entry->size, $entry->server_modified];
}
//usort($ar, fn($e1, $e2) => strcasecmp($e1[0], $e2[0]));//sort by fn
usort($ar, fn($e1, $e2) => strtotime($e1[2])-strtotime($e2[2]));//sort by date
var_dump($ar);

//download : 
//Dropbox-API-Arg
//Dropbox-API-Result
//touch($fn, filemtime($tn));

//data
include('footer.php');
