<?php
require_once('config.php');
require_once('SitePlugin.inc.php');

class RoyalRoad extends SitePlugin
{
	public const FOLDER = 'royalroad/';
	
	public function __construct() {
		
	}
	
	public function checkLogin() {
		$loggued = -1;
		$res = $this->send( 'https://www.royalroad.com/home' );
		
		$fn=$this::FOLDER.'rr.htm';
		file_put_contents($fn, $res);//*/
		clearstatcache(false, $fn);
		
		//$res = file_get_contents($this::FOLDER.'rr.htm');
		$xml = simplexml_load_html($res);
		$res = $xml->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' fa-sign-in ')]");
		//$this->dump($res);
		if(count($res)>0) $loggued=0;
		$res = $xml->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' fa-sign-out ')]");
		//$this->dump($res);
		if(count($res)>0) $loggued=1;
		//$this->dump($loggued);
		//die();
		return $loggued;
	}
	
	public function login(string $user, string $pass) {
		$ar = array(
			'ReturnUrl'=>'/welcome',
			'email'=>$user,
			'Password'=>$pass,
			'Remember'=>'true',
		);
		$res = $this->send( 'https://www.royalroad.com/account/login?returnurl=%2Fwelcome', $ar);
		file_put_contents($this::FOLDER.'login.htm', $res);
		if(strpos($res, '<title>Successfully logged in. | Royal Road</title>')!==0) return true;
		$this->dump($res);
		return false;
	}
	
	public function watches() {
		$i=1;
		$count=0;
		$count2=0;
		$pages=array();
		$skip=array('&laquo; First', '&lsaquo; Previous', 'Next &rsaquo;', 'Last &raquo;');
		$ar = array();
		$cookies = $this->get_cookies_for( 'https://www.royalroad.com/' );
		$data=array();
		$order=array();
		//$this->dump($cookies);die();
		do
		{
			if($i!=0) {
				$ar = array(
					'page'=>$i,
				);
			}
			$res = $this->get( 'https://www.royalroad.com/my/follows', $ar, array(), $cookies );
			//$this->dump($res);
			file_put_contents($this::FOLDER.'follows'.$i.'.htm', $res);
			$xml = simplexml_load_html($res);
			//$this->dump($xml);
			// /html/body/div[3]/div/div/div/div/div/div[2]/div[2]/div[2]/div[2]/div[1]
			$res2=$xml->xpath("//div[@class='fiction-list']/div");
			$count2=0;
			foreach($res2 as $node) {
				//$this->dump($node);die();
				$ar2=array();
				if(property_exists($node, 'h6') && (string)$node->h6=='Advertisement') continue;
				if(!property_exists($node, 'figure')) {
					$this->dump($node);
					var_dump($this->msg);die(); // TODO : remove
				}
				$count2++;
				$ar2['cover']=(string)$node->figure->img['src'];
				$ar2['title']=trim((string)$node->div->h2->a);
				$ar2['href']=(string)$node->div->h2->a['href'];
				$ar2['author']=(string)$node->div->div[0]->span->a;
				$ar2['author-href']=(string)$node->div->div[0]->span->a['href'];
				$ar2['next-chp']=(string)$node->div->div[1]->a['href'];
				if(is_iterable($node->div->ul->li) && count($node->div->ul->li)>=2) {
					if((string)$node->div->ul->li[0]->strong!='The last update has been deleted') {
						$ar2['last-upd-text']=trim((string)$node->div->ul->li[0]);
						$ar2['last-upd-href']=(string)$node->div->ul->li[0]->a['href'];
						$ar2['last-upd-title']=trim((string)$node->div->ul->li[0]->a->span[0]);
						$ar2['last-upd-date']=(string)$node->div->ul->li[0]->a->span[1]->time['title'];
						$ar2['last-upd-ago']=(string)$node->div->ul->li[0]->a->span[1]->time;
					}
					$ar2['last-read-text']=trim((string)$node->div->ul->li[1]);
					$ar2['last-read-href']=(string)$node->div->ul->li[1]->a['href'];
					$ar2['last-read-title']=trim((string)$node->div->ul->li[1]->a->span[0]);
					$ar2['last-read-date']=(string)$node->div->ul->li[1]->a->span[1]->time['title'];
					$ar2['last-read-ago']=(string)$node->div->ul->li[1]->a->span[1]->time;
				}
				else {
					if(property_exists($node->div->ul->li, 'strong')) {
						if((string)$node->div->ul->li->strong!='The last update has been deleted') {
							$ar2['last-upd-text']=trim((string)$node->div->ul->li);
							if(!is_iterable($node->div->ul->li->a->span)) {
								$this->dump($node->div->ul->li);
								var_dump($this->msg);die();
							}
							$ar2['last-upd-href']=(string)$node->div->ul->li->a['href'];
							$ar2['last-upd-title']=trim((string)$node->div->ul->li->a->span[0]);
							$ar2['last-upd-date']=(string)$node->div->ul->li->a->span[1]->time['title'];
							$ar2['last-upd-ago']=(string)$node->div->ul->li->a->span[1]->time;
						}
						else {
							//$this->dump($node->div->ul->li);die();
						}
					}
					else {
						$ar2['last-read-text']=trim((string)$node->div->ul->li);
						if(!property_exists($node->div->ul->li->a, 'span')) {
							$this->dump($node->div->ul->li);
							var_dump($this->msg);die();
						}
						$ar2['last-read-href']=(string)$node->div->ul->li->a['href'];
						$ar2['last-read-title']=trim((string)$node->div->ul->li->a->span[0]);
						$ar2['last-read-date']=(string)$node->div->ul->li->a->span[1]->time['title'];
						$ar2['last-read-ago']=(string)$node->div->ul->li->a->span[1]->time;
					}
				}
				//if(in_array($ar2['title'], array('The Last Battlemage', 'The Humble Life of a Skill Trainer', 'A Hero\'s Song', 'Evil Overlord: The Makening'))) { $this->dump(__LINE__, $ar2); }
				$id=explode('/', $ar2['href'])[2];
				$data[$id]=$ar2;
				$order[]=$id;
				//$this->dump($ar2);die();
			}
			$res2=$xml->xpath("//a[@data-page]");
			foreach($res2 as $node) {
				if(in_array((string)$node, $skip)) continue;
				if(array_key_exists( (string)$node['data-page'], $pages)) continue;
				$pages[(string)$node['data-page']]=(string)$node['href'];
			}
			if($i>=5 && $count2==50) $pages[$i+1]='/my/follows?page='.($i+1); // TODO : verify it's been fixed [temporary fix for bug #13041]
			//$this->dump($i, strlen($res));
			$i++;
			$count++;
			sleep(1);
		} while($i<=count($pages) && $count<20 ); // count set as 20, currently at 5
		//$this->dump($pages);
		ksort($data);
		$res=json_encode($data);
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'_books.json', $res);
		
		$res=json_encode($order);
		$res=$this->jsonp_to_json($res);
		file_put_contents($this::FOLDER.'_order.json', $res);
		
		return $data;
	}
	
	public function get_watches() {
		$res=json_decode(file_get_contents($this::FOLDER.'_books.json'), false, 512, JSON_THROW_ON_ERROR);
		if(is_object($res)) $res=get_object_vars($res);
		return $res;
	}
	
	public function get_list() {
		throw new Exception('Not available on RoyalRoad.');
	}
	
	public function get_order() {
		$res=json_decode(file_get_contents($this::FOLDER.'_order.json'), false, 512, JSON_THROW_ON_ERROR);
		if(is_object($res)) $res=get_object_vars($res);
		return $res;
	}
	
	public function info($fictionId) {
		$res = $this->get( 'https://www.royalroad.com/fiction/'.$fictionId );
		file_put_contents($this::FOLDER.'fiction_'.$fictionId.'.htm', $res);
		//$res=file_get_contents($this::FOLDER.'fiction_'.$fictionId.'.htm'); // NOTE : during DEBUG
		$xml=simplexml_load_html($res);
		
		$ar=array();
		
		$res=$xml->xpath("//div[@class='row fic-header']");
		assert(count($res)==1);
		$res=$res[0];
		$ar['info']=array();
		$ar['info']['cover']=(string)$res->div[0]->img['src'];
		$ar['info']['name']=trim((string)$res->div[1]->div->h1);
		$ar['info']['author-name']=trim((string)$res->div[1]->div->h4->span[1]->a);
		$ar['info']['author-href']=(string)$res->div[1]->div->h4->span[1]->a['href'];
		
		$res=$xml->xpath("//div[@class='fiction-info']");
		assert(count($res)==1);
		$res=$res[0];
		
		$ar['info']['tags']=array();
		$res2=$res->div[0]->div[1]->div[0];
		foreach($res2 as $node) {
			if(!isset($node->a)) {
				$ar['info']['tags'][]=trim((string)$node);
			}
			else {
				foreach($node->a as $node2) {
					$ar['info']['tags'][]=array('name'=>(string)$node2, 'href'=>(string)$node2['href']);
				}
			}
		}
		unset($res2);
		
		$div_desc=NULL;
		$ar['info']['warnings']=array();
		$res2=$res->div[0]->div[1]->div[1];
		//assert((string)$res2->strong=='Warning' || (string)$res2->span=='This fiction contains:');
		if((string)$res2->strong=='Warning' || (string)$res2->span=='This fiction contains:') {
			$res2=$res2->ul->li;
			foreach($res2 as $node) {
				$ar['info']['warnings'][]=(string)$node;
			}
			$div_desc=2;
		}
		else {
			$div_desc=1;
		}
		
		$ar['info']['description']=(string)trim(strip_tags($res->div[0]->div[1]->div[$div_desc]->asXML()));
		
		$res2=$res->div[1]->div->div;
		$ar['stats']=array();
		foreach($res2->div[0]->meta as $node) {
			$ar['stats'][(string)$node['property']]=(string)$node['content'];
		}
		$res3=$res2->div[0]->ul->li;
		assert(count($res3)%2==0); // NOTE : 0-indexed, even is name, odd is value
		for($i=0;$i<count($res3);$i+=2) {
			$ar['stats'][(string)$res3[$i]]=(string)$res3[$i+1]->span['data-content'];
		}
		$res3=$res2->div[1]->ul->li;
		assert(count($res3)%2==0); // NOTE : 0-indexed, even is name, odd is value
		for($i=0;$i<count($res3);$i+=2) {
			$ar['stats'][trim((string)$res3[$i])]=(string)$res3[$i+1];
		}
		unset($res3);
		unset($res2);
		
		$res=$xml->xpath("//div[@class='volumes-carousel']/div");
		$ar['volumes']=array();
		foreach($res as $node) {
			$id=(string)$node['data-volume-id'];
			$name=trim((string)$node->div->h6);
			$ar['volumes'][$id]=array('name'=>$name);
		}
		
		$res=$xml->xpath("//table[@id='chapters']/tbody/tr");
		$ar2=array();
		$count=0;
		foreach($res as $node) {
			$ar3=array();
			// <i class="fa fa-caret-right popovers" data-trigger="hover" data-container="body" data-placement="top" data-original-title="Reading Progress" data-content="This is the last chapter you've opened"></i>
			$attrs=((array)($node->attributes()))['@attributes'];
			if(array_key_exists('data-volume-id',$attrs)) $ar3['data-volume-id']=(string)$attrs['data-volume-id'];
			$ar3['href']=(string)$node->td[0]->a['href'];
			$ar3['title']=trim((string)$node->td[0]->a);
			$ar3['date']=(string)$node->td[1]->a->time['title'];
			$ar3['ago']=trim((string)$node->td[1]->a->time);
			if(isset($node->td[0]->i)) {
				$ar3['pos-title']=(string)$node->td[0]->i['data-original-title'];
				$ar3['pos-content']=(string)$node->td[0]->i['data-content'];
			}
			$ar2[$count]=$ar3;
			$count++;
		}
		$ar['chapters']=$ar2;
		unset($ar2);
		
		foreach($ar['volumes'] as $id=>&$ar2) {
			$ar2['count']=count(array_filter($ar['chapters'], fn($e) => array_key_exists('data-volume-id', $e) && $e['data-volume-id']==$id));
		}
		
		//$this->dump($ar);die();
		
		$res2=json_encode($ar);
		$res2=$this->jsonp_to_json($res2);
		file_put_contents($this::FOLDER.'fiction_'.$fictionId.'.json', $res2);
		
		$this->dump('get_info : '.$fictionId.' : '.$ar['info']['name']);
		
		//return $ar['chapters']; // NOTE : compatibility for old format (direct chapter list)
		return $ar;
	}
	
	public function get_info_cached($fictionId, $duration=604800) {
		$fn=$this::FOLDER.'fiction_'.$fictionId.'.json';
		if(file_exists($fn) && time()-filemtime($fn)<$duration) {
			$res=json_decode(file_get_contents($fn), false, 512, JSON_THROW_ON_ERROR);
			//return $res['chapters']; // NOTE : compatibility for old format (direct chapter list)
			return $res;
		}
		else {
			return $this->info($fictionId);
		}
	}
	
	public function chapter_list($fictionId) {
		$ar=$this->info($fictionId);
		return array_intersect_key($ar, array('chapters'=>array(), 'volumes'=>array()));
	}
	
	public function get_chapter_list_cached($fictionId, $duration=604800) {
		$fn=$this::FOLDER.'fiction_'.$fictionId.'.json';
		if(file_exists($fn) && time()-filemtime($fn)<$duration) {
			$res=json_decode(file_get_contents($fn), false, 512, JSON_THROW_ON_ERROR);
			//return $res['chapters']; // NOTE : compatibility for old format (direct chapter list)
			$res=get_object_vars($res);
			return array_intersect_key($res, array('chapters'=>array(), 'volumes'=>array()));
		}
		else {
			return $this->chapter_list($fictionId);
		}
	}
	
	public function get_names($id) {
		$res3=$this->get_info_cached($id);
		$names=array(
			$res3->info->name
		);
		return $names;
	}
};
?>