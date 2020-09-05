<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('wlnupdates.php');
require_once('webnovel.php');
define('DROPBOX', 'C:/Users/sebbu/Dropbox/Apps/Books/.Moon+/Cache/');
define('CWD', getcwd());

chdir(DROPBOX);
$ar=glob('*.po');
chdir(CWD);

$updatedCount=array(
	'wln'=>0,
	'wn'=>0,
);

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
		var_dump($fn);
		continue;
	}
	$fn2=str_replace(array('_'), ' ', $fn2);
	$data=file_get_contents(DROPBOX.$fn);
	$content=array();
	$content[]=strtok($data, '*@#:%');
	while(($content[]=strtok('*@#:%'))!==FALSE);
	unset($data);
	$id=array_search(false, $content, true);
	$content=array_slice($content, 0, $id);
	if( !array_key_exists($fn2, $fns) )
	{
		$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn);
		$ar2=array_merge($ar2, $content);
		$fns[$fn2]=$ar2;
	}
	else if(
		$max>$fns[$fn2]['max'] && //new last chapter is >
		(($content[1]+1+($content[3]>0?1:0)) >= ($fns[$fn2][1]+1+($fns[$fn2][3]>0?1:0))) // position is same or later (no diff between end of chapter and start of new one)
	) {
		var_dump($fns[$fn2]['fn']);//die();
		unlink(DROPBOX.$fns[$fn2]['fn']);//die();
		$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn);
		$ar2=array_merge($ar2, $content);
		$fns[$fn2]=$ar2;
	}
}
require_once('watches.inc.php');
$wln=new WLNUpdates();
$wn=new WebNovel;
$books=json_decode(file_get_contents($wn::FOLDER.'_books.json'));
var_dump(count($books));//die();
foreach($fns as $name=>$fn)
{
	//wlnupdates
	$key='';
	$id=-1;
	$found=false;
	{
		foreach($watches as $_key=>$ar1)
		{
			foreach($ar1 as $_id=>$ar2)
			{
				if(name_compare($name, $ar2['title']))
				{
					$key=$_key;
					$id=$_id;
					$found=true;
					break(2);
				}
			}
		}
		if($found) {
			$fns[$name]['watches']=$watches[$key][$id];
		}
		else {
			// DO NOTHING ! the other file does the add for missing novels
		}
	}
	
	$chp=(int)$fn[1]; // chapter is already -1 because it starts at 0
	if((int)$fn[3]>0) ++$chp; // if the position isn't at the top of the chapter, assume the chapter is fully read
	if($fn['min']<0) $chp+=$fn['min'];
	else if($fn['min']>1) $chp+=$fn['min']-1;
	//var_dump($name,$chp);
	if($fn[4]=='100') {
		assert($chp == $fn['max']);
	}
	
	if($found) {
		if($chp>(int)$watches[$key][$id]['chp']) {
			var_dump($name, $chp, $watches[$key][$id]);
			$data=$wln->read_update($watches[$key][$id], $chp);
			var_dump($data);
			$updatedCount['wln']++;
		}
	}
	else {
		// DO NOTHING
		var_dump($name);
	}
	
	// webnovel
	$key='';
	$id=-1;
	$found=false;
	{
		foreach($books as $key=>$book) {
			if(!is_object($book)) {
				var_dump($key,$book);die();
			}
			
			if(name_compare($name, @$book->bookName))
			{
				$id=$book->bookId;
				$found=true;
				break;
			}
		}
		if($found) {
			// DO NOTHING
			var_dump($name);
		}
		else {
			// TODO
			var_dump($name.' not found in WebNovel.');
		}
	}
	if($found) {
		$res=NULL;
		if(!file_exists($wn::FOLDER.'GetChapterList_'.$id.'.json')) {
			$res=$wn->get_chapter_list($id);
		}
		else {
			$res=json_decode(file_get_contents($wn::FOLDER.'GetChapterList_'.$id.'.json'));
		}
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
			//|| ($chp2==(int)$books[$key]->readToChapterIndex && $books[$key]->updateStatus=='1') // chapter == last chapter read && new chapter released
		) {
			var_dump($name, $chp);
			//$data=$wn->read_update($books[$key], $chp2);
			var_dump($data);
			$updatedCount['wn']++;
		}
		else if ($chp2<(int)$books[$key]->readToChapterIndex) {
			var_dump($name.' found but at higher chapter.', $chp2, $books[$key]->readToChapterIndex);
		}
	}
}
if($updatedCount>0) {
	define('DROPBOX_DONE', true);
	include_once('retr.php');
}
echo '<br/><a href="retr.php">retr</a><br/>'."\r\n";
