<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('wlnupdates.php');
require_once('webnovel.php');
require_once('royalroad.php');

if(!defined('MOONREADER_DID')) define('MOONREADER_DID', '1454083831785');
if(!defined('MOONREADER_DID2')) define('MOONREADER_DID2', '9999999999999');

if(direct()) include('header.php');

chdir(DROPBOX);
$ar=glob('*.po');
natcasesort($ar);

chdir(CWD);

$updatedCount=array(
	'wln'=>0,
	'wn'=>0,
	'rr'=>0,
);

$fns=array();//from reader
$fns_=array();//from here
foreach($ar as $fn)
{
	preg_match('#^(.*)_(-?\d+)-(\d+)(_FIN)?\.epub\.po$#i', $fn, $matches);
	if(!empty($matches)) {
		$fn2=$matches[1];
		$min=(int)$matches[2];
		$max=(int)$matches[3];
	}
	else {
		preg_match('#^(-?\d+)-(\d+)_(.*)\.epub\.po$#i', $fn, $matches);
		if(!empty($matches))
		{
			$min=(int)$matches[1];
			$max=(int)$matches[2];
			$fn2=$matches[3];
		}
	}
	if(empty($matches))
	{
		//var_dump($fn);
		echo '<div class="block b b-red">',$fn,'</div>',"\n";
		continue;
	}
	$fn2=name_simplify($fn2, 1);
	$fn3=strtolower($fn2);
	$data=file_get_contents(DROPBOX.$fn);
	$content=array();
	$content[]=strtok($data, '*@#:%');
	while(($content[]=strtok('*@#:%'))!==FALSE);
	unset($data);
	$id=array_search(false, $content, true);
	$content=array_slice($content, 0, $id);
	//if( array_key_exists($fn3, $fns) ) var_dump($fn3, $max, $fns[$fn3]['max'], $min+($min<0?1:0), $content[1], ($content[3]>0?1:0), $fns[$fn3]['min']+($fns[$fn3]['min']<0?1:0), $fns[$fn3][1], ($fns[$fn3][3]>0?1:0) );
	if($fn==='Unbound_1-7.epub.po') continue;
/*
1)a) if content[0] MOONREADER_DID
	fns
1)b) if content[0] MOONREADER_DID2
	fns_
2) if max > last (both MOONREADER_DID and MOONREADER_DID2) && pos >=
	replace & delete old
3) if last (MOONREADER_DID and MOONREADER_DID2) > max && pos <=
	delete new
4) if MOONREADER_DID2 < MOONREADER_DID
	delete
*/
	//if($content[1]=='0' && $content[3]=='0') continue; // drops 42+ novels at chp 1
	$chp=$min + ($min<0?1:0) + $content[1] + ($content[3]>0?1:0);
	//conds
	if($content[0]===MOONREADER_DID) {
		//1a
		if( !array_key_exists($fn3, $fns) ) {
			$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2, 'chp'=>$chp);
			$ar2=array_merge($ar2, $content);
			$fns[$fn3]=$ar2;
		}
		else {
			$lastchp=$fns[$fn3]['min'] + ($fns[$fn3]['min']<0?1:0) + $fns[$fn3][1] + ($fns[$fn3][3]>0?1:0);
			$max1=$fns[$fn3]['max'];
			//2a
			if($max>$max1 && $chp>=$lastchp) {
				unlink(DROPBOX.$fns[$fn3]['fn']);//die();
				echo '<div class="block b b-blue">',$fns[$fn3]['fn'],'</div>',"\n";
				$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2, 'chp'=>$chp);
				$ar2=array_merge($ar2, $content);
				$fns[$fn3]=$ar2;
			}
			//3a
			else if($max1>$max && $lastchp>=$chp) {
				unlink(DROPBOX.$fn);
				echo '<div class="block b b-blue">',$fn,'</div>',"\n";
			}
		}
		if(array_key_exists($fn3, $fns_)) {
			if($fns_[$fn3]['chp']<=$fns[$fn3]['chp']) {
				unlink(DROPBOX.$fns_[$fn3]['fn']);
				echo '<div class="block b b-blue">',$fns_[$fn3]['fn'],'</div>',"\n";
				unset($fns_[$fn3]);
			}
			else {
				unlink(DROPBOX.$fn);
				echo '<div class="block b b-blue">',$fn,'</div>',"\n";
				unset($fns[$fn3]);
			}
		}
	}
	else if($content[0]===MOONREADER_DID2) {
		//1b
		if( !array_key_exists($fn3, $fns_) ) {
			$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2, 'chp'=>$chp);
			$ar2=array_merge($ar2, $content);
			$fns_[$fn3]=$ar2;
		}
		else {
			$lastchp_=$fns_[$fn3]['min'] + ($fns_[$fn3]['min']<0?1:0) + $fns_[$fn3][1] + ($fns_[$fn3][3]>0?1:0);
			$max2=$fns_[$fn3]['max'];
			//2b
			if($max>$max2 && $chp>=$lastchp_) {
				unlink(DROPBOX.$fns_[$fn3]['fn']);//die();
				echo '<div class="block b b-green">',$fns_[$fn3]['fn'],'</div>',"\n";
				$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2, 'chp'=>$chp);
				$ar2=array_merge($ar2, $content);
				$fns_[$fn3]=$ar2;
			}
			//3b
			else if($max2>$max && $lastchp_>$chp) {
				unlink(DROPBOX.$fn);
				echo '<div class="block b b-green">',$fn,'</div>',"\n";
			}
		}
	}
	else {
		var_dump($fn, $fn2, $content);
		die();
	}
}
//var_dump(count($fns));
$wln=$wn=$rr=NULL;
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
$wln_list=$wln_order=$wln_books=[];
if(enabled('wln')) {
	$wln_list=$wln->get_list();
	$wln_order=$wln->get_order();
	$wln_books=$wln->get_watches();
}
$wn_list=$wn_order=$wn_books=[];
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
$rr_list=$rr_oder=$rr_books=[];
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

echo '<h2>Counts</h2>',"\n";
print_table(array(array(
	'files'=>count(array_merge($fns_,$fns)),
	'WLNUpdates'=>count($wln_books),
	'WebNovel'=>count($wn_books),
	'RoyalRoad'=>count($rr_books)
)));
echo '<br/>'."\r\n";
if(ob_get_level()>0) { ob_end_flush(); ob_flush(); }
flush();

if(direct()) include('footer.php');
