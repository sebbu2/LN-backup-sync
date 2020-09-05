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
			// DO NOTHING
		}
	}
	if($found) {
		// DO NOTHING
	}
	else {
		// THIS FILES search the missing novels to add them
		var_dump($name);
		//continue;
		$res=$wln->search($name);
		var_dump($res);
		$res=json_decode($res);
		//var_dump($res);
		$found=false;
		$sid=-1;
		$accuracy=0;
		foreach($res->data->results as $m1) {
			if(count($m1->match)>1) {
				foreach($m1->match as $m2) {
					if($m2[0]>=0.9) {
						$found=true;
						var_dump($m2[1]);
						$aid=$m1->sid;
						$accuracy=$m2[0];
					}
				}
			}
			else {
				if($m1->match[0][0]>=0.9) {
					$found=true;
					var_dump($m1->match[0][1]);
					$sid=$m1->sid;
					$accuracy=$m1->match[0][0];
				}
			}
			if($found) break;
		}
		if($found) {
			var_dump($sid, $accuracy);
		}
		else {
			var_dump($res->data->results[0]->match[0]);
			var_dump('not found');
			$res=$wn->checkLogin();
			var_dump($res);
			if($res->code!=0) {
				var_dump($res->code, $res->msg);
				//$res=$wn->login( $accounts['WebNovel']['user'], $accounts['WebNovel']['pass']);
				//var_dump($res);
			}
			//$res=$wn->search($name);
			//var_dump($res);
			$res=$wn->search2($name);
			var_dump($res);
		}
		die();
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
		// DO NOTHINGs
	}
}
if($updatedCount>0) {
	define('DROPBOX_DONE', true);
	include_once('retr.php');
}
echo '<br/><a href="retr.php">retr</a><br/>'."\r\n";
