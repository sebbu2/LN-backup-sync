<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('wlnupdates.php');
require_once('webnovel.php');

if(!defined('MOONREADER_DID')) define('MOONREADER_DID', '1454083831785');
if(!defined('MOONREADER_DID2')) define('MOONREADER_DID2', '9999999999999');

include('header.php');

chdir(DROPBOX);
$ar=glob('*.po');
natcasesort($ar);

chdir(CWD);

$updatedCount=array(
	'wln'=>0,
	'wn'=>0,
);

$fns=array();//from reader
$fns_=array();//from here
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
		//var_dump($fn);
		echo '<div class="block b b-red">',$fn,'</div>',"\n";
		continue;
	}
	/*$fn2=str_replace(array('_'), ' ', $fn2);
	$fn2=str_replace(array('Retranslated Version'), '', $fn2);
	$fn2=trim($fn2);//*/
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
			$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2);
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
				$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2);
				$ar2=array_merge($ar2, $content);
				$fns[$fn3]=$ar2;
			}
			//3a
			else if($max1>$max && $lastchp>=$chp) {
				unlink(DROPBOX.$fn);
				echo '<div class="block b b-blue">',$fn,'</div>',"\n";
			}
		}
	}
	else if($content[0]===MOONREADER_DID2) {
		//1b
		if( !array_key_exists($fn3, $fns_) ) {
			$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2);
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
				$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2);
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
	/*if( !array_key_exists($fn3, $fns) )
	{
		$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2);
		$ar2=array_merge($ar2, $content);
		$fns[$fn3]=$ar2;
	}
	else if(
		$max>$fns[$fn3]['max'] && //new last chapter is >
		(($min+($min<0?1:0)+$content[1]+($content[3]>0?1:0)) >= ($fns[$fn3]['min']+($fns[$fn3]['min']<0?1:0)+$fns[$fn3][1]+($fns[$fn3][3]>0?1:0))) // position is same or later (no diff between end of chapter and start of new one)
	) {
		//var_dump($fns[$fn3]['fn']);//die();
		if($content[0]!=MOONREADER_DID2) {
			unlink(DROPBOX.$fns[$fn3]['fn']);//die();
			echo '<div class="block b b-blue">',$fns[$fn3]['fn'],'</div>',"\n";
		}
		else {
			echo '<div class="block b b-green">',$fns[$fn3]['fn'],'</div>',"\n";
		}
		$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2);
		$ar2=array_merge($ar2, $content);
		$fns[$fn3]=$ar2;
	}
	else if(
		$max < $fns[$fn3]['max'] && //new last chapter is >
		(($min+($min<0?1:0)+$content[1]+($content[3]>0?1:0)) <= ($fns[$fn3]['min']+($fns[$fn3]['min']<0?1:0)+$fns[$fn3][1]+($fns[$fn3][3]>0?1:0))) // position is same or later (no diff between end of chapter and start of new one)
	) {
		//var_dump($fn);//die();
		if($fns[$fn3][0]!=MOONREADER_DID2) {
			unlink(DROPBOX.$fn);//die();
			echo '<div class="block b b-blue">',$fn,'</div>',"\n";
		}
		else {
			echo '<div class="block b b-green">',$fn,'</div>',"\n";
		}
	}//*/
}
//var_dump(count($fns));
//require_once('watches.inc.php');
$wln=new WLNUpdates();
$wn=new WebNovel;
$watches=json_decode(file_get_contents($wln::FOLDER.'_books.json'));
//var_dump(count($watches));//die();
$books=json_decode(file_get_contents($wn::FOLDER.'_books.json'));
//var_dump(count($books));//die();

echo '<h2>Counts</h2>',"\n";
print_table(array(array( 'files'=>count(array_merge($fns_,$fns)), 'WLNUpdates'=>count($watches), 'WebNovel'=>count($books) )));
echo '<br/>'."\r\n";
if(ob_get_level()>0) { ob_end_flush(); ob_flush(); }
flush();

$head=array('name', 'WLNUpdates old chp', 'WLNUpdates new chp', 'WLNUpdates sync', 'WebNovel old chp', 'WebNovel new chp', 'WebNovel sync', 'msg');
$lines=0;

