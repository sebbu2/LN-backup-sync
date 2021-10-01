<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('wlnupdates.php');
require_once('webnovel.php');
require_once('royalroad.php');

$loggued=false;

if(!defined('DROPBOX_DONE')||!DROPBOX_DONE) include('header.php');

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
	//if(in_array($res, $wln_errors)) $loggued = true;
	//$res=json_decode($res, false, 512, JSON_THROW_ON_ERROR);

	/*if( $res->error==true ) {
		$res=$wln->login( $accounts['WLNUpdates']['user'], $accounts['WLNUpdates']['pass'] );
		if($res===false) die('you need to log in.');
		//$res=json_decode($res);
		if(is_object($res) && $res->error==true) die($res->message);
		$res = $wln->watches();
	}

	//if(in_array($res, $wln_errors)) $res = false;
	if($res===false || $res->error==true) {
		$res = $wln->watches2();
		$res = $wln->watches2_lists($res);
		file_put_contents('watches.inc.php', '<?php'."\r\n".'$watches = '.var_export($res, true).';' );
	}
	else {
		//$res=json_decode($res);
		//var_dump($res);
		$res=$res->data[0];
		$res2=array();
		foreach($res as $w) { $res2=array_merge($res2, $w); };
		file_put_contents($wln::FOLDER.'_books.json',$wln->jsonp_to_json(json_encode($res2)));
		//var_dump('NEW WATCHES !', $res);
		//die();
	}//*/
	
	//$count=0;foreach($res as $list) $count+=count($list);
	echo 'wlnupdates=';var_dump(count($res));
	$lists=json_decode(file_get_contents($wln::FOLDER.'_order.json'), false, 512, JSON_THROW_ON_ERROR);

	ob_start();
	//foreach($res as $list=>$ar)
	foreach($lists as $list=>$ar)
	{
		echo '<h4>'.$list.'</h4>'."\r\n";
		//print_table($ar);//die();
		echo '<table border="1">'."\r\n";
		//keys
		print_thead_k($res[$ar[0]]);
		
		//values
		foreach($ar as $k=>$v)
		{
			print_tbody($res[$v]);
		}
		echo '</table>'."\r\n";
	}
	$data=ob_get_clean();
	file_put_contents('watches2.htm', $data);
	//if(!defined('DROPBOX_DONE')) echo $data;
	//echo '<a href="watches2.htm">wlnupdates</a><br/>'."\r\n";
}

$loggued=false;

{
	$wn=new WebNovel;
	$res=$wn->checkLogin();
	if($res->code!=0) {
		var_dump($res->code, $res->msg);
		$res=$wn->login( $accounts['WebNovel']['user'], $accounts['WebNovel']['pass'] );
		var_dump($res);
	}
	$res=$wn->watches();
	//$res=json_decode($res);
	echo 'webnovel= ';var_dump(count($res));
	
	ob_start();
	print_table($res);
	$data=ob_get_clean();
	file_put_contents('library.htm', $data);
	unset($data);
	//if(!defined('DROPBOX_DONE')) echo $data;
	//echo '<a href="library.htm">webnovel</a><br/>'."\r\n";

	//$res=json_decode(file_get_contents($wn::FOLDER.'_books.json'), false, 512, JSON_THROW_ON_ERROR);
	$order=json_decode(file_get_contents($wn::FOLDER.'_order.json'), false, 512, JSON_THROW_ON_ERROR);
	$res3=array();
	foreach($order as $i=>$e) {
		$res2[count($res)-1-$e[3]]=$res[$i];
	}
	ksort($res2);
	ob_start();
	print_table($res2);
	$data=ob_get_clean();
	file_put_contents('library2.htm', $data);
	unset($data);
}

{
	$rr = new RoyalRoad;
	$res=$rr->checkLogin();
	if($res!==1) {
		$res=$rr->login( $accounts['RoyalRoad']['user'], $accounts['RoyalRoad']['pass'] );
	}
	$res = $rr->watches();
	
	echo 'royalroad= ';var_dump(count($res));
	
	ob_start();
	print_table($res);
	$data=ob_get_clean();
	file_put_contents('royalroad.htm', $data);
	unset($data);
}
//if(!defined('DROPBOX_DONE')) echo '<br/><a href="dropbox.php">dropbox</a><br/>'."\r\n";

if(!defined('DROPBOX_DONE')||!DROPBOX_DONE) include('footer.php');
