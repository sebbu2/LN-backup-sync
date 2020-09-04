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

	if(!$loggued) {
		$res=$wln->login( $accounts['WLNUpdates']['user'], $accounts['WLNUpdates']['pass'] );
		assert($res==true) or die('you need to log in.');
	}

	$res = $wln->watches();
	if(in_array($res, $wln_errors)) $res = false;
	if($res===false) {
		$res = $wln->watches2();
		$watches = $wln->watches2_lists($res);
		file_put_contents('watches.inc.php', '<?php'."\r\n".'$watches = '.var_export($watches, true).';' );
	}
	else {
		var_dump('NEW WATCHES !', $res);
		die();
	}
	$count=0;foreach($watches as $list) $count+=count($list);
	echo 'wlnupdates=';var_dump($count);

	if(!defined('DROPBOX_DONE')) echo '<br/><a href="dropbox.php">dropbox</a><br/>'."\r\n";

	ob_start();
	foreach($watches as $id=>$list)
	{
		echo '<h4>'.$id.'</h4>'."\r\n";
		print_table($list);
	}
	$data=ob_get_clean();
	file_put_contents('watches2.htm', $data);
	if(!defined('DROPBOX_DONE')) echo $data;
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
	if(!defined('DROPBOX_DONE')) echo $data;
}
