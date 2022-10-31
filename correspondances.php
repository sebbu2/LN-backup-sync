<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('CJSON.php');
require_once('vendor/autoload.php');

require_once('wlnupdates.php');
require_once('webnovel.php');
require_once('royalroad.php');

if(!defined('MOONREADER_DID')) define('MOONREADER_DID', '1454083831785');
if(!defined('MOONREADER_DID2')) define('MOONREADER_DID2', '9999999999999');

define('IGNORE_DUPLICATES', false);

//if(!defined('DROPBOX_DONE')||!DROPBOX_DONE) $sub=true;
if(direct()) include('header.php');

//include_once('position.php');

if(direct()) {
	if(enabled('wln')) {
		$wln=new WLNUpdates();
		$wln_error_count=0;
	}
	
	if(enabled('wn')) {
		$wn=new WebNovel;
		$res=$wn->checkLogin();
		if($res->code!=0) {
			var_dump($res->code, $res->msg);
			$res=$wn->login( $accounts['WebNovel']['user'], $accounts['WebNovel']['pass']);
			var_dump($res);
			die();
		}
	}
	
	if(enabled('rr')) {
		$rr=new RoyalRoad;
		$res=$rr->checkLogin();
		if($res!==1) {
			var_dump($res);
			$res=$rr->login( $accounts['RoyalRoad']['user'], $accounts['RoyalRoad']['pass'] );
			var_dump($res);
			die();
		}
	}
	
	if(enabled('wln')) {
		$wln_list=$wln->get_list();
		$wln_order=$wln->get_order();
		$wln_books=$wln->get_watches();
	}
	else
		$wln_list=$wln_order=$wln_books=array();
	
	if(enabled('wn')) {
		try {
			$ex=false;
			$wn_list=$wn->get_list();
		}
		catch(Exception) {
			$ex=true;
		}
		finally {
			assert($ex);
		}
		$wn_order=$wn->get_order();
		$wn_books=$wn->get_watches();
	}
	else
		$wn_list=$wn_order=$wn_books=array();
	
	if(enabled('rr')) {
		try {
			$ex=false;
			$rr_list=$rr->get_list();
		}
		catch(Exception) {
			$ex=true;
		}
		finally {
			assert($ex);
		}
		$rr_order=$rr->get_order();
		$rr_books=$rr->get_watches();
	}
	else
		$rr_list=$rr_order=$rr_books=array();
	
	function sum_count($carry, $item) { $carry+=count($item); return $carry; }

	$wln_sum=0;
	$wln_wn=0;
	$wln_rr=0;
	$wln_sh=0;
	$wln_wp=0;
	$wln_others=0;
	foreach( $wln_order as $k=>$v) {
		//var_dump($k, count($v));
		//$v2=$wln_books[$v];
		if(substr($k.' ', 0, 7)==='QIDIAN ') $wln_wn+=count($v);
		else if(substr($k.' ', 0, 10)==='RoyalRoad ') $wln_rr+=count($v);
		else if(substr($k.' ', 0, 12)==='ScribbleHub ') $wln_sh+=count($v);
		else if(substr($k.' ', 0, 8)==='WattPad ') $wln_wp+=count($v);
		else $wln_others+=count($v);
		$wln_sum+=count($v);
	}
	var_dump(array_keys($wln_order));//*/
	$wlns=array('sum'=>$wln_sum, 'wn'=>$wln_wn, 'rr'=>$wln_rr, 'sh'=>$wln_sh, 'wp'=>$wln_wp, 'others'=>$wln_others);
	var_dump($wlns);

	//var_dump(array('wln'=>array_reduce($wln_books['data'][0], 'sum_count'), 'wn'=>count($wn_books), 'rr'=>count($rr_books)));
	var_dump(array('wln'=>$wln_sum, 'wn'=>count($wn_books), 'rr'=>count($rr_books)));

	$wn_=array('novel'=>0, 'novel translated'=>0, 'novel original'=>0, 'novel others'=>0, 'comic'=>0, 'others'=>0);
	$tr_=array('-1'=>0,'0'=>0,'1'=>0,'2'=>0);
	$tr_2=array(0=>array(), 100=>array(), 200=>array());
	$tr_2[0]=array('-1'=>array(),'0'=>array(),'1'=>array(),'2'=>array());
	$tr_2[100]=array('-1'=>array(),'0'=>array(),'1'=>array(),'2'=>array());
	$tr_2[200]=array('-1'=>array(),'0'=>array(),'1'=>array(),'2'=>array());
	$tr_3=array();
	$tr_3[0]=array('-1'=>0,'0'=>0,'1'=>0,'2'=>0);
	$tr_3[100]=array('-1'=>0,'0'=>0,'1'=>0,'2'=>0);
	$tr_3[200]=array('-1'=>0,'0'=>0,'1'=>0,'2'=>0);
	if(enabled('wn')) {
		foreach($wn_books as $k3=>$v3) {
			if($v3->novelType==0) $wn_['novel']++;
			else if($v3->novelType==100) $wn_['comic']++;
			else $wn_['others']++;
			if($v3->novelType==0) {
				$res=$wn->get_info_cached($v3->bookId, 3);
				$tr=$res->data->bookInfo->translateMode;
			}
			elseif($v3->novelType==100 || $v3->novelType==200) {
				$res=NULL;
				$tr=0;
			}
			//var_dump($v3,$tr,$res);die();
			if($tr==0 || $tr==1 || $tr==2) {
				$wn_['novel translated']++;
			} else if($tr==-1) {
				$wn_['novel original']++;
			} else {
				var_dump($v3, $res);die();
				$wn_['novel others']++;
			}//*/
			if(!array_key_exists($tr, $tr_)) $tr_[$tr]=1;
			else $tr_[$tr]++;
			if(!array_key_exists($v3->novelType, $tr_3)) $tr_3[$v3->novelType]=array();
			if(!array_key_exists($tr, $tr_3[$v3->novelType])) $tr_3[$v3->novelType][$tr]=1;
			else $tr_3[$v3->novelType][$tr]++;
			//if(!array_key_exists($tr, $tr_2)) $tr_2[$tr]=array();
			$tr_2[$v3->novelType][$tr][]=$v3;
		}
	}
	//var_dump($tr_2[0][0][0]);
	//var_dump($tr_2[100][0][0]);
	//$info=$wn->get_info_cached('17060542705315401', 2);
	//var_dump($info);
	//die();
	var_dump($wn_);var_dump($tr_);var_dump($tr_3);
	foreach($tr_2 as $k=>$v) {
		foreach($v as $k1=>$v1) {
			$v1=array_map(fn($e) => (exists($e, 'bookName')?get($e,'bookName'):''), $v1);
			sort($v1);
			$tr_2[$k][$k1]=$v1;
		}
		//var_dump($k, $v[0]);
	}
	/*var_dump($tr_2);
	die();//*/

	$cor=array();
	$cor_rr=array();
	$cor_wn=array();
	$cor_wln=array();
	$nocor=array();
	$nocor_rr=array();
	$nocor_wn=array();
	$nocor_wln=array();
	$names=array();

	//var_dump($wln_books['data'][1]);die();

	$skip_wln_wn=array();
	$skip_wln_rr=array();
	$skip_wn_rr=array();

	$timer1=microtime(true);
	$timer2=$timer3=$timer4=NULL;
	$counter1=$counter2=$counter3=$counter4=$counter5=$counter6=0;
	// 1 WLN
	foreach($wln_order as $k1=>$v1) {
		foreach($v1 as $k2) {
			$v2=$wln_books[$k2];
			//var_dump($v2);die();
			$wln_id=$v2[0]->id;
			$n1=trim($v2[0]->name);
			$n2=!empty($v2[3])?trim($v2[3]):'';
			$n1=normalize($n1);
			$n2=normalize($n2);
			$n2b=($n2!=false)?$n2:$n1;
			
			//try {
				$wln_info=$wln->get_info_cached($wln_id);
				//sleep(1);
			/*}
			catch(Exception $e) {
				if( is_object($wln_info) && $wln_info->error==true && $wln_error_count<1 ) {
					$res=$wln->login( $accounts['WLNUpdates']['user'], $accounts['WLNUpdates']['pass'] );
					if($res===false) die('you need to log in.');
					//$res=json_decode($res);
					if(is_object($res) && $res->error==true) die($res->message);
					$wln_error_count++;
				}
				else throw $e;
			}//*/
			if(strlen($n2b)==0) {
				var_dump($wln_id,$n1,$n2,$n2b);
				die('empty name for '.$wln_id.'.');
			}
			//var_dump($rr_info);
			$n3=NULL;
			try {
				$n3=get(get($wln_info, 'data'), 'alternatenames');
				if(is_string($n3)) $n3=trim($n3);
				else if(is_array($n3)) $n3=array_filter(array_map('trim', $n3));
				//var_dump($n3);die();
				if(is_array($n3)) $n3=array_map('normalize', $n3);
			}
			catch(Exception $e) {
				unlink($wln::FOLDER.'get-series-id'.$wln_id.'.json');
				//var_dump($e);die();
				xdebug_print_function_stack('WLNUpdates : '.$wln_id."<br/>\r\n".$wln_info->message);
				die();
			}
			if($n2!=false && !in_array($n2, $n3)) {
				$wln_info=$wln->info($wln_id);
				$n3=get(get($wln_info, 'data'), 'alternatenames');
				if(is_string($n3)) $n3=trim($n3);
				else if(is_array($n3)) $n3=array_filter(array_map('trim', $n3));
				//var_dump($n3);die();
				if(is_array($n3)) $n3=array_map('normalize', $n3);
			}
			$n3=array_map('name_simplify', $n3);
			$n3=array_merge($n3, array_map('normalize', $n3));
			$n3=array_merge($n3, array_map('normalize2', $n3));
			$n3=array_merge($n3, array_map(fn($e) => json_decode('"'.addslashes($e).'"'), $n3));
			$n3=array_values(array_unique($n3));
			//var_dump($wln_id, $n1, $n2, $n3);//die();
			//if($wln_id==110069) { var_dump($n3); }
			
			// 1 WLN 2 WN
			$wn_id=NULL;
			$k3=$v3=NULL;
			$n=NULL;
			$timer3=microtime(true);
			if(enabled('wn')) {
				foreach($wn_books as $k3=>$v3) {
					//var_dump($v3);//die();
					if($v3->novelType==100 || $v3->novelType==200) continue;
					if(IGNORE_DUPLICATES && isset($skip_wln_wn[$k3]) && $skip_wln_wn[$k3]) continue;
					//$res=$wn->get_info_cached($v3->bookId,3);
					//var_dump($res);die();
					//$n=name_simplify($v3->bookName);
					if(!exists($v3, 'bookName')) continue;
					$n=trim($v3->bookName);
					if(empty($n)) continue;
					$n=name_simplify($n);
					$n=normalize($n);
					foreach($n3 as $n_) {
						//if($n===$n_) {
						if(empty($n_)) continue;
						$n_=trim($n_);
						$n_=name_simplify($n_);
						$n_=normalize($n_);
						if(
							strtolower($n)===strtolower($n_)
							|| strtolower(normalize($n))===strtolower(normalize($n_))
							//|| strtolower(normalize2($n))===strtolower(normalize2($n_))
							|| strtolower(normalize(normalize($n)))===strtolower(normalize(normalize2($n_)))
							// TODO : fix this uglyness
							|| @strtolower(json_decode('"'.addslashes($n).'"'))===@strtolower(json_decode('"'.addslashes($n_).'"'))
							|| @strtolower(json_decode('"'.addslashes(normalize($n)).'"'))===@strtolower(json_decode('"'.addslashes(normalize($n_)).'"'))
						) {
							//if($wln_id==110069) { var_dump($n); }
							$wn_id=$v3->bookId;
							$skip_wln_wn[$k3]=true;
							break(2);
						}
					}
				}
			}
			$timer4=microtime(true);
			$counter2+=($timer4-$timer3);
			//if($wln_id==110069) die();
			//var_dump($wn_id);//die();
			
			// 1 WLN 2 RR
			$rr_id=NULL;
			$k4=$v4=NULL;
			$n=NULL;
			$timer3=microtime(true);
			foreach($rr_books as $k4=>$v4) {
				//var_dump($k4, $v4);die();
				if(IGNORE_DUPLICATES && isset($skip_wln_rr[$k4]) && $skip_wln_rr[$k4]) continue;
				//$n=name_simplify($v4['title']);
				$n=normalize(trim($v4->title));
				if(empty($n)) continue;
				$n=name_simplify($n);
				//if($wln_id==128991||$rr_id==37231) { var_dump($v4['title'], $n, $n3); }
				foreach($n3 as $n_) {
					//if($n===$n_) {
					if(empty($n_)) continue;
					if(strtolower($n)===strtolower($n_) || strtolower(normalize2($n))===strtolower($n_)) {
						$rr_id=$k4;
						$skip_wln_rr[$k4]=true;
						break(2);
					}
				}
			}
			$timer4=microtime(true);
			$counter3+=($timer4-$timer3);
			//die();
			//var_dump($rr_id);//die();
			//var_dump($wln_id, $wn_id, $rr_id);die();
			
			$ar=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id, 'name'=>$n2b, 'list'=>$k1);
			$id=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id);
			
			if(!empty($n1) && !array_key_exists($n1, $names)) $names[$n1]=$id;
			if(!empty($n1) && !array_key_exists(strtolower($n1), $names)) $names[strtolower($n1)]=$id;
			if(!empty($n1) && !array_key_exists(strtolower(normalize2($n1)), $names)) $names[strtolower(normalize2($n1))]=$id;
			if(!empty($n2) && !array_key_exists($n2, $names)) $names[$n2]=$id;
			if(!empty($n2) && !array_key_exists(strtolower($n2), $names)) $names[strtolower($n2)]=$id;
			if(!empty($n2) && !array_key_exists(strtolower(normalize2($n2)), $names)) $names[strtolower(normalize2($n2))]=$id;
			foreach($n3 as $n_) {
				if(empty($n_)) continue;
				if(!array_key_exists($n_, $names)) $names[$n_]=$id;
				if(!array_key_exists(json_decode('"'.addslashes($n_).'"'), $names)) $names[json_decode('"'.addslashes($n_).'"')]=$id;
				if(!array_key_exists(normalize2($n_), $names)) $names[normalize2($n_)]=$id;
				if(!array_key_exists(strtolower($n_), $names)) $names[strtolower($n_)]=$id;
				if(!array_key_exists(strtolower(normalize2($n_)), $names)) $names[strtolower(normalize2($n_))]=$id;
			}
			
			//if( $wln_id==109439 || $wn_id==14107231705361905 ) var_dump($n1, $n2, $n3, $ar);
			
			if(is_null($wn_id)&&is_null($rr_id)) continue;
			
			//var_dump($ar);die();
			$cor[]=$ar;
			if(!is_null($wln_id)) {
				$cor_wln[$wln_id]=array('wn'=>$wn_id, 'rr'=>$rr_id, 'name'=>$n2b, 'list'=>$k1);
			}
			if(!is_null($wn_id)) {
				$cor_wn[$wn_id]=array('wln'=>$wln_id, 'rr'=>$rr_id, 'name'=>$n2b, 'list'=>$k1);
			}
			if(!is_null($rr_id)) {
				$cor_rr[$rr_id]=array('wln'=>$wln_id, 'wn'=>$wn_id, 'name'=>$n2b, 'list'=>$k1);
			}
			
			if(ob_get_level()>0) { ob_end_flush(); ob_flush(); }
			flush();
		}
	}
	$timer2=microtime(true);
	$counter1+=($timer2-$timer1);
	var_dump(array('cor'=>count($cor),'wln'=>count($cor_wln),'wn'=>count($cor_wn),'rr'=>count($cor_rr),'names'=>count($names)));

	// 1 WN
	$timer3=microtime(true);
	if(enabled('wn')) {
		foreach($wn_books as $k3=>$v3) {
			if($v3->novelType==100 || $v3->novelType==200) continue;
			$wn_id=$v3->bookId;
			if(array_key_exists($wn_id, $cor_wn)) continue;
			if(!exists($v3, 'bookName')) continue;
			$n=name_simplify(trim($v3->bookName));
			if(empty($n)) continue;
			$n=normalize($n);
			$res=$wn->get_info_cached($v3->bookId, 3);
			
			assert(strlen($n)>0) or die('empty name for '.$wn_id.'.');
			$n2=NULL;
			// 1 WN 2 RR
			$rr_id=NULL;
			$k4=$v4=NULL;
			$n2=NULL;
			foreach($rr_books as $k4=>$v4) {
				if(IGNORE_DUPLICATES && isset($skip_wn_rr[$k4]) && $skip_wn_rr[$k4]) continue;
				$n2=name_simplify(trim($v4->title));
				if(empty($n2)) continue;
				$n2=normalize($n2);
				//if($n===$n2) {
				if(strtolower($n)===strtolower($n2)||strtolower(normalize2($n))===strtolower(normalize2($n2))) {
					$rr_id=$k4;
					$skip_wn_rr[$k4]=true;
					break;
				}
			}
			
			$wln_id=NULL;//none
			
			$ar=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id, 'name'=>$n, 'list'=>$k1);
			$id=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id);
			
			if(!array_key_exists($n, $names)) $names[$n]=$id;
			if(!array_key_exists(normalize2($n), $names)) $names[normalize2($n)]=$id;
			if(!array_key_exists(strtolower($n), $names)) $names[strtolower($n)]=$id;
			if(!array_key_exists(strtolower(normalize2($n)), $names)) $names[strtolower(normalize2($n))]=$id;
			
			//if( $wln_id==109439 || $wn_id==14107231705361905 ) var_dump($n, $n2, $ar);
			
			if(is_null($rr_id)) continue;
			
			if(!array_key_exists($wn_id, $cor_wn)) {
				$ar=array('wln'=>null, 'wn'=>$wn_id, 'rr'=>$rr_id, 'name'=>$v3->bookName);
				$cor[]=$ar;
				$cor_wn[$wn_id]=$ar;
				$cor_rr[$rr_id]=$ar;
			}
		}
	}
	$timer4=microtime(true);
	$counter4+=($timer4-$timer3);
	var_dump(array('cor'=>count($cor),'wln'=>count($cor_wln),'wn'=>count($cor_wn),'rr'=>count($cor_rr),'names'=>count($names)));

	// 1 RR
	$timer3=microtime(true);
	foreach($rr_books as $k4=>$v4) {
		$rr_id=$k4;
		if(array_key_exists($rr_id, $cor_rr)) continue;
		//if(isset($cor_rr[$skip_wln_rr[$k4]])) continue;
		$n2=name_simplify(trim($v4->title));
		if(empty($n2)) continue;
		$n2=normalize($n2);
		
		assert(strlen($n2)>0) or die('empty name for '.$rr_id.'.');
		$wln_id=NULL;//none
		$wn_id=NULL;//none
		
		$ar=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id, 'name'=>$n2b, 'list'=>$k1);
		$id=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id);
		
		if(!array_key_exists($n2, $names)) $names[$n2]=$id;
		if(!array_key_exists(normalize2($n2), $names)) $names[normalize2($n2)]=$id;
		if(!array_key_exists(strtolower($n2), $names)) $names[strtolower($n2)]=$id;
		if(!array_key_exists(strtolower(normalize2($n2)), $names)) $names[strtolower(normalize2($n2))]=$id;
	}
	$timer4=microtime(true);
	$counter5+=($timer4-$timer3);
	var_dump(array('cor'=>count($cor),'wln'=>count($cor_wln),'wn'=>count($cor_wn),'rr'=>count($cor_rr),'names'=>count($names)));

