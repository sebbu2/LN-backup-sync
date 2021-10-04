<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('webnovel.php');
require_once('wlnupdates.php');

$watches=json_decode(str_replace("\t",'',file_get_contents('wlnupdates/watches.json')),TRUE,512,JSON_THROW_ON_ERROR);// important : true as 2nd parameter
$books=json_decode(str_replace("\t",'',file_get_contents('webnovel/_books.json')),false,512,JSON_THROW_ON_ERROR);

$wln=new WLNUpdates;
$wn=new WebNovel;

require('header.php');

//$filter=$wn->get_filter_for('translated');
//$filter=(new ReflectionMethod('SitePlugin', 'get_filter_for'))->invoke($wln, 'comic');// cheat!
/*foreach($books as $book) {
	if($filter($book)) {
		var_dump($book);
		die();
	}
}
die();//*/

//$res=$wn->get_history();//retrieved
//$res=$wn->get_collections();//retrieved
//var_dump($res);
//die();

$updatedCount=array(
	'wln'=>0,
	'wn'=>0,
);

$correspondances=array();
foreach($books as $book) // qidian
{
	foreach($watches['data'][0] as $id=>$list) //wln list
	{
		foreach($list as $entry) // wln
		{
			$entry['title']=(array_key_exists(3,$entry)&&strlen($entry[3])>0)?$entry[3]:$entry[0]['name'];
			if( $book->novelType==0 && name_compare($entry['title'], $book->bookName) ) {
				$correspondances[]=array($book->bookId, $entry[0]['id'], $book->bookName, $id);
			}
		}
	}
}
usort($correspondances, function($e1, $e2) { return strcasecmp($e1[2], $e2[2]);});
//file_put_contents('correspondances.json', json_encode($correspondances, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
//var_dump($correspondances);die();

