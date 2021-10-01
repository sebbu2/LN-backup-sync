<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('CJSON.php');

require_once('wlnupdates.php');
require_once('webnovel.php');
require_once('royalroad.php');

if(!defined('MOONREADER_DID')) define('MOONREADER_DID', '1454083831785');
if(!defined('MOONREADER_DID2')) define('MOONREADER_DID2', '9999999999999');

$direct= ( realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__) );
//define('DROPBOX_DONE', true);
if($direct) include('header.php');

include('position.php');

$rr=new RoyalRoad;
$wn=new WebNovel;
$wln=new WLNUpdates;

$wln=new WLNUpdates();
$wln_error_count=0;
$wn=new WebNovel;
$res=$wn->checkLogin();
if($res->code!=0) {
	var_dump($res->code, $res->msg);
	$res=$wn->login( $accounts['WebNovel']['user'], $accounts['WebNovel']['pass']);
	var_dump($res);
	die();
}
$rr=new RoyalRoad;
$res=$rr->checkLogin();
if($res!==1) {
	var_dump($res);
	$res=$rr->login( $accounts['RoyalRoad']['user'], $accounts['RoyalRoad']['pass'] );
	var_dump($res);
	die();
}

$watches=json_decode(str_replace("\t",'',file_get_contents('wlnupdates/watches.json')),true,512,JSON_THROW_ON_ERROR);// important : true as 2nd parameter
$books=json_decode(str_replace("\t",'',file_get_contents('webnovel/_books.json')),true,512,JSON_THROW_ON_ERROR);
$follows=json_decode(str_replace("\t",'',file_get_contents('royalroad/_books.json')),true,512,JSON_THROW_ON_ERROR);

function sum_count($carry, $item) { $carry+=count($item); return $carry; }

$wln_sum=0;
$wln_wn=0;
$wln_rr=0;
$wln_others=0;
foreach( $watches['data'][0] as $k=>$v) {
	//var_dump($k, count($v));
	if(substr($k.' ', 0, 7)==='QIDIAN ') $wln_wn+=count($v);
	else if(substr($k.' ', 0, 10)==='RoyalRoad ') $wln_rr+=count($v);
	else $wln_others+=count($v);
	$wln_sum+=count($v);
}
var_dump($watches['data'][1]);//*/
$wlns=array('sum'=>$wln_sum, 'wn'=>$wln_wn, 'rr'=>$wln_rr, 'others'=>$wln_others);
var_dump($wlns);

//var_dump(array('wln'=>array_reduce($watches['data'][0], 'sum_count'), 'wn'=>count($books), 'rr'=>count($follows)));
var_dump(array('wln'=>$wln_sum, 'wn'=>count($books), 'rr'=>count($follows)));

