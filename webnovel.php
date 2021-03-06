<?php
require_once('config.php');
class WebNovel extends SitePlugin
{
	public const FOLDER='webnovel/';
	
	public function __construct()
	{
		
	}
	
	public function checkLogin()
	{
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
	
	public function login(string $user, string $pass)
	{
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
			
			preg_match('#(?<=LoginV1\.init\()\{(.*?)\}(?=\);)#s', $res, $matches);
			$json=json_decode($matches[0]);
			
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
			$xml=simplexml_load_html($res);
			//var_dump(strval($xml->script[4])); // number not definitive
			preg_match('#(?<=LoginV1\.init\()\{(.*?)\}(?=\);)#s', $res, $matches);
			$json=json_decode($matches[0]);
			//var_dump($json);
			
			$pub_key=$json->pubkey;
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
		$autoLoginSessionKey=$data->data->autoLoginSessionKey;
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		//var_dump($cookies);
		
		{ // 9 loginSuccess
			//$ar=array();//data?
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get($data->data->returnurl);
			file_put_contents($this::FOLDER.'loginSuccess.htm', $res);
			//$res=json_decode($res);
		}
		
		$referer=$this->lastUrl;
		$referer='https://www.webnovel.com/';
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		
		{ // 9b webnovel
			$res=$this->get( 'https://www.webnovel.com/'); // initialize cookies
			file_put_contents($this::FOLDER.'wn.htm', $res);
		}
		$referer=$this->lastUrl;
		
		{ // 10 login
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'code'=>'',
				'ticket'=>$ticket,
				'guid'=>$userid,
				'forceRedirect'=>'',
			);
			$headers=array(
				'X-Requested-With: XMLHttpRequest',
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://www.webnovel.com/apiajax/login/login', $ar);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'login.json', $res);
			//$res=json_decode($res);
		}
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		
		{ // 11 getUserInfo
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://www.webnovel.com/apiajax/login/getUserInfo', $ar);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'getUserInfo.json', $res);
			//$res=json_decode($res);
		}
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		
		{ // 12 notification-status
			
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://www.webnovel.com/apiajax/notification/status', $ar);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'notification-status.json', $res);
			//$res=json_decode($res);
		}
		return $data;
	}
	
	private function novel_cmp($e1, $e2)
	{
		//usort($books, function($e1, $e2) { $res=strcasecmp($e1->bookName, $e2->bookName); if($res!=0) return $res; return $e1->novelType <=> $e2->novelType; });
		$n1=$n2='';
		if(property_exists($e1, 'bookName')) $n1=$e1->bookName;
		else {
			$res=$this->get_info_cached($e1->bookId);
			$n1=$res[0]->Data->BookName;
		}
		if(property_exists($e2, 'bookName')) $n2=$e2->bookName;
		else {
			$res=$this->get_info_cached($e2->bookId);
			$n2=$res[0]->Data->BookName;
		}
		$res=strcasecmp($n1, $n2);
		if($res!==0) return $res;
		return $e1->novelType <=> $e2->novelType;
	}
	
	public function watches()
	{
		$cookies=$this->get_cookies_for('https://www.webnovel.com/apiajax/');
		$referer='https://www.webnovel.com/library';
		{
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
		}
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
				$res = $this->get( 'https://www.webnovel.com/apiajax/Library/LibraryAjax', $ar);
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'LibraryAjax'.strval($i).'.json', $res);
				$res=json_decode($res);
				//var_dump($i, $res);
			}
			while(++$j<5 && (
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
			if($res->data!==NULL) {
				$books=array_merge($books, $res->data->books);
				++$i;
			}
		}
		while( ( (is_object($res->data)&&$res->data->isLast==0) ) && $i<20);//$i should not reach 20*30 books soon (i'm at 16)
		$books2=json_encode($books);
		$books2=$this->jsonp_to_json($books2);
		file_put_contents($this::FOLDER.'_books2.json', $books2 );//TEMP
		$books2=$books;
		$order=array();
		//usort($books, function($e1, $e2) { $res=strcasecmp($e1->bookName, $e2->bookName); if($res!=0) return $res; return $e1->novelType <=> $e2->novelType; });
		usort($books, array($this, 'novel_cmp'));
		foreach($books as $i=>$b) {
			$ind=-1;
			foreach($books2 as $i2=>$b2) { if($b2==$b) { $ind=$i2; break; } }
			if($b->novelType==0) {
				$res=$this->get_chapter_list_cached($b->bookId);
				if(!property_exists($res, 'data')) {
					$res=$this->get_chapter_list($b->bookId);
				}
				$order[]=[$b->bookId, $b->bookName, $res->data->bookInfo->bookSubName, count($books2)-1-$ind];
			}
			elseif($b->novelType==100) {
				$res=$this->get_chapter_list_comic_cached($b->bookId);
				if(!property_exists($res, 'data')) {
					$res=$this->get_chapter_list_comic($b->bookId);
				}
				$order[]=[$b->bookId, $b->comicName, $res->data->comicInfo->comicName, count($books2)-1-$ind];
			}
		}
		$books2=json_encode($books); unset($books); //
		$books2=$this->jsonp_to_json($books2);
		file_put_contents($this::FOLDER.'_books.json', $books2 );
		$order2=json_encode($order); unset($order);
		$order2=$this->jsonp_to_json($order2);
		file_put_contents($this::FOLDER.'_order.json', $order2 );
		return json_decode($books2);
	}
	