$res=$wn->checkLogin();
if($res->code!=0) {
	$res=$wn->login( $accounts['WebNovel']['user'], $accounts['WebNovel']['pass']);
}

foreach($fns as $name=>$fn)
{
	$row=array();
	//wlnupdates
	$key='';
	$id=-1;
	$found1=false;
	{
		/*foreach($watches as $_key=>$ar1)
		{
			foreach($ar1 as $_id=>$ar2)
			{
				if(name_compare($name, $ar2['title']))
				{
					$key=$_key;
					$id=$_id;
					$found1=true;
					break(2);
				}
			}
		}//*/
		foreach($watches as $_key=>$book)
		{
			if($book[3]!==NULL && name_compare($name, $book[3], 1))
			{
				$key=$_key;
				$id=$book[0]->id;
				$found1=true;
				break;
			}
		}
		if(!$found1) {
			foreach($watches as $_key=>$book)
			{
				if(name_compare($name, $book[0]->name, 1))
				{
					$key=$_key;
					$id=$book[0]->id;
					$found1=true;
					break;
				}
			}
		}
		if($found1) {
			$fns[$name]['watches']=$watches[$key];
		}
		else {
			// DO NOTHING ! the other file does the add for missing novels
			//var_dump($name.' not found in WLNUpdates.');
			$row['msg']=array_merge((array_key_exists('msg',$row)?$row['msg']:array()),array('not found in WLNUpdates'));
		}
	}
	
	$chp=(int)$fn[1]; // chapter is already -1 because it starts at 0
	if($fn['min']<0) $chp+=$fn['min'];
	else if($fn['min']>1) $chp+=$fn['min']-1;
	if($chp===0) $chp=1;
	if((int)$fn[3]>0) ++$chp; // if the position isn't at the top of the chapter, assume the chapter is fully read
	//var_dump($name,$chp);
	if($fn[4]=='100') {
		if( !($chp == $fn['max'])) {
			// NOTE : old .po
			// NOTE : 100 rounded up :(
			//var_dump($chp,$fn);die();
		}
	}
	
	if( ($fn[1]=='0' && $fn[3]=='0') || $chp==1) continue;
	
	if($found1) {
		//if($chp>(int)$watches[$key]['chp']) {
		if($chp>(int)$watches[$key][1]->chp) {
			//var_dump($name, $chp, $watches[$key][1]->chp);
			$row['WLNUpdates old chp']=$watches[$key][1]->chp;
			$row['WLNUpdates new chp']=$chp;
			$data=$wln->read_update($watches[$key], $chp);
			//$data=json_decode($data);
			//var_dump($data);
			if($data->error===false && $data->message==='Succeeded') $row['WLNUpdates sync']='true';
			else $row['WLNUpdates sync']='false';
			$updatedCount['wln']++;
		}
	}
	else {
		// TODO
	}
	
	// webnovel
	$key='';
	$id=-1;
	$found2=false;
	{
		foreach($books as $key=>$book) {
			if(!is_object($book)) {
				var_dump($key,$book);die();
			}
			$name2='';
			if(property_exists($book, 'bookName')) $name2=$book->bookName;
			else {
				$res=$wn->get_info_cached($book->bookId);
				$retr=false;
				if($res[0]->Result!==0 || $res[1]->Result!==0 || $res[2]->code!==0 || $res[3]->code!==0) $res=$wn->get_info($book->bookId);
				if(!is_array($res)) { var_dump($book, res); die(); }
				if(!is_object($res[0])) { var_dump($book, $res); die(); }
				if(!property_exists($res[0], 'Data')) { var_dump($book, $res); die(); }
				if(!is_object($res[0]->Data)) { var_dump($book, $res, $res[0]->Result, $res[1]->Result, $res[2]->code, $res[3]->code); die(); }
				if(!property_exists($res[0]->Data, 'BookName')) { var_dump($book, $res); die(); }
				$name2=$res[0]->Data->BookName;
			}
			if($book->novelType==0 && name_compare($name, $name2, 1))
			{
				$id=$book->bookId;
				$found2=true;
				break;
			}
		}
		if($found2) {
			// DO NOTHING
		}
		else {
			// TODO
			//var_dump($name.' not found in WebNovel.');
			$row['msg']=array_merge((array_key_exists('msg',$row)?$row['msg']:array()),array('not found in WebNovel'));
		}
	}
	if($found2) {
		$res=NULL;
		if(!file_exists($wn::FOLDER.'GetChapterList_'.$id.'.json')) {
			$res=$wn->get_chapter_list($id);
		}
		else {
			$res=json_decode(file_get_contents($wn::FOLDER.'GetChapterList_'.$id.'.json'), false, 512, JSON_THROW_ON_ERROR);
			if(!property_exists($res,'data')) {
				unlink($wn::FOLDER.'GetChapterList_'.$id.'.json');
				$res=$wn->get_chapter_list($id);
			}
		}
		if(!property_exists($res,'data') || !property_exists($res->data, 'volumeItems')) { var_dump($res); die(); }
		//if($res->data->volumeItems[0]->index==0) $chp2=$chp-$res->data->volumeItems[0]->chapterItems[0]->index; // fix for negative chapters
		//if($res->data->volumeItems[0]->index==0) $chp2=$chp+$res->data->volumeItems[0]->chapterCount; // fix for negative chapters
		//else $chp2=$chp;
		$chp2=$chp;
		$priv_only=0;
		$max_pub=0;
		foreach($res->data->volumeItems as $vol) {
			foreach($vol->chapterItems as $chap) {
				if($chap->chapterLevel!=0) $priv_only++;
				else if($chap->index>$max_pub) $max_pub=$chap->index;
			}
		}
		if(
			($chp2 > ((int)$books[$key]->readToChapterIndex+$priv_only)) // chapter > last chapter read + number of chapter privilege only
			|| ($chp2 > (int)$books[$key]->readToChapterIndex && $chp2<=$max_pub) // chapter > last chapter read && chapter is public
			|| ($chp2==(int)$books[$key]->readToChapterIndex && $books[$key]->updateStatus=='1') // chapter == last chapter read && new chapter released
		) {
			//var_dump($name, $chp);
			$row['WebNovel old chp']=(int)$books[$key]->readToChapterIndex;
			$row['WebNovel new chp']=($chp2<=$max_pub)?$chp2:$max_pub;
			$data=$wn->read_update($books[$key], $chp2);
			//var_dump($data);
			if(!property_exists($data, 'code') || !property_exists($data, 'msg')) { var_dump($data);die(); }
			if($data->code===0 && $data->msg==='Success') $row['WebNovel sync']='true';
			else $row['WebNovel sync']='false';
			$updatedCount['wn']++;
		}
		else if ($chp2<(int)$books[$key]->readToChapterIndex) {
			//var_dump($name.' found in WebNovel but at higher chapter.', $chp2, $books[$key]->readToChapterIndex, $fns[$name]);
			$row['msg']=array_merge((array_key_exists('msg',$row)?$row['msg']:array()),array('found in WebNovel at '.$books[$key]->readToChapterIndex.' instead of '.$chp2));
		}
	}
	if(count($row)>0) $row=array_merge(array('name'=>$name),$row);
	$row=array_merge(array_diff_key($row,array('msg'=>'')),(array_key_exists('msg',$row)?array('msg'=>$row['msg']):array()));
	if(array_key_exists('msg',$row)) $row['msg']=implode(' + ',$row['msg']);
	if(count($row)>0) {
		if($lines==0) {
			echo '<table border="1">'."\r\n";
			print_thead_v($head);
		}
		//print_table(array($row));
		print_tbody($row, $head);
		++$lines;
	}
	if(ob_get_level()>0) { ob_end_flush(); ob_flush(); }
	flush();
}
if($lines>0) {
	echo '</table>'."\r\n";
}
if($updatedCount['wln']>0 || $updatedCount['wn']>0) {
	define('DROPBOX_DONE', true);
	include_once('retr.php');
}//*/
//echo '<br/><a href="retr.php">retr</a><br/>'."\r\n";

include('footer.php');