$wn_=array('novel'=>0, 'novel translated'=>0, 'novel original'=>0, 'novel others'=>0, 'comic'=>0, 'others'=>0);
$tr_=array('-1'=>0,'0'=>0,'1'=>0,'2'=>0);
$tr_2=array(0=>array(), 100=>array());
$tr_2[0]=array('-1'=>array(),'0'=>array(),'1'=>array(),'2'=>array());
$tr_2[100]=array('-1'=>array(),'0'=>array(),'1'=>array(),'2'=>array());
$tr_3=array();
$tr_3[0]=array('-1'=>0,'0'=>0,'1'=>0,'2'=>0);
$tr_3[100]=array('-1'=>0,'0'=>0,'1'=>0,'2'=>0);
foreach($books as $k3=>$v3) {
	if($v3['novelType']==0) $wn_['novel']++;
	else if($v3['novelType']==100) $wn_['comic']++;
	else $wn_['others']++;
	$res=$wn->get_info_cached($v3['bookId'], 3);
	$tr=$res[3]->data->bookInfo->translateMode;
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
	if(!array_key_exists($tr, $tr_3[$v3['novelType']])) $tr_3[$v3['novelType']][$tr]=1;
	else $tr_3[$v3['novelType']][$tr]++;
	//if(!array_key_exists($tr, $tr_2)) $tr_2[$tr]=array();
	$tr_2[$v3['novelType']][$tr][]=$v3;
}
//var_dump($tr_2[0][0][0]);
//var_dump($tr_2[100][0][0]);
//$info=$wn->get_info_cached('17060542705315401', 2);
//var_dump($info);
//die();
var_dump($wn_);var_dump($tr_);var_dump($tr_3);
/*foreach($tr_2 as $k=>$v) var_dump($k, $v[0]);
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

//var_dump($watches['data'][1]);die();

// 1 WLN
foreach($watches['data'][0] as $k1=>$v1) {
	foreach($v1 as $k2=>$v2) {
		//var_dump($v2);die();
		$wln_id=$v2[0]['id'];
		$n1=$v2[0]['name'];
		$n2=$v2[3];
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
			else if(is_array($n3)) $n3=array_filter($n3);
			//var_dump($n3);die();
			if(is_array($n3)) $n3=array_map('normalize', $n3);
		}
		catch(Exception $e) {
			unlink($wln::FOLDER.'get-series-id'.$wln_id.'.json');
			//var_dump($e);die();
			xdebug_print_function_stack('WLNUpdates : '.$wln_id."<br/>\r\n".$wln_info->message);
			die();
		}
		$n3=array_map('name_simplify', $n3);
		$n3=array_values(array_unique($n3));
		//var_dump($wln_id, $n1, $n2, $n3);//die();
		
		// 1 WLN 2 WN
		$wn_id=NULL;
		foreach($books as $k3=>$v3) {
			//var_dump($v3);//die();
			if($v3['novelType']==100) continue;
			//$res=$wn->get_info_cached($v3['bookId'],3);
			//var_dump($res);die();
			$n=name_simplify($v3['bookName']);
			foreach($n3 as $n_) {
				if($n===$n_) {
					$wn_id=$v3['bookId'];
					break(2);
				}
			}
		}
		//var_dump($wn_id);//die();
		
		// 1 WLN 2 RR
		$rr_id=NULL;
		foreach($follows as $k4=>$v4) {
			//var_dump($k4, $v4);die();
			$n=name_simplify($v4['title']);
			foreach($n3 as $n_) {
				if($n===$n_) {
					$rr_id=$k4;
					break(2);
				}
			}
		}
		//var_dump($rr_id);//die();
		//var_dump($wln_id, $wn_id, $rr_id);die();
		
		$ar=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id, 'name'=>$n2b, 'list'=>$k1);
		$id=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id);
		
		if(!array_key_exists($n1, $names) && !empty($n1)) $names[$n1]=$id;
		if(!array_key_exists($n2, $names) && !empty($n2)) $names[$n2]=$id;
		foreach($n3 as $n_) if(!array_key_exists($n_, $names)) $names[$n_]=$id;
		
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
var_dump(array('cor'=>count($cor),'wln'=>count($cor_wln),'wn'=>count($cor_wn),'rr'=>count($cor_rr),'names'=>count($names)));

// 1 WN
foreach($books as $k3=>$v3) {
	if($v3['novelType']==100) continue;
	$wn_id=$v3['bookId'];
	if(array_key_exists($wn_id, $cor_wn)) continue;
	$n=name_simplify($v3['bookName']);
	$n=normalize($n);
	$res=$wn->get_info_cached($v3['bookId'], 3);
	
	assert(strlen($n)>0) or die('empty name for '.$wn_id.'.');
	$n2=NULL;
	// 1 WN 2 RR
	$rr_id=NULL;
	foreach($follows as $k4=>$v4) {
		$n2=name_simplify($v4['title']);
		$n2=normalize($n2);
		if($n===$n2) {
			$rr_id=$k4;
		}
	}
	
	$wln_id=NULL;//none
	
	$ar=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id, 'name'=>$n, 'list'=>$k1);
	$id=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id);
	
	if(!array_key_exists($n, $names)) $names[$n]=$id;
	
	//if( $wln_id==109439 || $wn_id==14107231705361905 ) var_dump($n, $n2, $ar);
	
	if(is_null($rr_id)) continue;
	
	if(!array_key_exists($wn_id, $cor_wn)) {
		$ar=array('wln'=>null, 'wn'=>$wn_id, 'rr'=>$rr_id, 'name'=>$v3['bookName']);
		$cor[]=$ar;
		$cor_wn[$wn_id]=$ar;
		$cor_rr[$rr_id]=$ar;
	}
}
var_dump(array('cor'=>count($cor),'wln'=>count($cor_wln),'wn'=>count($cor_wn),'rr'=>count($cor_rr),'names'=>count($names)));

// 1 RR
foreach($follows as $k4=>$v4) {
	$rr_id=$k4;
	if(array_key_exists($rr_id, $cor_rr)) continue;
	$n2=name_simplify($v4['title']);
	
	assert(strlen($n2)>0) or die('empty name for '.$rr_id.'.');
	$wln_id=NULL;//none
	$wn_id=NULL;//none
	
	$ar=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id, 'name'=>$n2b, 'list'=>$k1);
	$id=array('wln'=>$wln_id, 'wn'=>$wn_id, 'rr'=>$rr_id);
	
	if(!array_key_exists($n2, $names)) $names[$n2]=$id;
}
var_dump(array('cor'=>count($cor),'wln'=>count($cor_wln),'wn'=>count($cor_wn),'rr'=>count($cor_rr),'names'=>count($names)));

ksort($cor_wln, SORT_FLAG_CASE | SORT_NATURAL);
ksort($cor_wn, SORT_FLAG_CASE | SORT_NATURAL);
ksort($cor_rr, SORT_FLAG_CASE | SORT_NATURAL);
ksort($names, SORT_FLAG_CASE | SORT_NATURAL);

$per_wn=number_format( ($wln_wn/count($books)*100), 2);
$per_rr=number_format( ($wln_rr/count($follows)*100), 2);
$per_wn_cor0=number_format( (count($cor_wn)/$wln_wn*100), 2);
$per_wn_cor2=number_format( (count($cor_wn)/count($books)*100), 2);
$per_rr_cor0=number_format( (count($cor_rr)/$wln_rr*100), 2);
$per_rr_cor2=number_format( (count($cor_rr)/count($follows)*100), 2);
var_dump(array(
'wln: wln_wn/wn'=>$per_wn, 'wln: cor_wn/wlr_wn'=>$per_wn_cor0, 'wln: cor_wn/wn'=>$per_wn_cor2,
'wln: wln_rr/rr'=>$per_rr, 'wln: cor_rr/wlr_rr'=>$per_rr_cor0, 'wln: cor_rr/rr'=>$per_rr_cor2,
));

file_put_contents('correspondances.json',json_encode($cor, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
file_put_contents('correspondances_wln.json',json_encode($cor_wln, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
file_put_contents('correspondances_wn.json',json_encode($cor_wn, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
file_put_contents('correspondances_rr.json',json_encode($cor_rr, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
file_put_contents('names.json',json_encode($names, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));

if($direct) include('footer.php');
