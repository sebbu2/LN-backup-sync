<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('WLNUpdates.php');
require_once('WebNovel.php');

if(!defined('MOONREADER_DID')) define('MOONREADER_DID', '1454083831785');
if(!defined('MOONREADER_DID2')) define('MOONREADER_DID2', '9999999999999');

$skip_existing=true;

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
	$fn2=str_replace(array('_'), ' ', $fn2);
	$fn3=strtolower($fn2);
	$data=file_get_contents(DROPBOX.$fn);
	$content=array();
	$content[]=strtok($data, '*@#:%');
	while(($content[]=strtok('*@#:%'))!==FALSE);
	unset($data);
	$id=array_search(false, $content, true);
	$content=array_slice($content, 0, $id);
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

$diff_old=json_decode(file_get_contents('wn_diff.json'), TRUE, 512, JSON_THROW_ON_ERROR); // important : true as 2nd parameter
if(!array_key_exists('cur',$diff_old)) $diff_old['cur']=array();
if(!array_key_exists('old',$diff_old)) $diff_old['old']=array();
$diff=array();

//foreach($watches as $id=>$list) { // WLN list
foreach($watches['data'][0] as $id=>$list) { // WLN list
	if( strpos(strtolower($id), 'on-hold')!==false || strpos(strtolower($id), 'plan to read')!==false || strpos(strtolower($id), 'completed')!==false ) continue;
	echo '<h1>'.$id.'</h1>';
	foreach($list as $entry) { // WLN book
		//TODO : fix the next 2 lines
		$entry['title']=(strlen($entry[3])>0)?$entry[3]:$entry[0]['name'];
		$entry['chp']=$entry[1]['chp'];
		foreach($books as $book) { // WN book
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
					if($res->code!=0 || $res->data===0) {
						var_dump('request', $book->bookId, $book->bookName, $entry['title'], $res);
						unlink('webnovel/GetChapterList_'.$book->bookId.'.json');
						die();
					}
				}
				if( !isset($res->data) || !isset($res->data->bookInfo) || !isset($res->data->volumeItems) || count($res->data->volumeItems)==0 ) {
					var_dump($book->bookId, $entry['title'], $entry['title'], $res);die();
				}
				//fixing chapter number
				$add=0;
				$add2=0;
				$priv_only=0;
				$max_pub=0;
				if($res->data->volumeItems[0]->index==0) $add=-$res->data->volumeItems[0]->chapterCount; // substract auxiliary volume chapters
				if(array_key_exists($entry['title'], $diff_old)) $add2+=$diff_old[$entry['title']];
				else if(array_key_exists($entry['title'], $diff_old['cur'])) $add2+=$diff_old['cur'][$entry['title']];
				else if(array_key_exists($entry['title'], $diff_old['old'])) $add2+=$diff_old['old'][$entry['title']];
				else {
					foreach($res->data->volumeItems as $vol) {
						foreach($vol->chapterItems as $chap) {
							if($chap->chapterLevel!=0) $priv_only++;
							else if($chap->index>$max_pub) $max_pub=$chap->index;
						}
					}
					$add2+=$priv_only;
				}
				//updating list of chapters
				if( $book->newChapterIndex > $res->data->bookInfo->totalChapterNum+$add) {
					var_dump('updating',$entry['title']);
					$res=$wn->get_chapter_list($book->bookId);
				}
				//checking new chapters
				if( $res->data->bookInfo->totalChapterNum+$add > (int)$entry['chp'] ) {
					chdir(DROPBOX);
					$title=name_simplify($entry['title']);
					$title=str_replace('+', '', $title);
					$name=str_replace(' ','.*', $title);
					$filename='*_*-'.($res->data->bookInfo->totalChapterNum+$add).'.epub.po';
					$ar2=$fns[$title];
					$exists=array_values(preg_grep('#^'.$name.'#i',glob($filename)));
					$content=array();
					if(count($exists)==1) {
						//var_dump($exists);
						$data=file_get_contents($exists[0]);
						$content[]=strtok($data, '*@#:%');
						while(($content[]=strtok('*@#:%'))!==FALSE);
					}//*/
					//var_dump(!$skip_existing, $exists);
					//var_dump($content[4]);
					chdir(CWD);
					if( !$skip_existing
						|| count($exists)==0
						//|| (count($exists)==1&&array_key_exists(4,$content)&&$content[0]==MOONREADER_DID&&$content[4]!='100')
					) {
						var_dump($entry['title'], (int)$entry['chp'], $book->readToChapterIndex+$add2, $res->data->bookInfo->bookSubName, $res->data->bookInfo->totalChapterNum+$add);
						$chp=$res->data->bookInfo->totalChapterNum+$add;
						$fn=$ar2['fn2'].'_'.$ar2['min'].'-'.$chp.'.epub.po';
						//unlink(DROPBOX.$fn);
						$fn=str_replace(' ', '_', $fn);
						if(!file_exists(DROPBOX.$fn)) {
							$numerator=(int)$entry['chp'];
							if($ar2['min']<0) $numerator-=$ar2['min']; // - - => +
							if($ar2['min']>0) $numerator-=($ar2['min']-1); // starts at 1
							$denominator=$chp;
							if($ar2['min']<0) $denominator-=$ar2['min'];
							if($ar2['min']>0) $denominator-=($ar2['min']-1);
							$chp2=(100*$numerator)/$denominator;
							$num=number_format($chp2,1);
							if($num=='100.0') $num='100';
							$did=$ar2[0]; //old d[evice] id
							assert($did!=MOONREADER_DID2) or die('this shouldn\'t happen, or you\'re unlucky to have the same DeviceID.');
							//$did=str_repeat('9',strlen($did)); //new d[evice] id
							$did=MOONREADER_DID2;
							$data=$did.'*'.($entry['chp']).'@'.$ar2[2].'#0:'.$num.'%';
							file_put_contents(DROPBOX.$fn, $data);
							var_dump($fn.' written');
						}//*/
					}
					if(isset($priv_only)) $diff_old['old'][$entry['title']]=$add2;
				}
				else {
					//up-to-date
					if( !( ($book->readToChapterIndex+$add2) == ($res->data->bookInfo->totalChapterNum+$add) ) )
					{
						var_dump($book,$add,$add2,$priv_only,$max_pub,$res->data->bookInfo);
					}
					assert( ($book->readToChapterIndex+$add2) == ($res->data->bookInfo->totalChapterNum+$add) );
					$diff[$entry['title']]=$res->data->bookInfo->totalChapterNum+$add - $book->readToChapterIndex;
				}
				print('');
			}
		}
	}
}
$diff=array('cur'=>$diff,'old'=>array_diff_key(array_merge($diff_old['cur'],$diff_old['old']),$diff));
file_put_contents('wn_diff.json', $wn->jsonp_to_json(json_encode($diff)));
var_dump($diff);
