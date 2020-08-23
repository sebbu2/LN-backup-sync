<?php
require('config.php');
require('functions.inc.php');
require('wlnupdates.php');
define('DROPBOX', 'C:/Users/sebbu/Dropbox/Apps/Books/.Moon+/Cache/');
define('CWD', getcwd());
chdir(DROPBOX);
$ar=glob('*.po');
chdir(CWD);

$fns=array();
foreach($ar as $fn)
{
	preg_match('#^(.*)_(-?[0-9+])-(\d+)\.epub\.po$#i', $fn, $matches);
	if(!empty($matches)) {
		$fn2=$matches[1];
		$min=$matches[2];
		$max=$matches[3];
	}
	else {
		preg_match('#^(-?[0-9+])-(\d+)_(.*)\.epub\.po$#i', $fn, $matches);
		if(!empty($matches))
		{
			$min=$matches[1];
			$max=$matches[2];
			$fn2=$matches[3];
		}
	}
	$fn2=str_replace(array('_'), ' ', $fn2);
	$data=file_get_contents(DROPBOX.$fn);
	$content=array();
	$content[]=strtok($data, '*@#:%');
	while(($content[]=strtok('*@#:%'))!==FALSE);
	$id=array_search(false, $content, true);
	$content=array_slice($content, 0, $id);
	if( !array_key_exists($fn2, $fns) )
	{
		$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn);
		$ar2=array_merge($ar2, $content);
		$fns[$fn2]=$ar2;
	}
	else if( $fns[$fn2]['max']<=($content[1]+1) )
	{
		var_dump($fns[$fn2]['fn']);//die();
		unlink(DROPBOX.$fns[$fn2]['fn']);//die();
		$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn);
		$ar2=array_merge($ar2, $content);
		$fns[$fn2]=$ar2;
	}
}
require('watches.inc.php');
$wln=new WLNUpdates();
foreach($fns as $name=>$fn)
{
	//var_dump($name);
	$key='';
	$id=-1;
	$found=false;
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
	if(!$found) var_dump($name);
	if($found)
	{
		$fns[$name]['watches']=$watches[$key][$id];
		$chp=(int)$fn[1]; // chapter is already -1 because it starts at 0
		if((int)$fn[3]>0) ++$chp; // if the position isn't at the top of the chapter, assume the chapter is fully read
		if($fn['min']<0) $chp+=$fn['min'];
		if($fn[4]=='100') {
			assert($chp == $fn['max']);
		}
		if($chp>(int)$watches[$key][$id]['chp']) {
			var_dump($name, $chp, $watches[$key][$id]);
			$data=$wln->read_update($watches[$key][$id], $chp);
			var_dump($data);
		}
	}
}
echo '<br/><a href="retr.php">retr</a><br/>'."\r\n";