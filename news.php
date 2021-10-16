<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('wlnupdates.php');
require_once('webnovel.php');
require_once('royalroad.php');

$loggued=false;

if(!defined('DROPBOX_DONE')||!DROPBOX_DONE) include('header.php');

include_once('position.php');
include_once('correspondances.php');

define('PERLINE', false);

$wln=new WLNUpdates();
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

ob_start();

$head=array('title',
 'WLNUpdate cur',
 'WebNovel cur', 'WebNovel last',
 'RoyalRoad cur', 'RoyalRoad last',
 'start', 'pos', 'last',
 'msg');
//1 WLN 2 WN 3 RR
foreach($wln_order as $id=>$list) {
	//if( !( strpos(strtolower($id), 'on-hold')!==false || strpos(strtolower($id), 'plan to read')!==false || strpos(strtolower($id), 'completed')!==false || strpos(strtolower($id), 'royalroad')!==false ) ) {
		echo '<h1>'.$id.'</h1>',"\n";
	//}
	$lines=0;
	foreach($list as $entry) {
		$wln1=$wln_books[$entry];
		$row=array();
		$row['title']=$wln1[0]->name;
		if(!is_null($wln1[3])) $row['title']=$wln1[3];
		$row['title']=rawurldecode($row['title']);
		$row['title']=html_entity_decode($row['title']);
		$row['WLNUpdate cur']=$wln1[1]->chp;
		if(array_key_exists($entry, $cor_wln)) {
			$wln2=$cor_wln[$entry];
			if(!is_null($wln2['wn'])) {
				$wn1=$wn_books[$wln2['wn']];
				$row['WebNovel cur']=$wn1->readToChapterNum;
				$row['WebNovel last']=$wn1->totalChapterNum;//readToChapterIndex or totalChapterNum or newChapterIndex
			}
			if(!is_null($wln2['rr'])) {
				$rr1=$rr_books[$wln2['rr']];
				$rr2=$rr->get_chapter_list_cached($wln2['rr']);
				if(exists($rr1, 'last-read-title')) {
					$found=array_filter($rr2, fn($e) => (get($e, 'title')==get($rr1, 'last-read-title')) );
					if(count($found)==0) $rr2=$rr->get_chapter_list($wln2['rr']);
				}
				if(exists($rr1, 'last-upd-title')) {
					$found=array_filter($rr2, fn($e) => (get($e, 'title')==get($rr1, 'last-upd-title')) );
					if(count($found)==0) $rr2=$rr->get_chapter_list($wln2['rr']);
				}
				if(count($rr2)>0) {
					$rr2a=count($rr2)-1;
					$rr2b=$rr2[$rr2a];
					$check=(strpos(get($rr2b, 'title'), $rr2a)!==false) || (strpos(get($rr2b, 'href'), $rr2a)!==false);
					$rr2d=array_filter($rr2, fn($e) => exists($e, 'pos-title')&&strlen(get($e, 'pos-title'))>0);
					if(count($rr2d)==1) $rr2c=array_keys($rr2d)[0];
					else $rr2c=NULL;
					$row['RoyalRoad cur']=(is_numeric($rr2c)?$rr2c+1:$rr2c);
					$row['RoyalRoad last']=(is_numeric($rr2a)?$rr2a+1:$rr2a);
				}
			}
		}
		$name=strtolower(normalize(name_simplify($row['title'], 1)));
		if(array_key_exists($name, $pos)) {
			$pos1=$pos[$name];
			$row['start']=$pos1['min'];
			$row['pos']=$pos1['pos'];
			$row['last']=$pos1['max'];
		}
		if(count($row)>1) {
			if($lines==0) {
				echo '<table border="1">'."\r\n";
				print_thead_v($head);
			}
			++$lines;
			print_tbody($row, $head);
		}
		if(PERLINE) {
			if(ob_get_level()>0) { ob_flush(); }
			//flush();
		}
	}
	if($lines>0) {
		echo '</table>'."\r\n";
	}
	//if(ob_get_level()>0) { ob_flush(); }
	flush();
}
//if(ob_get_level()>0) { ob_flush(); }
flush();
//1 WN 2 RR
$head=array('title',
 //'WLNUpdate cur',
 'WebNovel cur', 'WebNovel last',
 'RoyalRoad cur', 'RoyalRoad last',
 'start', 'pos', 'last',
 'msg');
