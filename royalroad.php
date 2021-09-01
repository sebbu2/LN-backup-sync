<?php
require_once('config.php');
class RoyalRoad extends SitePlugin
{
	public const FOLDER = 'royalroad/';
	
	public function __construct()
	{
		
	}
	
	public function checkLogin()
	{
		$loggued = -1;
		$res = $this->send( 'https://www.royalroad.com/home' );
		file_put_contents($this::FOLDER.'rr.htm', $res);//*/
		//$res = file_get_contents($this::FOLDER.'rr.htm');
		$xml = simplexml_load_html($res);
		$res = $xml->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' fa-sign-in ')]");
		//var_dump($res);
		if(count($res)>0) $loggued=0;
		$res = $xml->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' fa-sign-out ')]");
		//var_dump($res);
		if(count($res)>0) $loggued=1;
		//var_dump($loggued);
		//die();
		return $loggued;
	}
	
	public function login(string $user, string $pass)
	{
		$ar = array(
			'ReturnUrl'=>'/welcome',
			'email'=>$user,
			'Password'=>$pass,
			'Remember'=>'true',
		);
		$res = $this->send( 'https://www.royalroad.com/account/login?returnurl=%2Fwelcome', $ar);
		file_put_contents($this::FOLDER.'login.htm', $res);
		if(strpos($res, '<title>Successfully logged in. | Royal Road</title>')!==0) return true;
		var_dump($res);
		return false;
	}
	
	public function watches()
	{
		$i=1;
		$count=0;
		$pages=array();
		$skip=array('&laquo; First', '&lsaquo; Previous', 'Next &rsaquo;', 'Last &raquo;');
		$ar = array();
		$cookies = $this->get_cookies_for( 'https://www.royalroad.com/' );
		$data=array();
		//var_dump($cookies);die();
		do
		{
			if($i!=0) {
				$ar = array(
					'page'=>$i,
				);
			}
			$res = $this->get( 'https://www.royalroad.com/my/follows', $ar, array(), $cookies );
			//var_dump($res);
			file_put_contents($this::FOLDER.'follows'.$i.'.htm', $res);
			$xml = simplexml_load_html($res);
			//var_dump($xml);
			// /html/body/div[3]/div/div/div/div/div/div[2]/div[2]/div[2]/div[2]/div[1]
			$res2=$xml->xpath("//div[@class='fiction-list']/div");
			foreach($res2 as $node) {
				//var_dump($node);die();
				$ar2=array();
				if(property_exists($node, 'h6') && (string)$node->h6=='Advertisement') continue;
				if(!property_exists($node, 'figure')) {
					var_dump($node);die(); // TODO : remove
				}
				$ar2['cover']=(string)$node->figure->img['src'];
				$ar2['title']=(string)$node->div->h2->a;
				$ar2['href']=(string)$node->div->h2->a['href'];
				$ar2['author']=(string)$node->div->div[0]->span->a;
				$ar2['author-href']=(string)$node->div->div[0]->span->a['href'];
				$ar2['next-chp']=(string)$node->div->div[1]->a['href'];
				if(is_iterable($node->div->ul->li) && count($node->div->ul->li)>=2) {
					$ar2['last-upd-text']=trim((string)$node->div->ul->li[0]);
					$ar2['last-upd-href']=(string)$node->div->ul->li[0]->a['href'];
					$ar2['last-upd-title']=(string)$node->div->ul->li[0]->a->span[0];
					$ar2['last-upd-date']=(string)$node->div->ul->li[0]->a->span[1]->time['title'];
					$ar2['last-upd-ago']=(string)$node->div->ul->li[0]->a->span[1]->time;
					$ar2['last-read-href']=(string)$node->div->ul->li[1]->a['href'];
					$ar2['last-read-title']=(string)$node->div->ul->li[1]->a->span[0];
					$ar2['last-read-date']=(string)$node->div->ul->li[1]->a->span[1]->time['title'];
					$ar2['last-read-ago']=(string)$node->div->ul->li[1]->a->span[1]->time;
				}
				else {
					if(property_exists($node->div->ul->li, 'strong') && (string)$node->div->ul->li->strong!='The last update has been deleted') {
						$ar2['last-upd-text']=trim((string)$node->div->ul->li);
						$ar2['last-upd-href']=(string)$node->div->ul->li->a['href'];
						if(!is_iterable($node->div->ul->li->a->span)) {
							var_dump($node->div->ul->li);
							die();
						}
						$ar2['last-upd-title']=(string)$node->div->ul->li->a->span[0];
						$ar2['last-upd-date']=(string)$node->div->ul->li->a->span[1]->time['title'];
						$ar2['last-upd-ago']=(string)$node->div->ul->li->a->span[1]->time;
					}
				}
				$id=explode('/', $ar2['href'])[2];
				$data[$id]=$ar2;
				//var_dump($ar2);die();
			}
			$res2=$xml->xpath("//a[@data-page]");
			foreach($res2 as $node) {
				if(in_array((string)$node, $skip)) continue;
				if(array_key_exists( (string)$node['data-page'], $pages)) continue;
				$pages[(string)$node['data-page']]=(string)$node['href'];
			}
			//var_dump($i, strlen($res));
			$i++;
			$count++;
		} while($i<=count($pages) && $count<20 ); // count set as 20, currently at 4
		//var_dump($pages);
		ksort($data);
		$res=json_encode($data);
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'_books.json', $res);
		
		return $data;
	}
	
	public function get_chapter_list($fictionId)
	{
		$res = $this->get( 'https://www.royalroad.com/fiction/'.$fictionId );
		file_put_contents($this::FOLDER.'fiction_'.$fictionId.'.htm', $res);
		$xml=simplexml_load_html($res);
		$res=$xml->xpath("//table[@id='chapters']/tbody/tr");
		$ar2=array();
		$count=0;
		foreach($res as $node) {
			$ar3=array();
			$ar3['href']=(string)$node->td[0]->a['href'];
			$ar3['title']=trim((string)$node->td[0]->a);
			$ar3['date']=(string)$node->td[1]->a->time['title'];
			$ar3['ago']=trim((string)$node->td[1]->a->time);
			$ar2[$count]=$ar3;
			$count++;
		}
		$res=json_encode($ar2);
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'fiction_'.$fictionId.'.json', $res);
		return $ar2;
	}
	
	public function get_chapter_list_cached($fictionId, $duration=604800)
	{
		$fn=$this::FOLDER.'fiction_'.$fictionId.'.json';
		if(file_exists($fn) && time()-filemtime($fn)<$duration) {
			return file_get_contents($fn);
		}
		else {
			return get_chapter_list($fictionId);
		}
	}
};
?>