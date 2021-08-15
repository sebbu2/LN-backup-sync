<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('WLNUpdates.php');
require_once('WebNovel.php');

$loggued=false;

if(!defined('DROPBOX_DONE')||!DROPBOX_DONE) include('header.php');

$action=0;
if(array_key_exists('action', $_REQUEST) && (is_int($_REQUEST['action'])||ctype_digit($_REQUEST['action']))) {
	$action=(int)($_REQUEST['action']);
}

$wn=new WebNovel;
$res=$wn->checkLogin();
if($res->code!=0) {
	var_dump($res->code, $res->msg);
	$res=$wn->login( $accounts['WebNovel']['user'], $accounts['WebNovel']['pass']);
	var_dump($res);
}

$files=array();

$res=json_decode(file_get_contents($wn::FOLDER.'_books.json'));
$files['_books.json']=count($res);
$res=json_decode(file_get_contents($wn::FOLDER.'_books2.json'));
$files['_books2.json']=count($res);
$res=json_decode(file_get_contents($wn::FOLDER.'_order.json'));
$files['_order.json']=count($res);

if(!file_exists($wn::FOLDER.'_history.json')) $res2=$wn->get_history();

$res=json_decode(file_get_contents($wn::FOLDER.'_history.json'));
$files['_history.json']=count($res);
$files['ReadingHistoryAjax']=count($res[0]);
$files['get-history']=count($res[1]);

if(!file_exists($wn::FOLDER.'_collection.json')) $res2=$wn->get_collections();

$res=json_decode(file_get_contents($wn::FOLDER.'_collection.json'), true, 512, JSON_OBJECT_AS_ARRAY);
$res3=array_keys($res);
$files['_collection.json']=count($res);

$cols=array();

for($i=0;$i<count($res);$i++) {
	$cols[$i.' : '.$res3[$i]]=count($res[$res3[$i]]);
}

ob_start();
print_table(array($files));
$data=ob_get_clean();
ob_start();
print_table(array($cols));
$data.=ob_get_clean();
file_put_contents('webnovel_data.htm', $data);
echo $data;
unset($data);

if(!defined('DROPBOX_DONE')||!DROPBOX_DONE) include('footer.php');