	public function read_update($watch, $chp)
	{
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
		if($res->data->volumeItems[0]->index==0) $add=-$res->data->volumeItems[0]->chapterCount; // substract auxiliary volume chapters
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
					if($chapter->index == $chp && $chapter->chapterLevel==0) {
						$cid = $chapter->id;
						$found=true;
						$update=true;
					}
					if($chapter->index>$cid_max_num && $chapter->index<=$chp && $chapter->chapterLevel==0) {
						$cid_max_num=$chapter->index;
						$cid_max=$chapter->id;
					}
				}
			}
		}
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
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'bookId'=>strval($watch->bookId),
				'chapterId'=>($cid>0?$cid:$cid_max),
			);
			//var_dump($ar);
			$res = $this->send( 'https://www.webnovel.com/apiajax/Library/SetReadingProgressAjax', $ar );
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'SetReadingProgressAjax.json', $res);
			$res=json_decode($res);
		}
		else {
			$res=false;
		}
		return $res;
	}
	
	public function get_chapter_list($bookId)
	{
		$cookies=$this->get_cookies_for('https://www.webnovel.com/apiajax/');
		$referer='https://www.webnovel.com/library';
		{
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'bookId'=>strval($bookId),
				'_'=>millitime(),
			);
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get( 'https://www.webnovel.com/apiajax/chapter/GetChapterList', $ar, $headers);
			file_put_contents('request.log', $res);
			$res=$this->jsonp_to_json($res);
			unlink('request.log');
			file_put_contents($this::FOLDER.'GetChapterList_'.strval($bookId).'.json', $res);
			$res=json_decode($res);
		}
		return $res;
	}
	
	public function get_chapter_list_cached($bookId)
	{
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
	
	public function get_chapter_list_comic($bookId)
	{
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
			$res = $this->get( 'https://www.webnovel.com/apiajax/comic/GetChapterList', $ar, $headers);
			file_put_contents('request.log', $res);
			$res=$this->jsonp_to_json($res);
			unlink('request.log');
			file_put_contents($this::FOLDER.'GetChapterList_'.strval($bookId).'.json', $res);
			$res=json_decode($res);
		}
		return $res;
	}
	
	public function get_chapter_list_comic_cached($bookId)
	{
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
	
	public function search($name)
	{
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
	
	public function search2($name)
	{
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
	
	public function get_info($id, $types=NULL)
	{
		// id
		$data=array();
		
		//types
		if($types===NULL) $types=array(0,1,2,3);
		if(is_string($types)) $types=explode(',', $types);
		if(is_int($types)) $types=array($types);
		if(count(array_filter($types, fn($e)=>ctype_digit($e) ))>0) { die('ERROR: bad types.'); }
		if(count($types)==0) { die('ERROR: empty types.'); }
		
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
	
	public function get_info_cached($id) {
		$filenames=array(
			$this::FOLDER.'get-book'.$id.'.json',
			$this::FOLDER.'get-book-extended'.$id.'.json',
			$this::FOLDER.'get-reviews'.$id.'.json',
			$this::FOLDER.'getRecommendList'.$id.'.json',
		);
		$res=array();
		foreach($filenames as $i=>$fn) {
			$j=0;
			$res2=NULL;
			if(file_exists($fn)) {
				$res2=json_decode(file_get_contents($fn),false, 512, JSON_THROW_ON_ERROR);
			}
			else {
				$res2=$this->get_info($id, $i);
			}
			//if($res[0]->Result!==0 || $res[1]->Result!==0 || $res[2]->code!==0 || $res[3]->code!==0) $res=$wn->get_info($book->bookId);
			while(property_exists($res2, 'Result') && $res2->Result!==0 && ++$j<5) $res2=$this->get_info($id, $i);
			while(property_exists($res2, 'code') && $res2->code!==0 && ++$j<5) $res2=$this->get_info($id, $i);
			$res[]=$res2;
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
			$res = $this->get( 'https://www.webnovel.com/apiajax/ReadingHistory/ReadingHistoryAjax', $ar, $headers );
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'ReadingHistoryAjax'.$pageIndex.'.json', $res);
			$res=json_decode($res, false, 512, JSON_THROW_ON_ERROR);
			$data2[]=$res;
			if($pageIndex==1) if($res->data->isLast==0) $pageSize=$res->data->total;
		}
		while($res->data->isLast==0);
		
		$data[]=$data2;
		
		$data2=array();
		
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
			$data2[]=$res;
		}
		
		$data[]=$data2;
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
		file_put_contents($this::FOLDER.'book-collection.json', $res);
		$res=json_decode($res, false, 512, JSON_THROW_ON_ERROR);
		
		$data[]=$res;
		
		$data2[]=array();
		
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
				$data3[]=$res;
			}
			while($res->Data->IsLast==0);
			$data2[]=$data3;
		}
		$data[]=$data2;
		
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
};
?>