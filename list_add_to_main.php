<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('wlnupdates.php');
require_once('webnovel.php');
require_once('royalroad.php');

$loggued=false;
$force=false;

if(direct()) include('header.php');

include_once('position.php');
include_once('correspondances.php');

define('PERLINE', true);
$data='';
$data2='';

$wln=new WLNUpdates;
$wn=new WebNovel;
$rr=new RoyalRoad;

$wln_list=$wln->get_list();
$wln_order=$wln->get_order();
$wln_books=$wln->get_watches();

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

$skip=array('19886807705655205', '19636465406948705', '19230521106872905', '19212887105657605', '7860061006001305');
$skip=array_merge($skip,array('16447293106086205','14286329606946605'));
$skip[]='hell_15666872205580405';//tmfh	
$skip[]='20492878505809705';//twof
$skip[]='11013622205237905';//wisp
$skip[]='20101769906656005';//ambal
$skip[]='11248412105311105';//armipotent (old)
$skip[]='na_15514663106826405';//mml
$skip[]='12610692305122305';//btnh
$skip[]='20343529205397405';//unavailable
$skip[]='12624232606210205';
$skip[]='10961635803380503';

$count=0;
$count2=0;

$fn=$wn::FOLDER.'_history.json';
$res2=NULL;
if(!$force) {
	if(!file_exists($fn) || (time()-filemtime($fn))>604800) $res2=$wn->get_history();
	else $res2=json_decode(file_get_contents($fn), false, 512, JSON_THROW_ON_ERROR);
}
else $res2=$wn->get_history();

var_dump('history');
//var_dump($res2[0][1],$res2[1][1]);//die();

$skips=array();
{
	$res=$res2[0];
	foreach($res as $it) {
		if(in_array($it->bookId, $skip)) continue;
		if(!array_key_exists($it->bookId, $wn_books)) {
			if(in_array((string)$it->bookId, $skips)) continue;
			$res_=$wn->add_watch($it->bookId, $it->novelType);
			if($res_->code!==0 || $res_->data!==null || $res_->msg!=='Success') { var_dump($it, $res_);die(); }
			$count2++;
			var_dump($count2, $it->bookId, $it->bookName);//die();
			$skips[]=(string)$it->bookId;
		}
	}
	$res=$res2[1];
	foreach($res as $it) {
		if(in_array($it->BookId, $skip)) continue;
		if(!array_key_exists($it->BookId, $wn_books)) {
			if(in_array((string)$it->BookId, $skips)) continue;
			$res_=$wn->add_watch($it->BookId, $it->ItemType);
			if($res_->code!==0 || $res_->data!==null || $res_->msg!=='Success') { var_dump($it, $res_);die(); }
			$count2++;
			var_dump($count2, $it->BookId, $it->BookName);//die();
			$skips[]=(string)$it->BookId;
		}
	}
}

if($count2>0) $res2=$wn->get_history();
$count+=$count2;
$count2=0;

//die();

$fn=$wn::FOLDER.'_collection.json';
$res2=NULL;
if(!$force) {
	if(!file_exists($fn) || (time()-filemtime($fn))>604800) $res2=$wn->get_collections();
	else $res2=json_decode(file_get_contents($fn), false, 512, JSON_THROW_ON_ERROR);
}
else $res2=$wn->get_collections();

if(is_object($res2)) $res2=get_object_vars($res2);
if(array_key_exists(0, $res2) && is_object($res2[0])) {
	$res2=$res2[1];
}

var_dump('collections');
//var_dump($res2['pilot read'][0]);//die();

foreach($res2 as $list => $res_) {
	foreach($res_ as $it) {
		if(in_array($it->BookId, $skip)) continue;
		if(!array_key_exists($it->BookId, $wn_books)) {
			if(in_array((string)$it->BookId, $skips)) continue;
			$res_=$wn->add_watch($it->BookId, $it->BookType);
			if($res_->code!==0 || $res_->data!==null || $res_->msg!=='Success') { var_dump($it, $res_);die(); }
			$count2++;
			var_dump($count2, $it->BookId, $it->BookName);//die();
			$skips[]=(string)$it->BookId;
		}
	}
}

if($count2>0) $res2=$wn->get_collections();
$count+=$count2;
$count2=0;

var_dump('end');

var_dump($count);

if($count>0) {
	include('retr.php');
}

if(direct()) include('footer.php');
?>
