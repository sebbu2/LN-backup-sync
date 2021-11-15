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

ob_start();

function evaluate_expr($row, $expr) {
	assert(is_bool($expr) || (is_array($expr) && count($expr)==3));
	static $ops=array('=','==', '!=','<>', '<','<=', '>','>=', 'or', 'and');
	static $opd=array('=','==', '!=','<>', '<','<=', '>','>=');
	static $opl=array('or', 'and');
	static $opl2=array('or2', 'and2');
	$cond2=NULL;
	$op2=array_shift($expr);
	$elem1_=$expr[0];
	$elem2_=$expr[1];
	//var_dump($op2, $elem1, $elem2);die();
	if(is_array($elem1_)) {
		$elem1=evaluate_expr($row, $elem1_);
	}
	else {
		if(is_string($elem1_)) {
			if(!array_key_exists($elem1_, $row)) return NULL;
			$elem1=$row[$elem1_];
		}
		else $elem1=$elem1_;
	}
	if(is_array($elem2_)) {
		$elem2=evaluate_expr($row, $elem2_);
	}
	else {
		if(is_string($elem2_)) {
			if(!array_key_exists($elem2_, $row)) return NULL;
			$elem2=$row[$elem2_];
		}
		else $elem2=$elem2_;
	}
	if($elem1===NULL || $elem2===NULL) {
		if(in_array($op2, $opl)) {
			if($elem1===NULL&&$elem2!==NULL) return $elem2;
			if($elem1!==NULL&&$elem2===NULL) return $elem1;
			return NULL;
		} elseif(in_array($op2, $opl2)) return false;
		//if($expr[1]=='last' && $elem2===NULL) return false; // TEST
		assert(false);
	}
	if($op2=='='||$op2=='==')
		$cond2=($elem1==$elem2);
	if($op2=='!='||$op2=='<>')
		$cond2=($elem1!=$elem2);
	if($op2=='<' )
		$cond2=($elem1< $elem2);
	if($op2=='<=')
		$cond2=($elem1<=$elem2);
	if($op2=='>' )
		$cond2=($elem1> $elem2);
	if($op2=='>=')
		$cond2=($elem1>=$elem2);
	if($op2=='or'||$op2=='or2')
		$cond2=($elem1||$elem2);
	if($op2=='and'||$op2=='and2')
		$cond2=($elem1&&$elem2);
	//var_dump($op2, $elem1_, $elem2_, $cond2);
	assert($cond2!==NULL);
	return $cond2;
}

function skip($row) {
	global $filters;
	assert(count($filters['conditions'])==1);
	$op=array_keys($filters['conditions'])[0];
	$ar=$filters['conditions'][$op];
	$cond=NULL;
	if($op=='or') $cond=false;
	if($op=='and') $cond=true;
	//var_dump($op, $cond);
	assert($cond!==NULL);
	foreach($ar as $ar2) {
		$cond2=evaluate_expr($row, $ar2);
		if($cond2===NULL) continue;
		if($op=='or') $cond|=$cond2;
		if($op=='and') $cond&=$cond2;
		//var_dump($cond,$cond2);
		assert($cond2!==NULL);
	}
	//var_dump($row['title'], $cond);die();
	return $cond;
}

function colorize($row) {
	global $filters, $colors;
	foreach($filters['colors'] as $col=>$list) {
		foreach($list as $color=>$expr) {
			$cond=evaluate_expr($row, $expr);
			if($cond) {
				$row[$col]='<span style="color:'.$color.'">'.$row[$col].'</span>';
				$colors[$col]=$color;
				break;
			}
		}
	}
	return $row;
}

$filters=file_get_contents('filters.json');
//$filters=json_decode($filters, true, 512, JSON_THROW_ON_ERROR);
require('vendor/autoload.php');
$filters=json5_decode($filters, true, 512, JSON_THROW_ON_ERROR);
//var_dump($filters);die();

$colors=array();

$head=array('title',
 'WLNUpdate cur', 'WLNUpdate last',
 'WebNovel cur', 'WebNovel last-free', 'WebNovel last-paid',
 'RoyalRoad cur', 'RoyalRoad last',
 'start', 'pos', 'last',
 'msg');
