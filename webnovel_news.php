<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('WLNUpdates.php');
require_once('WebNovel.php');

if(!defined('MOONREADER_DID')) define('MOONREADER_DID', '1454083831785');
if(!defined('MOONREADER_DID2')) define('MOONREADER_DID2', '9999999999999');

$skip_existing=false;
$timeref=time();

include('header.php');

//include_once('watches.inc.php');
$watches=json_decode(str_replace("\t",'',file_get_contents('wlnupdates/watches.json')),TRUE,512,JSON_THROW_ON_ERROR);// important : true as 2nd parameter
$books=json_decode(str_replace("\t",'',file_get_contents('webnovel/_books.json')),false,512,JSON_THROW_ON_ERROR);

$wln=new WLNUpdates;
$wn=new WebNovel;

chdir(DROPBOX);
$ar=glob('*.po');
natcasesort($ar);

chdir(CWD);

$fns=array();
$fns2=array();
foreach($ar as $fn)
{
	preg_match('#^(.*)_(-?\d+)-(\d+)(_FIN)?\.epub\.po$#i', $fn, $matches);
	if(!empty($matches)) {
		$fn2=$matches[1];
		$min=$matches[2];
		$max=$matches[3];
	}
	else {
		preg_match('#^(-?\d+)-(\d+)_(.*)\.epub\.po$#i', $fn, $matches);
		if(!empty($matches))
		{
			$min=$matches[1];
			$max=$matches[2];
			$fn2=$matches[3];
		}
	}
	if(empty($matches))
	{
		continue;
	}
	/*$fn2=str_replace(array('_'), ' ', $fn2);
	$fn2=str_replace(array('Retranslated Version'), '', $fn2);
	$fn2=trim($fn2);//*/
	//var_dump($fn2);
	$fn3=name_simplify($fn2, 1);
	$fn3=strtolower($fn3);
	//var_dump($fn3);
	$data=file_get_contents(DROPBOX.$fn);
	$content=array();
	$content[]=strtok($data, '*@#:%');
	while(($content[]=strtok('*@#:%'))!==FALSE);
	unset($data);
	$id=array_search(false, $content, true);
	$content=array_slice($content, 0, $id);
	$fns2[$fn]=array_merge(array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2, 'fn3'=>$fn3), $content);
	if( !array_key_exists($fn3, $fns) )
	{
		$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2);
		$ar2=array_merge($ar2, $content);
		$fns[$fn3]=$ar2;
	}
	else if(
		$max>$fns[$fn3]['max'] && //new last chapter is >
		(($min+($min<0?1:0)+$content[1]+($content[3]>0?1:0)) >= ($fns[$fn3]['min']+($fns[$fn3]['min']<0?1:0)+$fns[$fn3][1]+($fns[$fn3][3]>0?1:0))) // position is same or later (no diff between end of chapter and start of new one)
	) {
		$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2);
		$ar2=array_merge($ar2, $content);
		$fns[$fn3]=$ar2;
	}
	else if(
		$max < $fns[$fn3]['max'] && //new last chapter is >
		(($min+($min<0?1:0)+$content[1]+($content[3]>0?1:0)) <= ($fns[$fn3]['min']+($fns[$fn3]['min']<0?1:0)+$fns[$fn3][1]+($fns[$fn3][3]>0?1:0))) // position is same or later (no diff between end of chapter and start of new one)
	) {
		//
	}
}
uksort($fns2, 'version_compare');
//var_dump($fns);die();

$diff_old=json_decode(file_get_contents('wn_diff.json'), TRUE, 512, JSON_THROW_ON_ERROR); // important : true as 2nd parameter
if(!array_key_exists('cur',$diff_old)||!is_array($diff_old['cur'])) $diff_old['cur']=array();
if(!array_key_exists('upd',$diff_old)||!is_array($diff_old['upd'])) $diff_old['upd']=array();
if(!array_key_exists('chk',$diff_old)||!is_array($diff_old['chk'])) $diff_old['chk']=array();
if(!array_key_exists('old',$diff_old)||!is_array($diff_old['old'])) $diff_old['old']=array();
$diff=array(); // cur, up-to-date
$diff2=array(); // upd, up-to-date but not updated yet
$diff3=array(); // chk, not up-to-date, but will be soon (tm)
$diff4=array(); // old, not up-to-date (or updated at old position)