foreach($correspondances as $ar) {
	list($wn_id, $wln_id, $name, $id)=$ar;
	
	$res=$wn->get_info_cached($wn_id);
	for($i=0;$i<4;++$i) {
		$j=0;
		while(count(get_object_vars($res[$i]))==0 && $j++<5) {
			$res[$i]=$wn->get_info($wn_id, $i);
			//var_dump($res[$i]);
		}
	}
	$resb=$wn->get_chapter_list_cached($wn_id);
	if(!is_object($resb) || !property_exists($resb, 'data')) {
		var_dump($wn_id, $resb);
		//die();
		break;
	}
	//var_dump($resb->data->bookInfo->bookSubName);

	/*
	var_dump($res[0]->Data->Type);//1 translated 2 original
	var_dump($res[2]->data->bookStatisticsInfo->bookType); // 1 translated 2 original
	var_dump($res[3]->data->bookInfo->bookType); // 1 translated 2 original
	var_dump($res[3]->data->bookInfo->type); // 1 translated 2 original
	var_dump($res[3]->data->bookInfo->translateMode);//-1 translated 1 original ???
	//var_dump($res[3]->data->bookInfo->categoryType);//1
	//var_dump($res[0]->Data->AuthorInfo->AuthorName);
	//var_dump($res[3]->data->bookInfo->authorName);
	//*/

	//var_dump($wln->search('New Game+'));

	$res[1]->Data->Gift->Users=NULL;
	$res[1]->Data->BookFans->Users=NULL;
	$res[1]->Data->TopReviewInfos=NULL;
	$res[1]->Data->AlsoLikes=NULL;
	$res[1]->Data->GenreBookItems=NULL;
	//foreach($res[1]->Data->AlsoLikes as &$v) { $v->StatParams=json_decode($v->StatParams); }
	if(property_exists($res[2], 'data') && property_exists($res[2]->data, 'bookReviewInfos')) {
		$res[2]->data->bookReviewInfos=NULL;
	}
	if(property_exists($res[3], 'data') && property_exists($res[3]->data, 'recommendListItems')) {
		$res[3]->data->recommendListItems=NULL;
		//foreach($res[3]->data->recommendListItems as &$v) { $v->alg=json_decode($v->alg); }
	}

	//var_dump($res);
	//$res2=$wln->get_info($wln_id);
	$res2=$wln->get_info_cached($wln_id);
	$res2->data->releases=NULL;
	$res2->data->similar_series=NULL;
	//var_dump($res2);
	//gmdate('c')//iso, GMT/UTC

	$json2=array();
	if(strlen($res2->data->description)==0) {
		$json2[]=array('key'=>'description-container','type'=>'singleitem','value'=>trim($res[0]->Data->Description));
	}
	//$tr=$res[3]->data->bookInfo->translateMode;
	//if( ($res2->data->tl_type=='oel'&&$res[0]->Data->Type>=0) || ($res2->data->tl_type=='translated'&&$res[0]->Data->Type<0) ) { // wrong tl type
	if( ($res2->data->tl_type=='oel'&&$res[3]->data->bookInfo->translateMode>=0) || ($res2->data->tl_type=='translated'&&$res[3]->data->bookInfo->translateMode<0) ) { // wrong tl type
		$json2[]=array('key'=>'tl_type-container','type'=>'combobox','value'=>($res[3]->data->bookInfo->translateMode>=0?'translated':($res[3]->data->bookInfo->translateMode<0?'oel':'error')));
		//if($wln_id==120307) { var_dump($wln_id, $wn_id, $json2, $res[3]->data->bookInfo->translateMode); die(); } // wln: ABL
	}
	if(count($res2->data->authors)==0) {
		$json2[]=array('key'=>'author-container','type'=>'multiitem','value'=>$res[0]->Data->AuthorInfo->AuthorName);
	}
	if(count($res2->data->tags)==0) {
		$ar=call_user_func(function(array $a){ natcasesort($a);return $a;}, array_map(function($x) { return strtolower($x->TagName); }, $res[1]->Data->TagInfos));
		if(count($ar)>0) {
			$json2[]=array('key'=>'tag-container','type'=>'multiitem','value'=>implode("\n",$ar));
		}
	}
	if(count($res2->data->genres)==0) {
		$json2[]=array('key'=>'genre-container','type'=>'multiitem','value'=>$res[0]->Data->CategoryName);
	}
	
	//$res_=json_decode(str_replace("\t",'',file_get_contents('webnovel/GetChapterList_'.$wn_id.'.json')),false,512,JSON_THROW_ON_ERROR);
	$subName='';
	if(property_exists($resb->data->bookInfo, 'bookSubName')) $subName=$resb->data->bookInfo->bookSubName;
	if(strlen($subName)==0) {
		$resc=$wn->get_info_html_cached($wn_id);
		//var_dump($resc);die();
		$subName='';
		if(is_object($resc)) $subName=$resc->bookInfo->bookSubName;
		else if(is_array($resc)) $subName=$resc['bookInfo']['bookSubName'];
		else {
			var_dump($wn_id,$resc);die();
		}
		$subName=str_replace('\ ', ' ', $subName);
		if(strlen($subName)==0) {
			$subName=implode('', array_map(function($s) { return $s[0]; }, explode(' ',$res[0]->Data->BookName)));
		}
		else {
			//$row['subName']=$subName;
		}
		//var_dump($subName);die();
	}
	
	//if(count($res2->data->alternatenames)<=1 || (strlen($resb->data->bookInfo->bookSubName)>0&&array_search($resb->data->bookInfo->bookSubName, $res2->data->alternatenames)==false) ) {
	$names=array_filter(array_unique(array_merge(
		array($res[0]->Data->BookName, $res[1]->Data->OriginalName, $subName),
		$res2->data->alternatenames
	)));
	$names=array_map('trim', $names);
	natcasesort($names);
	if(count(array_diff($names, $res2->data->alternatenames))>0) {
		//var_dump($wln_id, $wn_id, $names,$res2->data->alternatenames);die();
	}
	//if(count($res2->data->alternatenames)<=1 || (strlen($subName)>0&&array_search($subName, $res2->data->alternatenames)==false) ) {
	if(count(array_diff($names, $res2->data->alternatenames))>0) {
		$ar=$names;
		$ar2=array();
		foreach($ar as $k=>$v) {
			$_res=case_count($v);
			$_res=min($_res['low'], $_res['up']) + $_res['dig'] + $_res['symb'];
			$_res2=0;
			if(!array_key_exists(strtolower($v), $ar2)) $ar2[strtolower($v)]=$k;
			else {
				$_res2=case_count($ar[$ar2[strtolower($v)]]);
				$_res2=min($_res2['low'], $_res2['up']) + $_res2['dig'] + $_res2['symb'];
				if($_res>$_res2) $ar2[strtolower($v)]=$k;
			}
		}
		$ar2=array_map(function($v) use($ar) { return $ar[$v]; }, $ar2);
		$ar2=array_values($ar2);
		natcasesort($ar2);
		if(count(array_diff(array_unique(array_merge($res2->data->alternatenames, $ar2)), $res2->data->alternatenames))>0) {
			//var_dump($res2->data->alternatenames, $ar2);die();
			$json2[]=array('key'=>'altnames-container','type'=>'multiitem','value'=>implode("\n",$ar2),);
		}
	}
	if(strlen($res2->data->website)==0) {
	//if(true) {
		$url='https://www.webnovel.com/book/'.$wn_id;
		$data=$wn->get($url, NULL, NULL, NULL);
		foreach($wn->headersRecv as $hdr) {
			if(substr($hdr, 0, 10)=='Location: ') {
				$url='https://www.webnovel.com'.substr($hdr, 10);
			}
		}
		//var_dump($url);
		if(strlen($res2->data->website)==0 || ($res2->data->website!=$url && strpos($res2->data->website, "\n")===false) ) {
			//var_dump($res2->data->website, $url);die();
			$json2[]=array('key'=>'website-container','type'=>'singleitem','value'=>$url);
		}
		if(strpos($res2->data->website, "\n")!==false) {
			$found=false;
			$urls=array();
			$ar=explode("\n", $res2->data->website);
			foreach($ar as $v) {
				if($v==$url) {
					$found=true;
				}
				else if(strpos($v, 'www.webnovel.com')===false) {
					$urls[]=$v;
				}
			}
			if(!$found) {
				$urls=array_merge(array($url), $urls);
				$json2[]=array('key'=>'website-container','type'=>'singleitem','value'=>implode("\n",$urls));
			}
		}
	}
	
	if($id=='QIDIAN' && $res2->data->tl_type=='oel') {
		$res3=$wln->add_novel($wln_id, 'QIDIAN original');
		var_dump($res3);
	}
	if($id=='QIDIAN original' && $res2->data->tl_type=='translated') {
		$res3=$wln->add_novel($wln_id, 'QIDIAN');
		var_dump($res3);
	}
	
	if(count($json2)==0) continue;
	//var_dump($json2);
	/*foreach($json2 as $i=>$e) {
		$json2[$i]=(Object)$e;
	}//*/
	//var_dump($json2);//die();
	$json=array('mode'=>'series-update', 'item-id'=>$wln_id, 'entries'=>$json2);
	var_dump($json);//continue;
	//var_dump(json_encode($json2, JSON_UNESCAPED_SLASHES));
	$res=$wln->edit($json);
	var_dump($res);
	if($res!==false) $res2=$wln->get_info($wln_id);
	$updatedCount['wln']++;
	/*
	"entries": [{
		"key": "watch-container",
		"type": "combobox",
		"value": "QIDIAN completed"
	}]
	*/
	//die('job is already done, edit file to do something else.');
	if(ob_get_level()>0) { ob_end_flush(); ob_flush(); }
	flush();
	//if($res!==false) die();
}
if($updatedCount['wln']>0 || $updatedCount['wn']>0) {
	define('DROPBOX_DONE', true);
	include_once('retr.php');
}
require('footer.php');