function cmp_cor($e1, $e2) {
	if(!is_null($e1['wln'])&&is_null($e2['wln'])) return -1;
	if(is_null($e1['wln'])&&!is_null($e2['wln'])) return 1;
	if(!is_null($e1['wln'])&&!is_null($e2['wln'])) {
		if($e1['wln']<=$e2['wln']) return -2;
		if($e1['wln']> $e2['wln']) return 2;
	}
	if(!is_null($e1['wn'])&&is_null($e2['wn'])) return -3;
	if(is_null($e1['wn'])&&!is_null($e2['wn'])) return 3;
	if(!is_null($e1['wn'])&&!is_null($e2['wn'])) {
		if($e1['wn']<=$e2['wn']) return -4;
		if($e1['wn']> $e2['wn']) return 4;
	}
	if(!is_null($e1['rr'])&&is_null($e2['rr'])) return -5;
	if(is_null($e1['rr'])&&!is_null($e2['rr'])) return 5;
	if(!is_null($e1['rr'])&&!is_null($e2['rr'])) {
		if($e1['rr']<=$e2['rr']) return -6;
		if($e1['rr']> $e2['rr']) return 6;
	}
	return 0;
}
	$timer3=microtime(true);
	usort($cor, 'cmp_cor');
	ksort($cor_wln, SORT_FLAG_CASE | SORT_NATURAL);
	ksort($cor_wn, SORT_FLAG_CASE | SORT_NATURAL);
	ksort($cor_rr, SORT_FLAG_CASE | SORT_NATURAL);
	ksort($names, SORT_FLAG_CASE | SORT_NATURAL);
	$timer4=microtime(true);
	$counter6+=($timer4-$timer3);


	if(enabled('wn')) $per_wn=number_format( ($wln_wn/count($wn_books)*100), 2);
	else $per_wn=0;
	$per_rr=number_format( ($wln_rr/count($rr_books)*100), 2);
	if(enabled('wn')) $per_wn_cor0=number_format( (count($cor_wn)/$wln_wn*100), 2);
	else $per_wn_cor0=0;
	if(enabled('wn')) $per_wn_cor2=number_format( (count($cor_wn)/count($wn_books)*100), 2);
	else $per_wn_cor2=0;
	$per_rr_cor0=number_format( (count($cor_rr)/$wln_rr*100), 2);
	$per_rr_cor2=number_format( (count($cor_rr)/count($rr_books)*100), 2);
	var_dump(array(
	'wln: wln_wn/wn'=>$per_wn, 'wln: cor_wn/wln_wn'=>$per_wn_cor0, 'wln: cor_wn/wn'=>$per_wn_cor2,
	'wln: wln_rr/rr'=>$per_rr, 'wln: cor_rr/wln_rr'=>$per_rr_cor0, 'wln: cor_rr/rr'=>$per_rr_cor2,
	));

	var_dump($counter1, $counter2, $counter3, $counter4, $counter5, $counter6);

	file_put_contents('correspondances.json',json_encode($cor, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
	file_put_contents('correspondances_wln.json',json_encode($cor_wln, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
	file_put_contents('correspondances_wn.json',json_encode($cor_wn, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
	file_put_contents('correspondances_rr.json',json_encode($cor_rr, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
	file_put_contents('names.json',json_encode($names, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
}
else {
	$cor=json_decode(file_get_contents('correspondances.json'), true, 512, JSON_THROW_ON_ERROR);
	$cor_wln=json_decode(file_get_contents('correspondances_wln.json'), true, 512, JSON_THROW_ON_ERROR);
	$cor_wn=json_decode(file_get_contents('correspondances_wn.json'), true, 512, JSON_THROW_ON_ERROR);
	$cor_rr=json_decode(file_get_contents('correspondances_rr.json'), true, 512, JSON_THROW_ON_ERROR);
	$names=json_decode(file_get_contents('names.json'), true, 512, JSON_THROW_ON_ERROR);
}

if(direct()) include('footer.php');