//1 WLN 2 WN 3 RR
foreach($wln_order as $id=>$list) {
	if(in_array($id, $filters['lists'])) continue;
	//if( !( strpos(strtolower($id), 'on-hold')!==false || strpos(strtolower($id), 'plan to read')!==false || strpos(strtolower($id), 'completed')!==false || strpos(strtolower($id), 'royalroad')!==false ) ) {
		echo '<h1>'.$id.'</h1>',"\n";
	//}
	$lines=0;
	foreach($list as $entry) {
		$wln1=$wln_books[$entry];
		$row=array();
		$pos1=$pos9=NULL;
		$colors=array();
		$neg_chp=0;
		$row['title']=$wln1[0]->name;
		if(!is_null($wln1[3])) $row['title']=$wln1[3];
		$row['title']=rawurldecode($row['title']);
		$row['title']=html_entity_decode($row['title']);
		$row['WLNUpdate cur']=$wln1[1]->chp;
		$row['WLNUpdate last']=$wln1[2]->chp;
		if(array_key_exists($entry, $cor_wln)) {
			$wln2=$cor_wln[$entry];
			if(!is_null($wln2['wn'])) {
				$wn1=$wn_books[$wln2['wn']];
				$neg_chp=0;
				try {
					$wn_chps=$wn->get_chapter_list_cached($wln2['wn']);
				}
				catch(Exception $e) {
					if($e->getTrace()[0]['args'][0]=='Something went wrong. And we are reporting a custom error message.') {
						if(!array_key_exists('msg', $row)) $row['msg']='';
						$row['msg'].='empty wn: '.__LINE__.' + ';
					}
					$wn_chps=NULL;
				}
				if(is_null($wn_chps)) {
					/*var_dump($wn1);
					var_dump($wln2['wn']);//*/
					try {
						$wn_chps=$wn->get_chapter_list($wln2['wn']);
					}
					catch(Exception $e) {
						if($e->getTrace()[0]['args'][0]=='Something went wrong. And we are reporting a custom error message.') {
							if(!array_key_exists('msg', $row)) $row['msg']='';
							$row['msg'].='empty wn: '.__LINE__.' + ';
						}
						$wn_chps=NULL;
					}
					if(!array_key_exists('msg', $row)) $row['msg']='';
					$row['msg'].='updating wn: '.__LINE__.' + ';
				}
				if(!is_null($wn_chps) && exists($wn_chps, 'data') && (is_object($wn_chps->data) && !exists($wn_chps->data, 'volumeItems'))) {
					var_dump($wn1, $wn_chps);die();
				}
				if(!is_null($wn_chps) && exists($wn_chps, 'data') && is_object($wn_chps->data) && count($wn_chps->data->volumeItems)>0) {
					if(
						(exists($wn_chps->data->volumeItems[0], 'volumeId') && $wn_chps->data->volumeItems[0]->volumeId==0) ||
						(exists($wn_chps->data->volumeItems[0], 'index') && $wn_chps->data->volumeItems[0]->index==0)
					) {
						$neg_chp=count($wn_chps->data->volumeItems[0]->chapterItems);
					}
					if( !exists($wn_chps->data->volumeItems[0], 'volumeId') && !exists($wn_chps->data->volumeItems[0], 'index'))
						throw new BadMethodCallException();
				}
				$last_free=0;
				$found=false;
				if(!is_null($wn_chps) && exists($wn_chps, 'data') && is_object($wn_chps->data) && exists($wn_chps->data, 'volumeItems'))
					foreach($wn_chps->data->volumeItems as $vol) {
						foreach($vol->chapterItems as $chp) {
							if($chp->chapterLevel==0) {
								if(exists($chp, 'chapterIndex') && $chp->chapterIndex>$last_free) {
									$last_free=$chp->chapterIndex;
								}
								else if(exists($chp, 'index') && $chp->index>$last_free) {
									$last_free=$chp->index;
								}
								else if(!exists($chp, 'chapterIndex') && !exists($chp, 'index')) {
									var_dump($chp);
									throw new BadMethodCallException();
								}
							}
							if(exists($chp, 'chapterId') && $chp->chapterId==$wn1->newChapterId) $found=true;
							else if(exists($chp, 'id') && $chp->id==$wn1->newChapterId) $found=true;
							else if(!exists($chp, 'chapterId') && !exists($chp, 'id')) {
								var_dump($chp);
								throw new BadMethodCallException();
							}
						}
					}
				if(!is_null($wn_chps) && exists($wn_chps, 'data') && (!property_exists($wn1, 'newChapterIndex') || abs($wn1->newChapterIndex-$wn1->totalChapterNum)<25) && ($wn1->totalChapterNum>0 && !$found)) {
					//var_dump($wn1, $wn_chps->data->volumeItems[0]->chapterItems[0]);die();
					$wn_chps=$wn->get_chapter_list($wln2['wn']);
					if(!array_key_exists('msg', $row)) $row['msg']='';
					$row['msg'].='updating wn: '.__LINE__.' + ';
				}
				// TODO : redo parse result
				$row['WebNovel last-free']=$last_free;
				if(exists($wn1, 'readToChapterIndex')) $row['WebNovel cur']=$wn1->readToChapterIndex;
				else $row['WebNovel cur']=$wn1->readToChapterNum-$neg_chp;
				//newChapterId/Index or readToChapterId/Index/Num or totalChapterNum
				if(exists($wn1, 'totalChapterIndex')) $row['WebNovel last-paid']=$wn1->totalChapterIndex;
				else $row['WebNovel last-paid']=$wn1->totalChapterNum-$neg_chp;
			}
			if(!is_null($wln2['rr'])) {
				$rr1=$rr_books[$wln2['rr']];
				$rr2_=$rr->get_chapter_list_cached($wln2['rr']);
				if(!exists($rr2_, 0) && ( (is_array($rr2_) && count($rr2_)>0) || (is_object($rr2_) && count(get_object_vars($rr2_))>0) ) ) {
					$rr2=get($rr2_, 'chapters');
				}
				else {
					$rr2=$rr2_;
					unset($rr2_);
				}
				if(exists($rr1, 'last-read-title')) {
					$found=array_filter($rr2, fn($e) => (get($e, 'title')==get($rr1, 'last-read-title')) );
					if(count($found)==0) {
						$rr2_=$rr->get_chapter_list($wln2['rr']);
						if(!exists($rr2_, 0) && ( (is_array($rr2_) && count($rr2_)>0) || (is_object($rr2_) && count(get_object_vars($rr2_))>0) ) ) {
							$rr2=get($rr2_, 'chapters');
						}
						else {
							$rr2=$rr2_;
							unset($rr2_);
						}
						if(!array_key_exists('msg', $row)) $row['msg']='';
						$row['msg'].='updating rr: '.__LINE__.' + ';
					}
					$found=array_filter($rr2, fn($e) => exists($e, 'pos-title')&&strlen(get($e, 'pos-title'))>0);
					if(count($found)>0) $chp_=array_keys($found)[0];
					else $chp_=NULL;
					if(count($found)==0 || $found[$chp_]->title!=get($rr1, 'last-read-title')) {
						$rr2=$rr->get_chapter_list($wln2['rr']);
						if(!exists($rr2_, 0) && ( (is_array($rr2_) && count($rr2_)>0) || (is_object($rr2_) && count(get_object_vars($rr2_))>0) ) ) {
							$rr2=get($rr2_, 'chapters');
						}
						else {
							$rr2=$rr2_;
							unset($rr2_);
						}
						if(!array_key_exists('msg', $row)) $row['msg']='';
						$row['msg'].='updating rr: '.__LINE__.' + ';
					}
				}
				if(exists($rr1, 'last-upd-title')) {
					$found=array_filter($rr2, fn($e) => (get($e, 'title')==get($rr1, 'last-upd-title')) );
					if(count($found)==0) {
						$rr2_=$rr->get_chapter_list($wln2['rr']);
						if(!exists($rr2_, 0) && ( (is_array($rr2_) && count($rr2_)>0) || (is_object($rr2_) && count(get_object_vars($rr2_))>0) ) ) {
							$rr2=get($rr2_, 'chapters');
						}
						else {
							$rr2=$rr2_;
							unset($rr2_);
						}
						if(!array_key_exists('msg', $row)) $row['msg']='';
						$row['msg'].='updating rr: '.__LINE__.' + ';
					}
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
					//if(in_array($row['title'], array('The Last Battlemage', 'The Humble Life of a Skill Trainer', 'A Hero\'s Song', 'Evil Overlord: The Makening'))) { var_dump(__LINE__, $rr2a, $rr2b, $rr2c, $rr2d); }
				}
			}
		}
		$name=strtolower(normalize(name_simplify($row['title'], 1)));
		if(array_key_exists($name, $pos)) {
			$pos1=$pos[$name];
			$row['start']=$pos1['min'];
			$row['pos']=$pos1['pos'];
			$row['last']=$pos1['max'];
			if(array_key_exists($name, $pos_dev9)) {
				$pos9=$pos_dev9[$name];
				$row['start9']=$pos9['min'];
				$row['pos9']=$pos9['pos'];
				$row['last9']=$pos9['max'];
			}
		}
		else {
			$wln_info=$wln->get_info_cached($entry);
			$n3=get(get($wln_info, 'data'), 'alternatenames');
			$n3=array_map(fn($e)=>strtolower(normalize(name_simplify($e, 1))), $n3);
			foreach($n3 as $n3_) {
				if(array_key_exists($n3_, $pos)) {
					$pos1=$pos[$n3_];
					if(array_key_exists($n3_, $pos_dev9)) {
						$pos9=$pos_dev9[$n3_];
						$row['start9']=$pos9['min'];
						$row['pos9']=$pos9['pos'];
						$row['last9']=$pos9['max'];
					}
					$row['start']=$pos1['min'];
					$row['pos']=$pos1['pos'];
					$row['last']=$pos1['max'];
				}
			}
		}
		if(count($row)>1) {
			if(skip($row)) continue;
			$row=colorize($row);
			//var_dump($colors, $id);die();
			if( !array_key_exists('title', $colors) && (startswith($id, 'QIDIAN')||startswith($id, 'RoyalRoad')||startswith($id, 'ScribbleHub'||startswith($id, 'WattPad'))) ) {
			//if( (!array_key_exists('title', $colors)||in_array($colors['title'],array('green','blue') )) && (startswith($id, 'QIDIAN')||startswith($id, 'RoyalRoad')||startswith($id, 'ScribbleHub'||startswith($id, 'WattPad'))) ) {
				$col2=NULL;
				if(startswith($id, 'QIDIAN'))
					$col2='WebNovel last-paid';
				if(startswith($id, 'RoyalRoad'))
					$col2='RoyalRoad last';
				assert($col2!=NULL);
				//$neg_chp
				if( (is_null($pos9)||$pos9['max']!=$row[$col2]) && (array_key_exists('start',$row)||array_key_exists('pos',$row)) ) {
					$pos2=$pos_->createFileContent($row['start'], ($row['pos']<=$row['start']?0:$row['pos']), $row[$col2]);
					$fn2=$pos_->createFileName($pos1['fn2'], $row['start'], $row[$col2]);
					//if($row['start']<1) {var_dump($fn2,$pos1,$pos9,$pos2);die();}
					file_put_contents(DROPBOX.$fn2, $pos2);
					if(!array_key_exists('msg', $row)) $row['msg']='';
					$row['msg'].='.po '.__LINE__.' + ';
					$row['title']='<span style="color:red">'.$row['title'].'</span>';
					//var_dump($fn2,$pos2);die();
				}
				else {
					$pos2=$pos_->createFileContent(1, 0, $row[$col2]);
					$pos1=array('fn2'=>name_simplify($row['title'], 3));
					$fn2=$pos_->createFileName($pos1['fn2'], 1, $row[$col2]);
					if($row['start']<1) {var_dump($fn2,$pos2);die();}
					file_put_contents(DROPBOX.$fn2, $pos2);
					if(!array_key_exists('msg', $row)) $row['msg']='';
					$row['msg'].='.po '.__LINE__.' + ';
					$row['title']='<span style="color:red">'.$row['title'].'</span>';
					var_dump($fn2,$pos2);die();
				}
			}
			unset($row['start9'], $row['pos9'], $row['last9']);
			if(count($row)==0) continue;
			if($lines==0) {
				echo '<table border="1">'."\r\n";
				print_thead_v($head);
			}
			++$lines;
			print_tbody($row, $head);
		}
		if(count($row)>1 && PERLINE) {
			$data.=ob_get_contents();
			if(ob_get_level()>0) { ob_flush(); }
			flush();
		}
	}
	if($lines>0) {
		echo '</table>'."\r\n";
		var_dump($lines);
	}
	$data.=ob_get_contents();
	if(ob_get_level()>0) { ob_flush(); }
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
			$rr2_=$rr->get_chapter_list_cached($wn1['rr']);
			if(!exists($rr2_, 0) && ( (is_array($rr2_) && count($rr2_)>0) || (is_object($rr2_) && count(get_object_vars($rr2_))>0) ) ) {
				$rr2=get($rr2_, 'chapters');
			}
			else {
				$rr2=$rr2_;
				unset($rr2_);
			}
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
		$data.=ob_get_contents();
		if(ob_get_level()>0) { ob_flush(); }
		flush();
	}
}
if($lines>0) {
	echo '</table>'."\r\n";
	var_dump($lines);
}
$data.=ob_get_contents();
if(ob_get_level()>0) { ob_flush(); }
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
	$rr1=NULL;
	if(array_key_exists($rr_id, $cor_rr)) {
		$rr1=$cor_rr[$rr_id];
		if(!is_null($rr1['wln'])) continue; // already in previous big loop
		if(!is_null($rr1['wn'])) continue; // already in previous big loop
	}
	$rr2_=$rr->get_chapter_list_cached($rr_id);
	if(!exists($rr2_, 0) && ( (is_array($rr2_) && count($rr2_)>0) || (is_object($rr2_) && count(get_object_vars($rr2_))>0) ) ) {
		$rr2=get($rr2_, 'chapters');
	}
	else {
		$rr2=$rr2_;
		unset($rr2_);
	}
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
		if(!in_array($rr_id,array(7015,45718))) { var_dump($rr_id, $row['title'], $rr1, $rr2);die(); }
	}
	$name=strtolower(normalize(name_simplify($row['title'], 1)));
	if(array_key_exists($name, $pos)) {
		$pos1=$pos[$name];
		$row['start']=$pos1['min'];
		$row['pos']=$pos1['pos'];
		$row['last']=$pos1['max'];
	}
	if(count($row)>1||count($rr2)==0) {
		if($lines==0) {
			echo '<table border="1">'."\r\n";
			print_thead_v($head);
		}
		++$lines;
		print_tbody($row, $head);
	}
	if(PERLINE) {
		$data.=ob_get_contents();
		if(ob_get_level()>0) { ob_flush(); }
		flush();
	}
}
if($lines>0) {
	echo '</table>'."\r\n";
	var_dump($lines);
}
$data.=ob_get_contents();
if(ob_get_level()>0) { ob_flush(); }
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
		$data.=ob_get_contents();
		if(ob_get_level()>0) { ob_flush(); }
		flush();
	}
}
if($lines>0) {
	echo '</table>'."\r\n";
	var_dump($lines);
}
$data.=ob_get_contents();
if(ob_get_level()>0) { ob_flush(); }
flush();

$data.=ob_get_contents();
//$data=ob_get_clean(); // return-only (no print)
//$data=ob_get_flush(); // return and print
ob_flush();
flush();
file_put_contents('news.htm', $data);
unset($data);

if(!defined('DROPBOX_DONE')||!DROPBOX_DONE) include('footer.php');
?>