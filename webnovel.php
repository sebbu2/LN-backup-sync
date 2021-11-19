<?php
require_once('config.php');
require_once('SitePlugin.inc.php');

class WebNovel extends SitePlugin
{
	public const FOLDER='webnovel/';
	
	public const RETRY_LIMIT = 5; // we try each query up to 5 times in cases of errors (the API randomly fails)
	public const LIBRARY_LIMIT = 40; // to prevent infinite loop in case of error when retrieving the library (i'm at ~ half of it)
	
	public function __construct() {
		
	}
	
	public function checkLogin() {
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		$ar=array(
			'appid'=>'900',
			'areaid'=>'1',
			'source'=>'enweb',
			'format'=>'jsonp',
			'auto'=>'1',
			'method'=>'autoLoginHandler',
			'_csrfToken'=>(array_key_exists('_csrfToken', $cookies)?$cookies['_csrfToken']:''),
			'_'=>millitime(),
		);
		$headers=array(
			'Referer: https://www.webnovel.com/',
		);
		$res=$this->get( 'https://ptlogin.webnovel.com/login/checkstatus', $ar, $headers);
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'checkstatus.json', $res);
		$res=json_decode($res);
		return $res;
	}
	
	public function login(string $user, string $pass) {
		$cookies=array();$ar=array();$headers=array();$res=NULL;$data=NULL;$referer=NULL;
		$xml=NULL;$found=false;$crypted='';
		$data2=NULL;$res2=NULL;$res3=NULL;
		
		{ // 1 webnovel
			$res=$this->get( 'https://www.webnovel.com/'); // initialize cookies
			file_put_contents($this::FOLDER.'wn.htm', $res);
		}
		$referer=$this->lastUrl;
		
		{ // 2 checkstatus (1)
			$cookies=$this->get_cookies_for('https://www.webnovel.com/');
			$ar=array(
				'appid'=>'900',
				'areaid'=>'1',
				'source'=>'enweb',
				'format'=>'jsonp',
				'auto'=>'1',
				'method'=>'autoLoginHandler',
				'_csrfToken'=>$cookies['_csrfToken'],
				'_'=>millitime(),
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res=$this->get( 'https://ptlogin.webnovel.com/login/checkstatus', $ar, $headers);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'checkstatus0.json', $res);
			//$res=json_decode($res);
		}
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		//var_dump($cookies);
		
		{ // 3 login
			$ar=array(
				'auto'=>1,
				'appid'=>900,
				'areaid'=>1,
				'target'=>'iframe',
				'maskOpacity'=>50,
				'popup'=>1,
				'returnUrl'=>'https://www.webnovel.com/loginSuccess',
				'format'=>'redirect',
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://passport.webnovel.com/login.html', $ar, $headers);
			file_put_contents($this::FOLDER.'login1.htm', $res);
			
			/*preg_match('#(?<=LoginV1\.init\()\{(.*?)\}(?=\);)#s', $res, $matches);
			var_dump($matches);die();
			$json=json_decode($matches[0]);//*/
			
			$xml=simplexml_load_html($res);
			$found=false;
			$data=array();
			foreach($xml->xpath('//div[@class="m-bts m-login-bts"]//a') as $node) {
				if($node->i['class']=='i-mail') {
					$found=true;
					$data['title']=strval($node['title']);
					$data['href']=strval($node['href']);
				}
			}
			if(!$found) {
				var_dump('login.html page changed.');
				die();
			}
			if($data['href'][0]!='/' && substr($data['href'],0,5)!='http:' && substr($data['href'],0,6)!='https:')
				$data['href']='https://passport.webnovel.com/'.$data['href'];
		}
		
		$referer=$this->lastUrl;
		
		{ // 4 checkstatus (2)
			$cookies=$this->get_cookies_for('https://ptlogin.webnovel.com/login/checkStatus');
			$ar=array(
				//'callback'=>'',
				'appId'=>'900',
				'areaId'=>'1',
				'source'=>'',
				'returnurl'=>'https://www.webnovel.com/loginSuccess',
				'version'=>'',
				'imei'=>'',
				'qimei'=>'',
				'target'=>'iframe',
				'format'=>'redirect',
				'ticket'=>'',
				'autotime'=>0,
				'auto'=>'1',
				'fromuid'=>'0',
				'_csrfToken'=>$cookies['_csrfToken'],
				'method'=>'LoginV1.checkStatusCallback',
				'_'=>millitime(),
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://ptlogin.webnovel.com/login/checkStatus', $ar, $headers);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'checkStatus1.json', $res);
			//$res=json_decode($res);
		}
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		
		{ // 5 emaillogin
			//$ar=array();
			//$headers=array();
			$res = $this->get( $data['href'] ); // email page
			file_put_contents($this::FOLDER.'login2.htm', $res);
			/*$xml=simplexml_load_html($res);
			//var_dump(strval($xml->script[4])); // number not definitive
			preg_match('#(?<=LoginV1\.init\()\{(.*?)\}(?=\);)#s', $res, $matches);
			$json=json_decode($matches[0]);
			//var_dump($json);
			
			//$pub_key=$json->pubkey;//*/
			$pub_key=file_get_contents($this::FOLDER.'webnovel.pub');
			$res2=openssl_get_publickey($pub_key);
			if(!is_resource($res2) && !is_object($res2)) {
				var_dump($pub_key, $res2);
				var_dump('invalid webnovel pubkey.');
				die();
			}
		}
		
		$referer=$this->lastUrl;
		
		{ // 6 crypted password
			$crypted='';
			$res3=openssl_public_encrypt($pass, $crypted, $res2);
			if(!$res3) {
				var_dump('incorrect encrypt.');
				die();
			}
			$crypted=base64_encode($crypted);
		}
		
		{ // 7 checkstatus (3)
			$ar=array(
				//'callback'=>'jQuery19108260751275061321_1599031203274',
				'callback'=>'',
				'appid'=>900,
				'areaid'=>1,
				'source'=>'',
				'returnurl'=>'https://www.webnovel.com/loginSuccess',
				'version'=>'',
				'imei'=>'',
				'qimei'=>'',
				'target'=>'iframe',
				'format'=>'jsonp',
				'ticket'=>'',
				'autotime'=>'',
				'auto'=>'',
				'fromuid'=>0,
				'_csrfToken'=>$cookies['_csrfToken'],
				'method'=>'LoginV1.checkStatusCallback',
				'_'=>millitime(),
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://ptlogin.webnovel.com/login/checkStatus', $ar, $headers);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'checkStatus2.json', $res);
			//$res=json_decode($res);
		}
		
		{ // 8 checkcode
			$ar=array(
				//'callback'=>'jQuery19108260751275061321_1599031203274',
				'callback'=>'',
				'appId'=>900,
				'areaId'=>1,
				'source'=>'',
				'returnurl'=>'https://www.webnovel.com/loginSuccess',
				'version'=>'',
				'imei'=>'',
				'qimei'=>'',
				'target'=>'iframe',
				'format'=>'jsonp',
				'ticket'=>'',
				'autotime'=>'',
				'auto'=>1,
				'fromuid'=>0,
				'_csrfToken'=>$cookies['_csrfToken'],
				'username'=>$user,
				'password'=>$crypted,
				'sessionkey'=>'',
				'method'=>'LoginV1.checkCodeCallback',
				'logintype'=>22,
				'_'=>millitime(),
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://ptlogin.webnovel.com/login/checkcode', $ar, $headers);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'checkcode.json', $res);
			$data=json_decode($res);
			
		}
		
		$userid=$data->data->userid;
		$ticket=$data->data->ticket;
		$ukey=$data->data->ukey;
		//$autoLoginSessionKey=$data->data->autoLoginSessionKey;
		$sessionKey = $data->data->sessionKey;
		//$this->set_cookies_for('https://www.webnovel.com/', {});
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		//$cookies['_csrfToken']=$sessionKey;
		//var_dump($cookies);
		
		{ // 9 loginSuccess
			$ar=array(
				'sessionkey'=>$sessionKey,
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get($data->data->returnurl, $ar);
			file_put_contents($this::FOLDER.'loginSuccess.htm', $res);
			//$res=json_decode($res);
		}
		
		$referer=$this->lastUrl;
		$referer='https://www.webnovel.com/';
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		
		{ // 9b webnovel
			$res=$this->get( 'https://www.webnovel.com/', $ar); // initialize cookies
			file_put_contents($this::FOLDER.'wn.htm', $res);
		}
		$referer=$this->lastUrl;
		
		{ // 10 login
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'code'=>'',
				'ticket'=>$ticket,
				'guid'=>$userid,
				'sessionkey'=>$sessionKey,
				'forceRedirect'=>'',
			);
			$headers=array(
				'X-Requested-With: XMLHttpRequest',
				'Referer: '.$referer,
			);
			//$res = $this->get( 'https://www.webnovel.com/apiajax/login/login', $ar);
			$res = $this->get( 'https://www.webnovel.com/go/pcm/login/login', $ar);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'login.json', $res);
			//$res=json_decode($res);
		}
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		
		/*{ // 11 getUserInfo
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'sessionkey'=>$sessionKey,
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://www.webnovel.com/apiajax/login/getUserInfo', $ar);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'getUserInfo.json', $res);
			//$res=json_decode($res);
		}//*/
		
		{ // 11 bis page
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'bookCityType'=>2,
				'sex'=>1,
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://www.webnovel.com/go/pcm/pcbookcity/page', $ar);
			$res = $this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'page.json', $res);
		}
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		
		{ // 12 notification-status
			
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'sessionkey'=>$sessionKey,
			);
			$headers=array(
				'Referer: '.$referer,
			);
			//$res = $this->get( 'https://www.webnovel.com/apiajax/notification/status', $ar);
			$res = $this->get( 'https://www.webnovel.com/go/pcm/notification/status', $ar);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'notification-status.json', $res);
			//$res=json_decode($res);
		}
		return $data;
	}
	
	private function novel_cmp_by_name($e1, $e2) {
		//usort($books, function($e1, $e2) { $res=strcasecmp($e1->bookName, $e2->bookName); if($res!=0) return $res; return $e1->novelType <=> $e2->novelType; });
		$n1=$n2='';
		if(property_exists($e1, 'bookName')) $n1=$e1->bookName;
		else {
			$res=$this->get_info_cached($e1->bookId);
			if(is_object($res[0]->Data) && property_exists($res[0]->Data, 'BookName')) $n1=$res[0]->Data->BookName;
			else $n1='';
		}
		if(property_exists($e2, 'bookName')) $n2=$e2->bookName;
		else {
			$res=$this->get_info_cached($e2->bookId);
			if(is_object($res[0]->Data) && property_exists($res[0]->Data, 'BookName')) $n2=$res[0]->Data->BookName;
			else $n2='';
		}
		$res=strcasecmp($n1, $n2);
		if($res!==0) return $res;
		return $e1->novelType <=> $e2->novelType;
	}
	
	private function novel_cmp_by_id($e1, $e2) {
		$res = $e1->bookId <=> $e2->bookId;
		if($res!==0) return $res;
		return $e1->novelType <=> $e2->novelType;
	}
	
	public function watches() {
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		$referer='https://www.webnovel.com/library';
		/*{
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
			);
			$headers=array(
				'X-Requested-With: XMLHttpRequest',
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://www.webnovel.com/apiajax/ad/bookShelf', $ar, $headers );
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'bookShelf.json', $res);
			//$res=json_decode($res);
		}//*/
		$i=1;
		$books=array();
		do {
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'pageIndex'=>$i,
				'orderBy'=>'2',
			);
			$j=0;
			do {
				//$res = $this->get( 'https://www.webnovel.com/apiajax/Library/LibraryAjax', $ar);
				$res = $this->get( 'https://www.webnovel.com/go/pcm/library/library', $ar);
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'LibraryAjax'.strval($i).'.json', $res);
				$res=json_decode($res);
				//var_dump($i, $res);
			}
			while(++$j<$this::RETRY_LIMIT && (
				(!property_exists($res, 'code') || $res->code!=0) ||
				(!property_exists($res, 'msg') || $res->msg!='Success') ||
				(!property_exists($res, 'data') || $res->data==null)
			) );
			/*if(
				( !is_object($res) || !property_exists($res, 'data') || !is_object($res->data) || !property_exists($res->data, 'isLast') || !property_exists($res->data, 'books') )
				&&
				( !is_array($res) || !array_key_exists('data',$res) || !array_key_exists('isLast', $res['data']) || !array_key_exists('books', $res['data']) )
			) {
				var_dump($res);
				die();
			}//*/
			if(is_object($res) && property_exists($res, 'data') && $res->data!==NULL) {
				//$books=array_merge($books, $res->data->books);
				$books=array_merge($books, $res->data->items);
				++$i;
			}
		}
		while( ( (is_object($res) && property_exists($res, 'data') && is_object($res->data) && $res->data->isLast==0) ) && $i<=$this::LIBRARY_LIMIT);//$i should not reach 40*30 books soon (i'm at 21)
		$books2=json_encode($books);
		$books2=$this->jsonp_to_json($books2);
		if(is_null($books2)) { die('error'); }
		file_put_contents($this::FOLDER.'_books2.json', $books2 );//TEMP
		
		$books2=array();
		$order=array();
		
		//usort($books, function($e1, $e2) { $res=strcasecmp($e1->bookName, $e2->bookName); if($res!=0) return $res; return $e1->novelType <=> $e2->novelType; });
		//usort($books, array($this, 'novel_cmp'));
		//usort($books, array($this, 'novel_cmp_by_id'));
		
		foreach($books as $i=>$b) {
			//$ind=-1;
			//foreach($books2 as $i2=>$b2) { if($b2==$b) { $ind=$i2; break; } }
			if(array_key_exists($b->bookId, $books2)) die('duplicate ID.');
			$books2[$b->bookId]=$b;
			if($b->novelType==0) {
				//$order[]=[$b->bookId, $b->bookName, $subName, count($books2)-1-$ind];
				$order[]=$b->bookId;
			}
			elseif($b->novelType==100) {
				//$order[]=[$b->bookId, $b->comicName, $res->data->comicInfo->comicName, count($books2)-1-$ind];
				$order[]=$b->bookId;
			}
		}
		unset($books); //
		ksort($books2);
		$books=json_encode($books2); 
		$books=$this->jsonp_to_json($books);
		file_put_contents($this::FOLDER.'_books.json', $books );
		
		$order2=json_encode($order); unset($order);
		$order2=$this->jsonp_to_json($order2);
		file_put_contents($this::FOLDER.'_order.json', $order2 );
		
		$this->update_subnames();
		
		return $books2;
	}
	
	public function update_subnames() {
		/*$books=file_get_contents($this::FOLDER.'_books.json');
		$books=json_decode($books, false, 512, JSON_THROW_ON_ERROR);//*/
		$books=$this->get_watches();
		$sub=array();
		$subs=array();
		foreach($books as $i=>$b) {
			if($b->novelType==0) {
				$res=$this->get_chapter_list_cached($b->bookId);
				if( (is_object($res) && !property_exists($res, 'data')) || (is_object($res) && property_exists($res, 'data') && !is_object($res->data)) ) {
					$res=$this->get_chapter_list($b->bookId);
				}
				$subName='';
				if(!is_object($res) || !property_exists($res, 'data') || !is_object($res->data) || !property_exists($res->data, 'bookInfo') || !property_exists($res->data->bookInfo, 'bookSubName') || strlen($res->data->bookInfo->bookSubName)==0) {
					$res2=$this->get_info_html_cached($b->bookId);
					if(is_object($res2)) {
						if(property_exists($res2, 'bookInfo') && property_exists($res2->bookInfo, 'bookSubName'))
							$subName=$res2->bookInfo->bookSubName;
						else $subName=NULL;
					}
					else if(is_array($res2)) {
						if(array_key_exists('bookInfo', $res2) && array_key_exists('bookSubName', $res2['bookInfo']))
							$subName=$res2['bookInfo']['bookSubName'];
						else $subName=NULL;
					}
				}
				else if(is_object($res) && property_exists($res, 'data') && property_exists($res->data, 'bookInfo')) $subName=$res->data->bookInfo->bookSubName;
				else $subName=NULL;
				if($subName===NULL || $subName==='') continue;
				$sub[$b->bookId]=$subName;
				if(!array_key_exists($subName, $subs)) {
					$subs[$subName]=$b->bookId;
				}
				else {
					if(is_string($subs[$subName])) {
						$subs[$subName]=array_merge( array($subs[$subName]), array($b->bookId));
						sort($subs[$subName]);
					}
					else if(is_array($subs[$subName])) {
						$subs[$subName]=array_merge($subs[$subName], array($b->bookId));
						sort($subs[$subName]);
					}
					else die('error');
				}
			}
			elseif($b->novelType==100) {
				$res=$this->get_chapter_list_comic_cached($b->bookId);
				if(!property_exists($res, 'data')) {
					$res=$this->get_chapter_list_comic($b->bookId);
				}
				//var_dump($res->data->comicInfo);die();
			}
		}
		ksort($sub);
		$sub2=json_encode($sub);
		$sub2=$this->jsonp_to_json($sub2);
		file_put_contents($this::FOLDER.'_subname.json', $sub2);
		
		ksort($subs);
		$subs2=json_encode($subs);
		$subs2=$this->jsonp_to_json($subs2);
		file_put_contents($this::FOLDER.'_subnames.json', $subs2);
	}
	
	public function get_watches() {
		$res=json_decode(file_get_contents($this::FOLDER.'_books.json'), false, 512, JSON_THROW_ON_ERROR);
		if(is_object($res)) $res=get_object_vars($res);
		return $res;
	}
	
	public function get_list() {
		throw new Exception('Not available on WebNovel.');
	}
	
	public function get_order() {
		$res=json_decode(file_get_contents($this::FOLDER.'_order.json'), false, 512, JSON_THROW_ON_ERROR);
		if(is_object($res)) $res=get_object_vars($res);
		return $res;
	}
	
	public function read_update($watch, $chp) {
		$this->msg=array();
		if(file_exists($this::FOLDER.'GetChapterList_'.strval($watch->bookId).'.json'))
		{
			$res=json_decode(file_get_contents($this::FOLDER.'GetChapterList_'.strval($watch->bookId).'.json'), false, 512, JSON_THROW_ON_ERROR );
		}
		else {
			$res=$this->get_chapter_list($watch->bookId);
		}
		if( !is_object($res) || !isset($res->data) || !isset($res->data->bookInfo) || !isset($res->data->volumeItems) ) {
			unlink($this::FOLDER.'GetChapterList_'.strval($watch->bookId).'.json');
			//var_dump('deleting '.$this::FOLDER.'GetChapterList_'.strval($watch->bookId).'.json');
			$this->msg[]='deleting '.$this::FOLDER.'GetChapterList_'.strval($watch->bookId).'.json';
			$res=$this->get_chapter_list($watch->bookId);
			if( !is_object($res) || !isset($res->data) || !isset($res->data->bookInfo) || !isset($res->data->volumeItems) )
			{
				var_dump($res);die();
			}
		}
		$add=0;
		
		if(property_exists($res->data->volumeItems[0], 'index') && $res->data->volumeItems[0]->index==0) $add=-$res->data->volumeItems[0]->chapterCount; // substract auxiliary volume chapters
		if(property_exists($res->data->volumeItems[0], 'volumeIndex') && $res->data->volumeItems[0]->volumeIndex==0) $add=-count($res->data->volumeItems[0]->chapterItems); // substract auxiliary volume chapters
		//updating list of chapters
		if( ($watch->newChapterIndex > $res->data->bookInfo->totalChapterNum+$add) && $watch->readToChapterIndex<$chp) {
			//var_dump('Updating',$res->data->bookInfo->bookName);
			$this->msg[]='Updating';
			$res=$this->get_chapter_list($watch->bookId);
		}
		if(!is_object($res)||!property_exists($res, 'data')||!property_exists($res->data, 'volumeItems')) {
			var_dump($res);die();
		}
		$cookies=$this->get_cookies_for('https://www.webnovel.com/apiajax/');
		$referer='https://www.webnovel.com/library';
		/*{
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'bookId'=>strval($watch->bookId),
				'_'=>millitime(),
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://www.webnovel.com/apiajax/chapter/GetChapterList', $ar, $headers);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'GetChapterList_'.strval($watch->bookId).'.json', $res);
			$res=json_decode($res);
		}//*/
		
		$cid=0;
		$cid_max=0;
		$cid_max_num=0;
		$found=false;
		$update=false;
		{
			foreach($res->data->volumeItems as $volume) {
				foreach($volume->chapterItems as $chapter) {
					$id=-1;
					if(property_exists($chapter, 'id')) $id=$chapter->id;
					else if(property_exists($chapter, 'chapterIndex')) $id=$chapter->chapterId;
					$index=-1;
					if(property_exists($chapter, 'index')) $index=$chapter->index;
					else if(property_exists($chapter, 'chapterIndex')) $index=$chapter->chapterIndex;
					if($index <= $chp && $chapter->chapterLevel==0) {
						$cid = $id;
						//$update=true;
					}
					if($index == $chp && $chapter->chapterLevel==0) {
						$cid = $id;
						$found=true;
						$update=true;
					}
					if($index>$cid_max_num && $index<=$chp && $chapter->chapterLevel==0) {
						$cid_max_num=$index;
						$cid_max=$id;
					}
				}
			}
		}
		//var_dump($found,$update);
		if(!$found) {
			if($cid_max_num>$watch->readToChapterIndex) {
				//var_dump('Updating to '.$cid_max_num.' instead of '.$chp.'.');
				$this->msg[]='Updating to '.$cid_max_num.' instead of '.$chp.'.';
				$update=true;
			}
			else {
				//TODO
			}
		}
		
		if ($update) {
			if($watch->readToChapterIndex==$chp) return false;
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'bookId'=>strval($watch->bookId),
				'chapterId'=>($cid>0?$cid:$cid_max),
			);
			//var_dump($ar);
			//$res = $this->send( 'https://www.webnovel.com/apiajax/Library/SetReadingProgressAjax', $ar );
			$res = $this->send( 'https://www.webnovel.com/go/pcm/library/setReadingProgressAjax', $ar );
			
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'SetReadingProgressAjax.json', $res);
			$res=json_decode($res);
		}
		else {
			$res=false;
		}
		return $res;
	}
	
	public function get_chapter_list($bookId) {
		$cookies=$this->get_cookies_for('https://www.webnovel.com/apiajax/');
		$referer='https://www.webnovel.com/library';
		{
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'bookId'=>strval($bookId),
				'pageIndex'=>0,
				'_'=>millitime(),
			);
			$headers=array(
				'Referer: '.$referer,
			);
			//https://www.webnovel.com/go/pcm/chapter/get-chapter-list?_csrfToken=caa48fb1-b69d-4447-ae90-e30edc332950&bookId=16923111105764205&pageIndex=0&_=1630584775104
			//$res = $this->get( 'https://www.webnovel.com/apiajax/chapter/GetChapterList', $ar, $headers);
			$res = $this->get( 'https://www.webnovel.com/go/pcm/chapter/get-chapter-list', $ar, $headers);
			//file_put_contents('request.log', $res);
			try {
				$res=$this->jsonp_to_json($res);
				//unlink('request.log');
				$fn = $this::FOLDER.'GetChapterList_'.strval($bookId).'.json';
				file_put_contents($fn, $res);
				clearstatcache(true, $fn);
				$res=json_decode($res);
			}
			catch(Exception $e) {
				$res=NULL;
			}
		}
		return $res;
	}
	
	public function get_chapter_list_cached($bookId) {
		if(!file_exists($this::FOLDER.'GetChapterList_'.strval($bookId).'.json')) {
			return $this->get_chapter_list($bookId);
		}
		$res='';
		//var_dump($bookId);
		try {
			$res=file_get_contents($this::FOLDER.'GetChapterList_'.strval($bookId).'.json');
			$res=json_decode($res, false, 512, JSON_THROW_ON_ERROR);
		}
		catch(JsonException $e) {
			//var_dump($e);
			echo '<table class="xdebug-error xe-warning" dir="ltr" border="1" cellspacing="0" cellpadding="1">';echo $e->xdebug_message;echo '</table>';
			//die();
		}
		if(!is_object($res)) {
			var_dump($res);
			unlink($this::FOLDER.'GetChapterList_'.strval($bookId).'.json');
			return $this->get_chapter_list($bookId);
		}
		return $res;
	}
	
	public function get_chapter_list_comic($bookId) {
		$cookies=$this->get_cookies_for('https://www.webnovel.com/apiajax/');
		$referer='https://www.webnovel.com/library';
		{
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'comicId'=>strval($bookId),
				'_'=>millitime(),
			);
			$headers=array(
				'Referer: '.$referer,
			);
			//$res = $this->get( 'https://www.webnovel.com/apiajax/comic/GetChapterList', $ar, $headers);
			$res = $this->get( 'https://idruid.webnovel.com/app/api/comic/get-chapters', $ar, $headers);
			file_put_contents('request.log', $res);
			$res=$this->jsonp_to_json($res);
			unlink('request.log');
			file_put_contents($this::FOLDER.'GetChapterList_'.strval($bookId).'.json', $res);
			$res=json_decode($res);
		}
		return $res;
	}
	
	public function get_chapter_list_comic_cached($bookId) {
		if(!file_exists($this::FOLDER.'GetChapterList_'.strval($bookId).'.json')) {
			return $this->get_chapter_list_comic($bookId);
		}
		$res='';
		//var_dump($bookId);
		try {
			$res=file_get_contents($this::FOLDER.'GetChapterList_'.strval($bookId).'.json');
			$res=json_decode($res, false, 512, JSON_THROW_ON_ERROR);
		}
		catch(JsonException $e) {
			//var_dump($e);
			echo '<table class="xdebug-error xe-warning" dir="ltr" border="1" cellspacing="0" cellpadding="1">';echo $e->xdebug_message;echo '</table>';
			//die();
		}
		if(!is_object($res)) {
			var_dump($res);
			unlink($this::FOLDER.'GetChapterList_'.strval($bookId).'.json');
			return $this->get_chapter_list_comic($bookId);
		}
		return $res;
	}
	
	public function get_chapter_stats($bookId) {
		throw new Exception("Not Yet Implemented.");
	}
	
	public function search($name) {
		$ar=array(
			'keywords'=>$name,
		);
		$data = $this->get( 'https://www.webnovel.com/search', $ar);
		$data=trim($data);
		
		file_put_contents($this::FOLDER.'search.html', $data);
		
		$xml=simplexml_load_html($data);
		
		$res=$xml->xpath('//div[@class="tab-content-container j_tab-content"]');
		//var_dump(count($res));
		
		//$xml2=$res[0]->div[0]->div->ul->li[0]->asXML();
		//$xml2=wordwrap($xml2,150);
		//var_dump($xml2);
		
		//var_dump($res);die();
		$results=$res[0]->div[0];
		if(!empty($results->div)) $results=$results->div->ul->li;
		else $results=$results->ul->li;
		$extracts=array();
		foreach($results as $result)
		{
			if((string)$result=='No more results') continue;
			$extract=array();
			$extract['title']=strval($result->a['title']);
			$extract['data-bookname']=strval($result->a['data-bookname']);
			$extract['href']='https:'.strval($result->a['href']);
			$extract['data-bid']=strval($result->a['data-bid']);
			$extract['data-bookid']=strval($result->a['data-bookid']);
			$extract['type']=strval($result->a['data-search-type']);
			$extract['cover']=array();
			$res2=$result->a->img;
			if(is_null($res2)) {
				$res2=$result->a[0]->img;
			}
			if(is_null($res2)) { var_dump($res, $results); die(); }
			foreach($res2 as $link) {
				foreach($res2->attributes() as $attr) {
					foreach(array('http://','https://','//') as $start) {
						if(strncasecmp($start, $link[$attr->getName()], strlen($start))===0) {
							$extract['cover'][$attr->getName()]=strval($link[$attr->getName()]);
						}
					}
				}
			}
			$extract['categories']=array();
			$extract['tags']=array();
			foreach($result->p as $p)
			{
				if($p['class']=='mb8 g_tags') {
					foreach($p->a as $link)
					{
						if(strncasecmp($link['href'], '/category/', 10)==0) {
							$extract['categories'][strval($link['title'])]=strval($link);
						}
						if(strncasecmp($link['href'], '//www.webnovel.com/tags/', 24)==0) {
							$id=explode('_', $link['href']);
							array_pop($id);
							$id=array_pop($id);
							$extract['tags'][$id]=strval($link);
						}
					}
				}
				else if($p['class']=='fs16 c_000 ells _2 lh24') {
					$extract['description']=strval($p);
				}
			}
			$extracts[]=$extract;
		}
		
		file_put_contents($this::FOLDER.'_search.json', json_encode($extracts));
		return $extracts;
	}
	
	public function search2($name) {
		$cookies=$this->get_cookies_for('https://www.webnovel.com/apiajax/');
		$referer='https://www.webnovel.com/';
		$ar=array(
			'_crsfToken'=>$cookies['_csrfToken'],
			'keywords'=>$name,
		);
		$headers=array(
			'X-Requested-With: XMLHttpRequest',
			'Referer: '.$referer,
		);
		//$res = $this->send( 'https://www.webnovel.com/apiajax/search/AutoCompleteAjax', $ar, $headers, false); // no cookies (un-authentified)
		//$res = $this->send( 'https://www.webnovel.com/apiajax/search/AutoCompleteAjax', $ar, $headers);
		$res = $this->send( 'https://www.webnovel.com/go/pcm/search/autoComplete', $ar, $headers, false); // no cookies (un-authentified)
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'search2.json', $res);
		
		$data=json_decode($res);
		if($data->code!=0)
		{
			var_dump($data->code);
			var_dump($data->msg);
			die('autocomplete error');
		}
		if(!property_exists($data, 'data')) {
			return false;
		}
		if(property_exists($data->data, 'books') && count($data->data->books)>0) {
			if(name_compare($name, $data->data->books[0]->name)) {
				$id=strval($data->data->books[0]->id);
				$ar=array(
					'bookId'=>$id,
					'_'=>millitime(),
				);
				
				$ar=array(
					'_csrfToken'=>$cookies['_csrfToken'],
				);
				$res = $this->get( 'https://www.webnovel.com/apiajax/notification/status' );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'status.json', $res);
				//$res=json_decode($res);
				
				$res = $this->get( 'https://www.webnovel.com/apiajax/badge/getHoldBadge' );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'getHoldBadge.json', $res);
				//$res=json_decode($res);
				
				$res = $this->get( 'https://www.webnovel.com/apiajax/index/GetNewVersionFlagAjax' );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'GetNewVersionFlagAjax.json', $res);
				//$res=json_decode($res);
				
				$res = $this->get( 'https://www.webnovel.com/go/pcm/emoji/getEmoji' );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'GetEmoji.json', $res);
				//$res=json_decode($res);
				
				$res = $this->get( 'https://www.webnovel.com/apiajax/gift/getGiftInfo', $ar );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'getGiftInfo.json', $res);
				//$res=json_decode($res);
				
				$ar = array(
					'bookId'=>$id,
					'novelType'=>0,
				);
				$res = $this->send( 'https://www.webnovel.com/apiajax/Library/CheckInLibraryAjax', $ar);
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'CheckInLibraryAjax.json', $res);
				//$res=json_decode($res);
				
				$ar = array(
					'bookId'=>$id,
					'novelType'=>0,
					'_'=>millitime(),
				);
				$res = $this->get( 'https://www.webnovel.com/apiajax/library/GetReadingProgress', $ar );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'GetReadingProgress.json', $res);
				//$res=json_decode($res);
				
				$ar = array(
					'_csrfToken'=>$cookies['_csrfToken'],
					'bookId'=>$id,
					'pageIndex'=>1,
					'pageSize'=>30,
					'orderBy'=>1,
					'novelType'=>0,
					'needSummary'=>1,
					'_'=>millitime(),
				);
				$res = $this->get( 'https://www.webnovel.com/go/pcm/bookReview/get-reviews', $ar );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'get-reviews'.$id.'.json', $res);
				//$res=json_decode($res);
				
				$ar=array(
					'_csrfToken'=>$cookies['_csrfToken'],
					'bookId'=>$id,
					'bookType'=>2,
					'novelType'=>0,
				);
				$res = $this->get( 'https://www.webnovel.com/apiajax/powerStone/getRankInfoAjax', $ar );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'getRankInfoAjax.json', $res);
				//$res=json_decode($res);
				
				$ar=array(
					'_csrfToken'=>$cookies['_csrfToken'],
					'bookId'=>$id,
					'type'=>2,
				);
				$res = $this->get( 'https://www.webnovel.com/go/pcm/recommend/getRecommendList', $ar );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'getRecommendList'.$id.'.json', $res);
				//$res=json_decode($res);
				
				$ar=array(
					'bookId'=>$id,
					'_'=>millitime(),
				);
				$res = $this->get( 'https://www.webnovel.com/apiajax/chapter/GetChapterList', $ar );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'GetChapterList_'.$id.'.json', $res);
				//$res=json_decode($res);
				
				//https://www.webnovel.com/profile/4302108715
			}
		}
		return $data;
	}
	
	public function get_info_html($id) {
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		$res = $this->get( 'https://www.webnovel.com/book/'.$id );
		preg_match_all('#<script(?: (?:nonce|data-nonce|type|id|async|src)="[^"]+")*>(.*?)</script>#is', $res, $matches);
		array_shift($matches);$matches=$matches[0];
		//$matches=array_filter($matches, function($e) {return startswith($e, 'g_data');});
		//var_dump($matches);//die();
		$str ='g_data.pageId="qi_p_bookdetail",g_data.book= ';
		//$str2='g_data=g_data||{},g_data.login=';
		$str2='g_data=g_data||{},g_data.login={},g_data.login.statusCode="-1",g_data.login.user= ';
		$res1=NULL;
		foreach($matches as $m) {
			if(startswith($m, $str)) {
				$res1=trim(substr($m,strlen($str)));
			}
			if(startswith($m, $str2)) {
				$res1=trim(substr($m,strlen($str2)));
			}
		}
		if($res1===NULL) var_dump($matches);
		assert($res1!==NULL);
		
		/*$json=$res1;
		$json=str_replace('\/','/',$json);
		$json=str_replace('\\\\','\\',$json);
		$json=str_replace('\ ', ' ', $json);
		$json=str_replace('\\\'', '\'', $json);
		$json=str_replace('\_', '_', $json);
		$json=str_replace('\<', '<', $json);
		$json=str_replace('\>', '>', $json);
		$json=str_replace('\n', "\n", $json);
		$json=str_replace('\r', '', $json);
		$json=str_replace("\r", '', $json);
		$res2=$json;//*/
		
		//var_dump($res2);
		//file_put_contents('test.json', $res2);
		$res2=$res1;
		$res2=$this->json_to_json($res2);
		//var_dump($res2);die();
		//$res2=json_decode($res2, false, 512, JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_IGNORE);
		$res3=(new CJSON())->decode($res2);
		if(is_array($res3)&&count($res3)==0) {
			var_dump($id,$res3);
			var_dump(strlen($res1));
			//var_dump(strlen($json));
			var_dump(strlen($res2));
			die();
		}
		file_put_contents($this::FOLDER.'book_'.$id.'.json', $res2);
		return $res3;
	}
	
	public function get_info_html_cached($id, $duration=604800) {
		$fn=$this::FOLDER.'book_'.$id.'.json';
		if(file_exists($fn) && (time()-filemtime($fn))<=$duration ) {
			//$res=json_decode(file_get_contents($fn),false, 512, JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_IGNORE);
			$res=(new CJSON())->decode(file_get_contents($fn));
		}
		else {
			$res=$this->get_info_html($id);
		}
		return $res;
	}
	
	public function get_info($id, $types=NULL) {
		// id
		$data=array();
		
		//types
		if($types===NULL) $types=array(0,1,2,3);
		if(is_string($types)) $types=explode(',', $types);
		if(is_int($types)) $types=array($types);
		if(count(array_filter($types, fn($e)=>ctype_digit($e) ))>0) { die('ERROR: bad types.'); }
		if(count($types)==0) { die('ERROR: empty types.'); }
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/apiajax/');
		$referer='https://www.webnovel.com/';
		$ar=array(
			'bookId'=>$id,
		);
		$headers=array(
			//'X-Requested-With: XMLHttpRequest',
			//'Referer: '.$referer,
		);
		
		if(in_array(0, $types)) {
			$res = $this->get( 'https://idruid.webnovel.com/app/api/book/get-book', $ar, $headers);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'get-book'.$id.'.json', $res);
		
			$data[]=json_decode($res);
		}
		
		if(in_array(1, $types)) {
			$res = $this->get( 'https://idruid.webnovel.com/app/api/book/get-book-extended', $ar, $headers);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'get-book-extended'.$id.'.json', $res);
			
			$data[]=json_decode($res);
		}
		
		$ar = array(
			'_csrfToken'=>$cookies['_csrfToken'],
			'bookId'=>$id,
			'pageIndex'=>1,
			'pageSize'=>30,
			'orderBy'=>1,
			'novelType'=>0,
			'needSummary'=>1,
			'_'=>millitime(),
		);
		if(in_array(2, $types)) {
			$res = $this->get( 'https://www.webnovel.com/go/pcm/bookReview/get-reviews', $ar );
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'get-reviews'.$id.'.json', $res);
			
			$data[]=json_decode($res);
		}
		
		$ar=array(
			'_csrfToken'=>$cookies['_csrfToken'],
			'bookId'=>$id,
			'type'=>2,
		);
		if(in_array(3, $types)) {
			$res = $this->get( 'https://www.webnovel.com/go/pcm/recommend/getRecommendList', $ar );
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'getRecommendList'.$id.'.json', $res);
			
			$data[]=json_decode($res);
		}
		
		if(count($types)==1) $data=$data[0];
		
		var_dump('get_info: '.$id.' types '.implode(',', $types));
		return $data;
	}
	
	public function get_info_cached($id, $types=NULL, $duration=604800) {
		$filenames=array(
			$this::FOLDER.'get-book'.$id.'.json',
			$this::FOLDER.'get-book-extended'.$id.'.json',
			$this::FOLDER.'get-reviews'.$id.'.json',
			$this::FOLDER.'getRecommendList'.$id.'.json',
		);
		
		//types
		if($types===NULL) $types=array(0,1,2,3);
		if(is_string($types)) $types=explode(',', $types);
		if(is_int($types)) $types=array($types);
		if(count(array_filter($types, fn($e)=>ctype_digit($e) ))>0) { die('ERROR: bad types.'); }
		if(count($types)==0) { die('ERROR: empty types.'); }
		
		$res=array();
		foreach($filenames as $i=>$fn) {
			if($types==NULL || in_array($i, $types)) {
				$j=0;
				$res2=NULL;
				if(file_exists($fn) && (time()-filemtime($fn))<=$duration ) {
					$res2=json_decode(file_get_contents($fn),false, 512, JSON_THROW_ON_ERROR);
				}
				else {
					$res2=$this->get_info($id, $i);
				}
				//if($res[0]->Result!==0 || $res[1]->Result!==0 || $res[2]->code!==0 || $res[3]->code!==0) $res=$wn->get_info($book->bookId);
				while(property_exists($res2, 'Result') && $res2->Result!==0 && ++$j<5) $res2=$this->get_info($id, $i);
				while(property_exists($res2, 'code') && $res2->code!==0 && ++$j<5) $res2=$this->get_info($id, $i);
				$res[$i]=$res2;
			}
		}
		return $res;
	}
	
	public function add_watch($id, $novelType=0) {
		// id
		$cookies=$this->get_cookies_for('https://www.webnovel.com/apiajax/');
		$referer='https://www.webnovel.com/library';

		$data=array();
		
		$referer='https://www.webnovel.com/';
		$ar=array(
			'_csrfToken'=>$cookies['_csrfToken'],
			'bookIds'=>strval($id),
			'novelType'=>$novelType,
		);
		$headers=array(
			'X-Requested-With: XMLHttpRequest',
			'Referer: '.$referer,
		);
		
		$res = $this->send( 'https://www.webnovel.com/apiajax/Library/AddLibraryItemsAjax', $ar, $headers);
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'AddLibraryItemsAjax.json', $res);
		$res=json_decode($res);
		
		return $res;
	}
	
	public function get_history() {
		// id
		$cookies=$this->get_cookies_for('https://www.webnovel.com/apiajax/');
		$referer='https://www.webnovel.com/library';

		$data=array();
		
		$referer='https://www.webnovel.com/';
		$ar=array(
			'_csrfToken'=>$cookies['_csrfToken'],
		);
		$headers=array(
			'X-Requested-With: XMLHttpRequest',
			'Referer: '.$referer,
		);
		
		$data2=array();
		$pageIndex=0; // starts as 1 for this endpoint, but will be incremented at the top of the loop, so it will starts at 1 and must be init to 0
		$pageSize=20; // default?
		
		do {
			++$pageIndex;
			if($pageIndex>1) $ar['pageIndex']=$pageIndex;
			if($pageIndex==2) $ar['pageSize']=$pageSize=$res->data->total; // only need to be done once, so at index=2
			//$res = $this->get( 'https://www.webnovel.com/apiajax/ReadingHistory/ReadingHistoryAjax', $ar, $headers );
			$res = $this->get( 'https://www.webnovel.com/go/pcm/readingHistory/readingHistoryAjax', $ar, $headers );
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'ReadingHistoryAjax'.$pageIndex.'.json', $res);
			$res=json_decode($res, false, 512, JSON_THROW_ON_ERROR);
			$data2=array_merge($data2, $res->data->historyItems);
			if($pageIndex==1) if($res->data->isLast==0) $pageSize=$res->data->total;
		}
		while(!is_null($res->data) && ((property_exists($res->data,'isLast')&&$res->data->isLast==0) && ($res->data->total>0)) && $pageIndex<=50);
		// i'm at 35 pages
		
		$data[]=$data2;
		
		$data2=array();
		$pageSize=30;
		
		for($i=1;$i<=$pageIndex;$i++) {
			$ar=array(
				'pageIndex'=>$i,
				'pageSize'=>$pageSize,
			);
			
			$res = $this->get( 'https://idruid.webnovel.com/app/api/reading/get-history', $ar, $headers);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'get-history'.$i.'.json', $res);
			$res=json_decode($res, false, 512, JSON_THROW_ON_ERROR);
			
			if($i==1 && $pageIndex>1) assert($pageSize==count($res->Data)) or die('error in ReadingHistoryAjax vs get-history.');
			if(is_null($res->Data)||$res->Result!=0||strlen($res->Message)==0||$res->Message!='Success') {
				break; // history complete
			}
			$data2=array_merge($data2, $res->Data);
		}
		
		$data[]=$data2;
		
		$history=json_encode($data); unset($data2); //
		$history=$this->jsonp_to_json($history);
		file_put_contents($this::FOLDER.'_history.json', $history );
		
		return $data;
	}
	
	public function get_collections() {
		// id
		$cookies=$this->get_cookies_for('https://www.webnovel.com/apiajax/');
		$referer='https://www.webnovel.com/library';

		$data=array();
		
		$referer='https://www.webnovel.com/';
		$ar=array(
		);
		$headers=array(
			'X-Requested-With: XMLHttpRequest',
			'Referer: '.$referer,
		);
		
		$res = $this->get( 'https://idruid.webnovel.com/app/api/book-collection/list', $ar, $headers);
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'book-collection-list.json', $res);
		$res=json_decode($res, false, 512, JSON_THROW_ON_ERROR);
		
		$data[]=$res;
		$data2=array();
		
		$ar=array(
			'collectionId'=>'',
			'pageIndex'=>0, // starts as 1 for this endpoint, but will be incremented at the top of the loop, so it will starts at 1 and must be init to 0
			'pageSize'=>20, // fixed, always 20 even if changed
		);
		foreach($res->Data->Items as $col) {
			$cid=$col->CollectionId;
			$ar['collectionId']=$cid;
			$name=$col->Name;
			$num=$col->BookItems;
			$pageIndex=0;
			$data3=array();
			do {
				++$pageIndex;
				$ar['pageIndex']=$pageIndex;
				$res = $this->get( 'https://idruid.webnovel.com/app/api/book-collection/detail', $ar, $headers);
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'book-collection'.$cid.'-'.$pageIndex.'.json', $res);
				$res=json_decode($res, false, 512, JSON_THROW_ON_ERROR);
				//$data3[]=$res;
				$data3=array_merge($data3, $res->Data->BookItems);
			}
			while($res->Data->IsLast==0);
			$collection=json_encode($data3);
			$collection=$this->jsonp_to_json($collection);
			file_put_contents($this::FOLDER.'book-collection'.$cid.'.json', $collection);
			$data2[$name]=$data3;
		}
		$data[]=$data2;
		
		$collection=json_encode($data2);
		$collection=$this->jsonp_to_json($collection);
		file_put_contents($this::FOLDER.'_collection.json', $collection);
		
		return $data;
	}

	public function get_filter_for($type) {
/*
var_dump($res[0]->Data->Type);//1 translated 2 original
var_dump($res[2]->data->bookStatisticsInfo->bookType); // 1 translated 2 original
var_dump($res[3]->data->bookInfo->bookType); // 1 translated 2 original
var_dump($res[3]->data->bookInfo->type); // 1 translated 2 original
var_dump($res[3]->data->bookInfo->translateMode);//-1 translated 1 original ???
//*/
		if($type=='oel') return function($watch) { return ($this->get_info_cached($watch->bookId))[0]->Data->Type==2; };
		if($type=='translated') return function($watch) { return ($this->get_info_cached($watch->bookId))[0]->Data->Type==1; };
		if($type=='novel') return function($watch) { return ($watch->novelType==0);};
		if($type=='manga'||$type=='comic') return function($watch) { return ($watch->novelType==100);};
		return function($watch) { return false; };
		return parent::get_filter_for($type);
	}
	//https://www.webnovel.com/go/pcm/pcbookcity/page?_csrfToken=&bookCityType=2&sex=1
};
?>