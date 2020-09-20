<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('WLNUpdates.php');
require_once('WebNovel.php');

$skip_existing=true;

//include_once('watches.inc.php');
$watches=json_decode(str_replace("\t",'',file_get_contents('wlnupdates/watches.json')),TRUE,512,JSON_THROW_ON_ERROR);// important : true as 2nd parameter
$books=json_decode(str_replace("\t",'',file_get_contents('webnovel/_books.json')),false,512,JSON_THROW_ON_ERROR);

$wln=new WLNUpdates;
$wn=new WebNovel;

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
					$name=str_replace(' ','.*', name_simplify($entry['title']));
					$filename='*_*-'.($res->data->bookInfo->totalChapterNum+$add).'.epub.po';
					$exists=preg_grep('#^'.$name.'#i',glob($filename));
					//var_dump(!$skip_existing, $exists);
					chdir(CWD);
					if( !$skip_existing || count($exists)==0 )
						var_dump($entry['title'], (int)$entry['chp'], $book->readToChapterIndex+$add2, $res->data->bookInfo->bookSubName, $res->data->bookInfo->totalChapterNum+$add);
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
			}
		}
	}
}
$diff=array('cur'=>$diff,'old'=>array_diff_key(array_merge($diff_old['cur'],$diff_old['old']),$diff));
file_put_contents('wn_diff.json', $wn->jsonp_to_json(json_encode($diff)));
var_dump($diff);
