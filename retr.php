<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('WLNUpdates.php');
require_once('WebNovel.php');

$loggued=false;

{
	$wln_errors=array(
		'{"error":true,"message":"Not yet implemented","reload":true}'."\n",
		'{"error":true,"message":"Not yet implemented","reload":true}',
		'{'."\n    ".'"error": true,'."\n    ".'"message": "Not yet implemented",'."\n    ".'"reload": true'."\n".'}'."\n",
		'{'."\n    ".'"error": true,'."\n    ".'"message": "Not yet implemented",'."\n    ".'"reload": true'."\n".'}',
		'{'."\n  ".'"error": true,'."\n  ".'"message": "Not yet implemented",'."\n  ".'"reload": true'."\n".'}'."\n",
		'{'."\n  ".'"error": true,'."\n  ".'"message": "Not yet implemented",'."\n  ".'"reload": true'."\n".'}',
		'{'."\n\t".'"error": true,'."\n\t".'"message": "Not yet implemented",'."\n\t".'"reload": true'."\n".'}'."\n",
		'{'."\n\t".'"error": true,'."\n\t".'"message": "Not yet implemented",'."\n\t".'"reload": true'."\n".'}',
	);
	$wln=new WLNUpdates();
	$res=$wln->watches();
	if(in_array($res, $wln_errors)) $loggued = true;
	$watches=json_decode($res, false, 512, JSON_THROW_ON_ERROR);

	if(!$loggued && $watches->error==true) {
		$res=$wln->login( $accounts['WLNUpdates']['user'], $accounts['WLNUpdates']['pass'] );
		if($res===false) die('you need to log in.');
		$res=json_decode($res);
		if(is_object($res) && $res->error==true) die($res->message);
		$res = $wln->watches();
	}

	if(in_array($res, $wln_errors)) $res = false;
	if($res===false) {
		$res = $wln->watches2();
		$watches = $wln->watches2_lists($res);
		file_put_contents('watches.inc.php', '<?php'."\r\n".'$watches = '.var_export($watches, true).';' );
	}
	else {
		//$res=json_decode($res);
		//var_dump($res);
		$watches=$watches->data[0];
		$watches2=array();
		foreach($watches as $w) { $watches2=array_merge($watches2, $w); };
		file_put_contents($wln::FOLDER.'_books.json',$wln->jsonp_to_json(json_encode($watches2)));
		//var_dump('NEW WATCHES !', $res);
		//die();
	}
	$count=0;foreach($watches as $list) $count+=count($list);
	echo 'wlnupdates=';var_dump($count);

	ob_start();
	foreach($watches as $id=>$list)
	{
		echo '<h4>'.$id.'</h4>'."\r\n";
		print_table($list);//die();
	}
	$data=ob_get_clean();
	file_put_contents('watches2.htm', $data);
	//if(!defined('DROPBOX_DONE')) echo $data;
	echo '<a href="watches2.htm">wlnupdates</a><br/>'."\r\n";
}

$loggued=false;

{
	$wn=new WebNovel;
	$res=$wn->checkLogin();
	if($res->code!=0) {
		var_dump($res->code, $res->msg);
		$res=$wn->login( $accounts['WebNovel']['user'], $accounts['WebNovel']['pass']);
		var_dump($res);
	}
	$res=$wn->watches();
	$res=json_decode($res);
	echo 'webnovel= ';var_dump(count($res));
	
	ob_start();
	print_table($res);
	$data=ob_get_clean();
	file_put_contents('library.htm', $data);
	//if(!defined('DROPBOX_DONE')) echo $data;
	echo '<a href="library.htm">webnovel</a><br/>'."\r\n";
}
if(!defined('DROPBOX_DONE')) echo '<br/><a href="dropbox.php">dropbox</a><br/>'."\r\n";
