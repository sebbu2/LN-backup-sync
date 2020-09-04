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
			'_csrfToken'=>$cookies['_csrfToken'],
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
		}
		
		$cookies=$this->get_cookies_for('https://www.webnovel.com/');
		var_dump($cookies);
		
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
			foreach($xml->xpath('//div[@class="m-bts m-login-bts"]/a') as $node) {
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
			var_dump($json);
			
			$pub_key=$json->pubkey;
			$res2=openssl_get_publickey($pub_key);
			if(!is_resource($res2)) {
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
		var_dump($cookies);
		
		{ // 9 loginSuccess
			//$ar=array();//data?
			$headers=array(
				'Referer: '.$referer,
			);
			$res = $this->get($data->data->returnurl);
			file_put_contents($this::FOLDER.'loginSuccess.htm', $res);
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
		}
		return $data;
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
			$res=json_decode($res);
		}
		$i=1;
		$books=array();
		do {
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'pageIndex'=>$i,
				'orderBy'=>'2',
			);
			$res = $this->get( 'https://www.webnovel.com/apiajax/Library/LibraryAjax', $ar);
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'LibraryAjax'.strval($i).'.json', $res);
			$res=json_decode($res);
			$books=array_merge($books, $res->data->books);
			++$i;
		}
		while($res->data->isLast==0);
		file_put_contents($this::FOLDER.'_books.json', json_encode($books));
		return json_encode($books);
	}
	
	public function read_update($watch, $chp)
	{
		$cookies=$this->get_cookies_for('https://www.webnovel.com/apiajax/');
		$referer='https://www.webnovel.com/library';
		{
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
		}
		
		$cid=0;
		$cid_max=0;
		$cid_max_num=0;
		$found=false;
		{
			foreach($res->data->volumeItems as $volume) {
				foreach($volume->chapterItems as $chapter) {
					if($chapter->index == $chp && $chapter->chapterLevel==0) {
						$cid = $chapter->id;
						$found=true;
					}
					if($chapter->index>$cid_max_num && $chapter->index<=$chp && $chapter->chapterLevel==0) {
						$cid_max_num=$chapter->index;
						$cid_max=$chapter->id;
					}
				}
			}
		}
		if(!$found) {
			var_dump('Updating to '.$cid_max_num.' instead of '.$chp.'.');
		}
		
		{
			$ar=array(
				'_csrfToken'=>$cookies['_csrfToken'],
				'bookId'=>strval($watch->bookId),
				'chapterId'=>($cid>0?$cid:$cid_max),
			);
			var_dump($ar);
			$res = $this->send( 'www.webnovel.com/apiajax/Library/SetReadingProgressAjax', $ar );
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'SetReadingProgressAjax'.strval($watch->bookId).'.json', $res);
			$res=json_decode($res);
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
			$res=$this->jsonp_to_json($res);
			file_put_contents($this::FOLDER.'GetChapterList_'.strval($bookId).'.json', $res);
			$res=json_decode($res);
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
		var_dump(count($res));
		
		//$xml2=$res[0]->div[0]->div->ul->li[0]->asXML();
		//$xml2=wordwrap($xml2,150);
		//var_dump($xml2);
		
		$results=$res[0]->div[0]->div->ul->li;
		$extracts=array();
		foreach($results as $result)
		{
			$extract=array();
			$extract['title']=strval($result->a['title']);
			$extract['data-bookname']=strval($result->a['data-bookname']);
			$extract['href']='https:'.strval($result->a['href']);
			$extract['data-bid']=strval($result->a['data-bid']);
			$extract['data-bookid']=strval($result->a['data-bookid']);
			$extract['type']=strval($result->a['data-search-type']);
			$extract['cover']=array();
			$res2=$result->a->img;
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
		
		file_put_contents($this::FOLDER.'search.json', json_encode($extracts));
		return $extracts;
	}
	
	public function search2($name)
	{
		$referer='https://www.webnovel.com/';
		$ar=array(
			'keywords'=>$name,
		);
		$headers=array(
			'X-Requested-With: XMLHttpRequest',
			//'Referer: '.$referer,
		);
		$res = $this->send( 'https://www.webnovel.com/apiajax/search/AutoCompleteAjax', $ar, $headers, false); // no cookies (un-authentified)
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'search2.json', $res);
		
		$data=json_decode($res);
		if($data->code!=0)
		{
			var_dump($data->code);
			var_dump($data->msg);
			die('autocomplete error');
		}
		if(count($data->data->books)>0) {
			if(name_compare($name, $data->data->books[0]->name)) {
				$id=$data->data->books[0]->id;
				$ar=array(
					'bookId'=>$id,
					'_'=>millitime(),
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
				file_put_contents($this::FOLDER.'GetNewVersionFlagAjax.json', $res);
				//$res=json_decode($res);
				
				$ar = array(
					'bookId'=>$id,
					'novelType'=>0,
					'_'=>millitime(),
				);
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
				$res=json_decode($res);
				
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
				file_put_contents($this::FOLDER.'get-reviews.json', $res);
				$res=json_decode($res);
				
				$ar=array(
					'bookId'=>$id,
					'bookType'=>2,
					'novelType'=>0,
				);
				$res = $this->get( 'https://www.webnovel.com/apiajax/powerStone/getRankInfoAjax', $ar );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'getRankInfoAjax.json', $res);
				$res=json_decode($res);
				
				$ar=array(
					'bookId'=>$id,
					'type'=>2,
				);
				$res = $this->get( 'https://www.webnovel.com/go/pcm/recommend/getRecommendList', $ar );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'getRecommendList.json', $res);
				$res=json_decode($res);
				
				$ar=array(
					'bookId'=>$id,
					'_'=>millitime(),
				);
				$res = $this->get( 'https://www.webnovel.com/apiajax/chapter/GetChapterList', $ar );
				$res=$this->jsonp_to_json($res);
				file_put_contents($this::FOLDER.'GetChapterList.json', $res);
				$res=json_decode($res);
				
				//https://www.webnovel.com/profile/4302108715
			}
		}
		return $data;
	}
	
	public function get_info($name)
	{
		//name or id
	}
};
?>