<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('WLNUpdates.php');
require_once('WebNovel.php');

include_once('watches.inc.php');
//$books=json_decode('webnovel/_books.json',false,512,JSON_THROW_ON_ERROR);
$books=json_decode(str_replace("\t",'',file_get_contents('webnovel/_books.json')),false,512,JSON_THROW_ON_ERROR);
//var_dump($books);die();

$wln=new WLNUpdates;
$wn=new WebNovel;

$diff_old=json_decode(file_get_contents('wn_diff.json'), TRUE, 512, JSON_THROW_ON_ERROR); // important : true as 2nd parameter
$diff=array();

foreach($watches as $id=>$list) { // WLN list
	if( strpos($id, 'On-Hold')!==false || strpos($id, 'Plan To Read')!==false || strpos($id, 'Completed')!==false ) continue;
	echo '<h1>'.$id.'</h1>';
	foreach($list as $entry) { // WLN book
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
				if( !isset($res->data) || !isset($res->data->bookInfo) || !isset($res->data->volumeItems) ) {
					var_dump($book->bookId, $entry['title'], $entry['title'], $res);die();
				}
				//fixing chapter number
				$add=0;
				$add2=0;
				if($res->data->volumeItems[0]->index==0) $add=-$res->data->volumeItems[0]->chapterCount; // substract auxiliary volume chapters
				if(array_key_exists($entry['title'], $diff_old)) $add2+=$diff_old[$entry['title']];
				//updating list of chapters
				if( $book->newChapterIndex > $res->data->bookInfo->totalChapterNum+$add) {
					var_dump('updating',$entry['title']);
					$res=$wn->get_chapter_list($book->bookId);
				}
				//checking new chapters
				if( $res->data->bookInfo->totalChapterNum+$add > (int)$entry['chp'] ) {
					var_dump($entry['title'], (int)$entry['chp'], $book->readToChapterIndex+$add2, $res->data->bookInfo->bookSubName, $res->data->bookInfo->totalChapterNum+$add);
					
				}
				else {
					//up-to-date
					assert( ($book->readToChapterIndex+$add2) == ($res->data->bookInfo->totalChapterNum+$add) );
					$diff[$entry['title']]=$res->data->bookInfo->totalChapterNum+$add - $book->readToChapterIndex;
				}
			}
		}
	}
}
file_put_contents('wn_diff.json', $wn->jsonp_to_json(json_encode($diff)));
var_dump($diff);