$lines=0;
usort($wn_books, fn($e1, $e2) => strnatcasecmp($e1->bookName, $e2->bookName));
echo '<h1>WebNovel</h1>',"\n";
foreach($wn_books as $entry) {
	if($entry->novelType==100) continue;
	$row=array();
	$row['title']=$entry->bookName;
	if(!property_exists($entry, 'readToChapterNum')) $row['WebNovel cur']=$entry->readToChapterIndex;
	else $row['WebNovel cur']=$entry->readToChapterNum;
	if(!property_exists($entry, 'totalChapterNum')) $row['WebNovel last']=$entry->newChapterIndex;
	else $row['WebNovel last']=$entry->totalChapterNum;//readToChapterIndex or totalChapterNum or newChapterIndex
	if(array_key_exists($entry->bookId, $cor_wn)) {
		$wn1=$cor_wn[$entry->bookId];
		if(!is_null($wn1['wln'])) continue; // already in previous big loop
		if(!is_null($wn1['rr'])) {
			$wn2=$rr_books[$wn1['rr']];
			$rr2=$rr->get_chapter_list_cached($wn1['rr']);
			if(count($rr2)>0) {
				$rr2a=count($rr2)-1;
				$rr2b=$rr2[$rr2a];
				$check=(strpos(get($rr2b, 'title'), $rr2a)!==false) || (strpos(get($rr2b, 'href'), $rr2a)!==false);
				$rr2d=array_filter($rr2, fn($e) => exists($e, 'pos-title')&&strlen(get($e, 'pos-title'))>0);
				if(count($rr2d)==1) $rr2c=array_keys($rr2d)[0];
				else $rr2c=NULL;
				$row['RoyalRoad cur']=$rr2c;
				$row['RoyalRoad last']=$rr2a;
			}
		}
	}
	$name=strtolower(normalize(name_simplify($row['title'], 1)));
	if(array_key_exists($name, $pos)) {
		$pos1=$pos[$name];
		$row['start']=$pos1['min'];
		$row['pos']=$pos1['pos'];
		$row['last']=$pos1['max'];
	}
	if(count($row)>1) {
		if($lines==0) {
			echo '<table border="1">'."\r\n";
			print_thead_v($head);
		}
		++$lines;
		print_tbody($row, $head);
	}
	if(PERLINE) {
		//if(ob_get_level()>0) { ob_flush(); }
		flush();
	}
}
if($lines>0) {
	echo '</table>'."\r\n";
}
//if(ob_get_level()>0) { ob_flush(); }
flush();
//1 RR
$head=array('title',
 //'WLNUpdate cur',
 //'WebNovel cur', 'WebNovel last',
 'RoyalRoad cur', 'RoyalRoad last',
 'start', 'pos', 'last',
 'msg');
$lines=0;
uasort($rr_books, fn($e1, $e2) => strnatcasecmp($e1->title, $e2->title));
echo '<h1>RoyalRoad</h1>',"\n";
foreach($rr_books as $rr_id=>$entry) {
	$row=array();
	$row['title']=$entry->title;
	if(array_key_exists($rr_id, $cor_rr)) {
		$rr1=$cor_rr[$rr_id];
		if(!is_null($rr1['wln'])) continue; // already in previous big loop
		if(!is_null($rr1['wn'])) continue; // already in previous big loop
	}
	$rr2=$rr->get_chapter_list_cached($rr_id);
	if(count($rr2)>0) {
		$rr2a=count($rr2)-1;
		$rr2b=$rr2[$rr2a];
		$check=(strpos(get($rr2b, 'title'), $rr2a)!==false) || (strpos(get($rr2b, 'href'), $rr2a)!==false);
		$rr2d=array_filter($rr2, fn($e) => exists($e, 'pos-title')&&strlen(get($e, 'pos-title'))>0);
		if(count($rr2d)==1) $rr2c=array_keys($rr2d)[0];
		else $rr2c=NULL;
		$row['RoyalRoad cur']=$rr2c;
		$row['RoyalRoad last']=$rr2a;
	}
	else {
		if($rr_id!=7015) { var_dump($rr_id, $rr2);die(); }
	}
	$name=strtolower(normalize(name_simplify($row['title'], 1)));
	if(array_key_exists($name, $pos)) {
		$pos1=$pos[$name];
		$row['start']=$pos1['min'];
		$row['pos']=$pos1['pos'];
		$row['last']=$pos1['max'];
	}
	if(count($row)>1) {
		if($lines==0) {
			echo '<table border="1">'."\r\n";
			print_thead_v($head);
		}
		++$lines;
		print_tbody($row, $head);
	}
	if(PERLINE) {
		//if(ob_get_level()>0) { ob_flush(); }
		flush();
	}
}
if($lines>0) {
	echo '</table>'."\r\n";
}
//if(ob_get_level()>0) { ob_flush(); }
flush();
//1 pos
$head=array('title',
 //'WLNUpdate cur',
 //'WebNovel cur', 'WebNovel last',
 //'RoyalRoad cur', 'RoyalRoad last',
 'start', 'pos', 'last',
 'msg');
$lines=0;
echo '<h1>__Positions</h1>',"\n";
foreach($pos as $item) {
	$row=array();
	$row['title']=$item['fn3'];
	$name=strtolower(normalize(name_simplify($row['title'])));
	if(array_key_exists($name, $names)) {
		$names1=$names[$name];
		if(!is_null($names1['wln'])) continue;
		if(!is_null($names1['wn'])) continue;
		if(!is_null($names1['rr'])) continue;
	}
	$row['start']=$item['min'];
	$row['pos']=$item['pos'];
	$row['last']=$item['max'];
	if(count($row)>1) {
		if($lines==0) {
			echo '<table border="1">'."\r\n";
			print_thead_v($head);
		}
		++$lines;
		print_tbody($row, $head);
	}
	if(PERLINE) {
		//if(ob_get_level()>0) { ob_flush(); }
		flush();
	}
}
if($lines>0) {
	echo '</table>'."\r\n";
}
//if(ob_get_level()>0) { ob_flush(); }
flush();

//$data=ob_get_clean(); // return-only (no print)
$data=ob_get_flush(); // return and print
file_put_contents('news.htm', $data);
unset($data);

if(!defined('DROPBOX_DONE')||!DROPBOX_DONE) include('footer.php');
?>