$head=array('title', 'WLNUpdate', 'WebNovel', 'new chp', 'subName', 'start',
 'Last upd', 'Last chk',
// 'Last chp',
 'msg');
if(ob_get_level()>0) { ob_end_flush(); ob_flush(); }
flush();
//foreach($watches as $id=>$list) { // WLN list
foreach($watches['data'][0] as $id=>$list) { // WLN list
	//if( strpos(strtolower($id), 'on-hold')!==false || strpos(strtolower($id), 'plan to read')!==false || strpos(strtolower($id), 'completed')!==false ) continue;
	if( !( strpos(strtolower($id), 'on-hold')!==false || strpos(strtolower($id), 'plan to read')!==false || strpos(strtolower($id), 'completed')!==false || strpos(strtolower($id), 'royalroad')!==false ) ) {
		echo '<h1>'.$id.'</h1>',"\n";
	}
	$lines=0;
	foreach($list as $entry) { // WLN book
		//TODO : fix the next 2 lines
		$entry['title']=(strlen($entry[3])>0)?$entry[3]:$entry[0]['name'];
		$entry['chp']=$entry[1]['chp'];
		foreach($books as $book) { // WN book
			if(!property_exists($book, 'bookName')) { var_dump($book); die(); }
			if( $book->novelType==0 && name_compare($entry['title'], $book->bookName) ) {
				
				$row=array();
				
				//retrieving list of chapters
				if(!file_exists('webnovel/GetChapterList_'.$book->bookId.'.json'))
				{
					$res=$wn->get_chapter_list($book->bookId);
					if($res->code!=0 || $res->data===0) {
						var_dump('file', $book->bookId, $book->bookName, $entry['title'], $res);
						unlink('webnovel/GetChapterList_'.$book->bookId.'.json');
						die();
					}
				}
				else {
					//$res=json_decode('webnovel/GetChapterList_'.$book->bookId.'.json', false, 512, JSON_THROW_ON_ERROR);
					$res=json_decode(str_replace("\t",'',file_get_contents('webnovel/GetChapterList_'.$book->bookId.'.json')),false,512,JSON_THROW_ON_ERROR);
					//var_dump($res);die();
					if(!is_object($res) || !property_exists($res, 'code') || $res->code!=0 || !property_exists($res, 'data') || $res->data===0 || !property_exists($res->data, 'bookInfo')) {
						var_dump('request', $book->bookId, $book->bookName, $entry['title'], $res);
						unlink('webnovel/GetChapterList_'.$book->bookId.'.json');
						die();
					}
				}
				if( !isset($res->data) || !isset($res->data->bookInfo) || !isset($res->data->volumeItems) ) {
					//no info
					var_dump($book->bookId, $entry['title'], $entry['title'], $res);die();
				}
				if( count($res->data->volumeItems)==0 ) {
					//empty book
					try {
						$res=@$wn->get_chapter_list($book->bookId);
					} catch (Exception $e) {
					}
					continue;
				}
				//fixing chapter number
				$add=0;
				$add_=0;
				$add2=NULL;
				$priv_only=0;
				$max_pub=0;
				$chp_id=0;
				$last_chp=0;
				$last_upd='1970-01-01';
				$timestamp=filemtime('webnovel/GetChapterList_'.$book->bookId.'.json');
				if(property_exists($res->data->volumeItems[0], 'index') && $res->data->volumeItems[0]->index==0) $add_=$add=-$res->data->volumeItems[0]->chapterCount; // substract auxiliary volume chapters
				else if(property_exists($res->data->volumeItems[0], 'volumeId') && $res->data->volumeItems[0]->volumeId==0) $add_=$add=-count($res->data->volumeItems[0]->chapterItems); // substract auxiliary volume chapters
				if(array_key_exists($entry['title'], $diff_old)) $add2=$diff_old[$entry['title']];
				else if(array_key_exists($entry['title'], $diff_old['cur'])) $add2=$diff_old['cur'][$entry['title']];
				else if(array_key_exists($entry['title'], $diff_old['upd'])) $add2=$diff_old['upd'][$entry['title']];
				else if(array_key_exists($entry['title'], $diff_old['chk'])) $add2=$diff_old['chk'][$entry['title']];
				else if(array_key_exists($entry['title'], $diff_old['old'])) $add2=$diff_old['old'][$entry['title']];
				//else {
					foreach($res->data->volumeItems as $vol) {
						foreach($vol->chapterItems as $chap) {
							$index=-1;
							if(property_exists($chap, 'index')) $index=$chap->index;
							else if(property_exists($chap, 'chapterIndex')) $index=$chap->chapterIndex;
							$chid=0;
							if(property_exists($chap, 'id')) $chid=$chap->id;
							else if(property_exists($chap, 'chapterIndex')) $chid=$chap->chapterId;
							if($chap->chapterLevel!=0) $priv_only++;
							else if($index>$max_pub) $max_pub=$index;
							if($chid==$book->readToChapterId) $chp_id=$chid;
							$createTime='';
							if(property_exists($chap, 'createTime')) $createTime=$chap->createTime;
							else if(property_exists($chap, 'publishTimeFormat')) $createTime=parse_publishTimeFormat($chap->publishTimeFormat);
							//var_dump(date('Y-m-d H:i:s',strtotime($createTime, $timestamp)));die();
							//var_dump(date('Y-m-d H:i:s',strtotime($last_upd, $timestamp)));die();
							if(strtotime($createTime, $timestamp)>strtotime($last_upd, $timestamp)) {
								$last_upd=$createTime;
								//var_dump($last_upd, strtotime($last_upd, $timestamp));//die();
								$last_chp=$index;
							}
						}
					}
					//var_dump($last_upd, strtotime($last_upd, $timestamp));die();
					if($add2===NULL) $add2=0;
					if($add2===0) $add2=$priv_only;
				//}
				//var_dump($last_upd, strtotime($last_upd, $timestamp),__LINE__);//die();
				if($last_upd=='1970-01-01') {
					//var_dump($res->data->volumeItems);
					var_dump($timestamp,date('Y-m-d H:i:s', $timestamp));
					foreach($res->data->volumeItems as $vol) {
						foreach($vol->chapterItems as $chap) {
							var_dump($chap->publishTimeFormat);
							var_dump(strtotime(parse_publishTimeFormat($chap->publishTimeFormat),$timestamp));
							var_dump(date('Y-m-d H:i:s', strtotime(parse_publishTimeFormat($chap->publishTimeFormat),$timestamp)));
						}
					}
					die();
				}
				//updating list of chapters
				if( $book->newChapterIndex > $res->data->bookInfo->totalChapterNum+$add) {
					$row['msg']=array_merge((array_key_exists('msg',$row)?$row['msg']:array()), array('updating'));
					$count=0;
					do {
						try {
							$res=@$wn->get_chapter_list($book->bookId);
							//var_dump($res);die();
						} catch (Exception $e) {
							var_dump($e,  $res);die();
						}
						++$count;
						if( $count>5 && (!is_object($res) || !property_exists($res, 'data') || (is_int($res->data)&&$res->data==0) || !property_exists($res->data, 'bookInfo')) ) {
							var_dump('updating', $book->bookId, $book->bookName, $entry['title'], $res);
							die();
						}
					} while (!is_object($res) || !property_exists($res, 'data') || (is_int($res->data)&&$res->data==0) || !property_exists($res->data, 'bookInfo'));
					$timestamp=filemtime('webnovel/GetChapterList_'.$book->bookId.'.json');
					$priv_only=0;
					$last_upd=0;
					$last_chp=0;
					$add_=0;
					if(property_exists($res->data->volumeItems[0], 'index') && $res->data->volumeItems[0]->index==0) $add_=-$res->data->volumeItems[0]->chapterCount; // substract auxiliary volume chapters
					if(property_exists($res->data->volumeItems[0], 'chapterIndex') && $res->data->volumeItems[0]->chapterIndex==0) $add_=-count($res->data->volumeItems[0]->chapterItems); // substract auxiliary volume chapters
					foreach($res->data->volumeItems as $vol) {
						foreach($vol->chapterItems as $chap) {
							if($chap->chapterLevel!=0) $priv_only++;
							$index=-1;
							if(property_exists($chap, 'index')) $index=$chap->index;
							else if(property_exists($chap, 'chapterIndex')) $index=$chap->chapterIndex;
							$createTime='';
							if(property_exists($chap, 'createTime')) $createTime=$chap->createTime;
							else if(property_exists($chap, 'publishTimeFormat')) $createTime=parse_publishTimeFormat($chap->publishTimeFormat);
							if(strtotime($createTime, $timestamp)>strtotime($last_upd, $timestamp)) {
								$last_upd=$createTime;
								//var_dump($last_upd);die();
								$last_chp=$index;
							}
						}
					}
					//var_dump($last_upd, strtotime($last_upd, $timestamp));die();
				}
				if( strpos(strtolower($id), 'on-hold')!==false || strpos(strtolower($id), 'plan to read')!==false || strpos(strtolower($id), 'completed')!==false || strpos(strtolower($id), 'royalroad')!==false ) {
					if(!is_numeric($book->readToChapterIndex)) {
						$data=$wn->read_update($book, $entry['chp']);
						assert($data->code===0 && $data->msg==='Success') or die('update "'.$entry['title'].' failed.');
						//var_dump($book, $entry['chp'], $chp_id);die();
						continue;
					}
					if(!is_object($res)||!property_exists($res, 'data')||!property_exists($res->data,'bookInfo')) var_dump($entry['title'], $book->bookName, $res);
					if( !( ($book->readToChapterIndex-$add) == ($res->data->bookInfo->totalChapterNum+$add) ) ) {
						$diff4[$entry['title']]=$priv_only;
					}
					continue;
				}
				if($add2!=$priv_only) {
					//var_dump($add, $add_, $add2, $priv_only, $entry, $book, $res);
					$row['msg']=array_merge((array_key_exists('msg',$row)?$row['msg']:array()), array('priv'));
					$diff3[$entry['title']]=$priv_only;
				}
				//checking new chapters
				if( $res->data->bookInfo->totalChapterNum+$add > (int)$entry['chp'] ) {
					chdir(DROPBOX);
					$title=name_simplify($entry['title'], 1);
					$title2=strtolower($title);
					$name=name_simplify($title, 4); // 2 regex, 4 glob
					//var_dump($fns);die();
					if(!array_key_exists($title2, $fns)) { var_dump($entry['title'], $book->bookName); var_dump(strtolower(name_simplify($entry['title'], 1)), strtolower(name_simplify($book->bookName, 1))); var_dump($title); var_dump(array_keys($fns)); die(); }
					$ar2=$fns[$title2];
					
					//$filename='*_*-'.($res->data->bookInfo->totalChapterNum+$add).'.epub.po';
					$filename=$name.'_*-*.epub.po';
					//$regex='#^'.$name.'#i';
					$regex='#-'.($res->data->bookInfo->totalChapterNum+$add).'.epub.po$#i';
					/*$exists=array_values(preg_grep($regex,iglob($filename)));
					//var_dump($filename,$regex,$exists);die();
					$content=array();
					if(count($exists)==1) {
						//var_dump($exists);
						$data=file_get_contents($exists[0]);
						$content[]=strtok($data, '*@#:%');
						while(($content[]=strtok('*@#:%'))!==FALSE);
					}//*/
					
					$exists=array(); $content=array();
					foreach($fns2 as $fn=>$ar) {
						if(fnmatch(strtolower($filename), strtolower($fn)) && preg_match($regex, $fn)==1) {
							$exists[]=$ar;
							$content=$ar;
						}
					}
					/*if(count($exists)==0) {
						var_dump($filename, $regex, $fn, $title2, $ar2);
						var_dump( fnmatch(strtolower($filename), strtolower($ar2['fn'])) );
						var_dump( preg_match($regex, strtolower($ar2['fn'])) );
						die();
					}//*/
					
					//var_dump(!$skip_existing, $exists);
					//if($content[0]==MOONREADER_DID2) var_dump($title);
					chdir(CWD);
					if( !$skip_existing
						|| count($exists)==0
						|| ( count($exists)==1 && array_key_exists(0,$content) && $content[0]==MOONREADER_DID2 ) // exists, but is from this script
						|| ( count($exists)==1 && $ar2['min']<=1 && $ar2['min']!=($add==0?1:$add) ) // exists, but wrong negative chapter number
						//|| ( count($exists)==1 && array_key_exists(4,$content) && $content[4]!='100' ) // i'm not at the end
					) {
						//var_dump($entry['title'], (int)$entry['chp'], $book->readToChapterIndex+$add2, $res->data->bookInfo->bookSubName, $res->data->bookInfo->totalChapterNum+$add);
						$subName='';
						if(property_exists($res->data->bookInfo, 'bookSubName')) $subName=$res->data->bookInfo->bookSubName;
						$row=array_merge($row,array('title'=>$entry['title'], 'WLNUpdate'=>(int)$entry['chp'], 'WebNovel'=>$book->readToChapterIndex, 'new chp'=>$res->data->bookInfo->totalChapterNum+$add, 'subName'=>$subName));
						if(strlen($subName)==0) {
							$res2=$wn->get_info_html_cached($book->bookId);
							//var_dump($res2);die();
							$subName='';
							if(is_object($res2)) $subName=$res2->bookInfo->bookSubName;
							else if(is_array($res2)) $subName=$res2['bookInfo']['bookSubName'];
							if(strlen($subName)==0) {
								$row['subName']=implode('', array_map(function($s) { return $s[0]; }, explode(' ',$book->bookName)));
								$row['subName']='<span style="color:#0000FF">'.$row['subName'].'</span>';
							}
							else {
								$row['subName']=$subName;
								$row['subName']='<span style="color:#00FF00">'.$row['subName'].'</span>';
							}
						}
						if($row['subName']=='PD'&&$book->bookName=='Plague Doctor') $row['subName']='Plague'; // epub source bug in subnames duplicates
						if($ar2['min']>1) $row['start']=$ar2['min'];
						$chp=$res->data->bookInfo->totalChapterNum+$add;
						$min=($ar2['min']>=1?$ar2['min']:($add<0?$add:1));
						$min2=($add<0?$add:($ar2['min']>1?$ar2['min']-1:0));
						$fn=$ar2['fn2'].'_'.$min.'-'.$chp.'.epub.po';
						//if($res->data->bookInfo->bookSubName=='MVS') var_dump($ar2, $add, $fn, $exists);
						//unlink(DROPBOX.$fn);
						$fn=str_replace(' ', '_', $fn);
						//var_dump($content[1],$entry['chp'],$ar2['min'],$min,$add,$min2);
						if(!file_exists(DROPBOX.$fn) || (($content[1]+$min2)!=($entry['chp']>1?$entry['chp']:0)) ) {
							$chp_=(int)$entry['chp'];
							$numerator=$chp_;
							if($min<0) $numerator-=$min; // - - => +
							if($min>0) $numerator-=($min-1); // starts at 1
							if($chp_==1) $numerator=0; // chapter 1 is the first, so it's generally unread
							$denominator=$chp;
							if($min<0) $denominator-=$min;
							if($min>0) $denominator-=($min-1);
							$chp2=(100*$numerator)/$denominator;
							$num=number_format($chp2,1);
							if($num=='100.0') $num='100';
							if($num=='100' && $chp_!=$chp) $num='99.9';
							$did=$ar2[0]; //old d[evice] id
							//assert($did!=MOONREADER_DID2) or die('this shouldn\'t happen, or you\'re unlucky to have the same DeviceID.');
							//$did=str_repeat('9',strlen($did)); //new d[evice] id
							$did=MOONREADER_DID2;
							if($chp_==1) $chp_=0;
							if($min<0) $chp_-=$min;
							if($min>0) $chp_-=($min-1);
							$data=$did.'*'.$chp_.'@'.$ar2[2].'#0:'.$num.'%';
							file_put_contents(DROPBOX.$fn, $data);
							$row['msg']=array_merge((array_key_exists('msg',$row)?$row['msg']:array()), array('.po'));
						}//*/
						$diff3[$entry['title']]=$priv_only;
					}
					if(
						( count($exists)==1 && array_key_exists(4,$content) && $content[4]=='100' ) // i'm at the end) {
					) {
						$diff2[$entry['title']]=$priv_only;
					}
					else {
						$diff4[$entry['title']]=$priv_only;
					}
				}
				else {
					//up-to-date
					if( !( ($book->readToChapterIndex+$add2) == ($res->data->bookInfo->totalChapterNum+$add) ) )
					{
//						var_dump($book,$book->readToChapterIndex,$add,$add2,$priv_only,$max_pub,$res->data->bookInfo);
						$res=$wn->get_chapter_list($book->bookId);
						if( (!is_object($res) || !property_exists($res, 'data') || (is_int($res->data)&&$res->data==0) || !property_exists($res->data, 'bookInfo')) ) {
							$res=$wn->get_chapter_list($book->bookId);
						}
						$priv_only=0;
						$last_upd=0;
						$last_chp=0;
						if(property_exists($res->data->volumeItems[0],'index') && $res->data->volumeItems[0]->index==0) $add_=-$res->data->volumeItems[0]->chapterCount; // substract auxiliary volume chapters
						if(property_exists($res->data->volumeItems[0],'chapterIndex') && $res->data->volumeItems[0]->chaperIndex==0) $add_=-count($res->data->volumeItems[0]->chapterItems); // substract auxiliary volume chapters
						foreach($res->data->volumeItems as $vol) {
							foreach($vol->chapterItems as $chap) {
								if($chap->chapterLevel!=0) $priv_only++;
								$index=-1;
								if(property_exists($chap, 'index')) $index=$chap->index;
								else if(property_exists($chap, 'chapterIndex')) $index=$chap->chapterIndex;
								$createTime='';
								if(property_exists($chap, 'createTime')) $createTime=$chap->createTime;
								else if(property_exists($chap, 'publishTimeFormat')) $createTime=parse_publishTimeFormat($chap->publishTimeFormat);
								if(strtotime($createTime, $timestamp)>strtotime($last_upd, $timestamp)) {
									$last_upd=$createTime;
									var_dump($last_upd);die();
									$last_chp=$chid;
								}
							}
						}
						var_dump($last_upd, strtotime($last_upd, $timestamp));die();
						$add2=$priv_only;
//						var_dump($res->data->bookInfo);
					}
//					assert( ($book->readToChapterIndex+$priv_only) == ($res->data->bookInfo->totalChapterNum+$add) );
					$diff[$entry['title']]=$priv_only;
				}
				if(count($row)>0) {
					if(count($row)==1 && array_keys($row)[0]=='msg') {
						$row=array_merge(array('title'=>$entry['title'], 'WLNUpdate'=>(int)$entry['chp'], 'WebNovel'=>$book->readToChapterIndex, 'new chp'=>$res->data->bookInfo->totalChapterNum+$add, 'subName'=>$res->data->bookInfo->bookSubName), $row);
					}
					if( (count($row)>=1 && array_keys($row)[0]=='msg' && $ar2['min']<0) && $add!=$add_) {
						$index=0;
						if(property_exists($res->data->volumeItems[0], 'index')) $index=$res->data->volumeItems[0]->index;
						if(property_exists($res->data->volumeItems[0], 'chapterIndex')) $index=$res->data->volumeItems[0]->chapterIndex;
						$count=0;
						if(property_exists($res->data->volumeItems[0], 'chapterCount')) $count=$res->data->volumeItems[0]->chapterCount;
						else $count=count($res->data->volumeItems[0]->chapterItems);
						
						$row['msg'][]=(($index==0)?'(-'.$count.')':'(0)'); // auxiliary volume chapters'';
					}
					$row['Last upd']=timetostr(strtotime($last_upd, $timestamp));
					$row['Last chk']=timetostr($timestamp);
					//$row['Last chp']=$last_chp;
					
					if(
						$row['WLNUpdate']==$row['new chp'] ||
						( count($exists)==1 && array_key_exists(0,$content) && $content[0]==MOONREADER_DID )
					) $row['title']='<span style="color:#0000FF">'.$row['title'].'</span>';
					
					$row=array_merge(array_diff_key($row,array('msg'=>'')),(array_key_exists('msg',$row)?array('msg'=>$row['msg']):array()));
					if(array_key_exists('msg',$row)) $row['msg']=implode(' + ', $row['msg']);
					if($lines==0) {
						echo '<table border="1">'."\r\n";
						print_thead_v($head);
					}
					
					print_tbody($row, $head);
					++$lines;
				}
			}
			if(ob_get_level()>0) { ob_end_flush(); ob_flush(); }
			flush();
		}
	}
	if($lines>0) {
		echo '</table>'."\r\n";
	}
}
if(ob_get_level()>0) { ob_end_flush(); ob_flush(); }
flush();
$diff=array('cur'=>$diff,'upd'=>$diff2,'chk'=>$diff3,'old'=>array_diff_key(array_merge($diff4,$diff_old['cur'],$diff_old['upd'],$diff_old['old']),array_merge($diff,$diff2,$diff3)));
file_put_contents('wn_diff.json', $wn->jsonp_to_json(json_encode($diff)));
//var_dump(count($diff['cur']),count($diff['upd']),count($diff['chk']),count($diff['old']));
//var_dump(count($diff['cur'])+count($diff['upd'])+count($diff['chk'])+count($diff['old']));
var_dump($diff);

include('footer.php');
