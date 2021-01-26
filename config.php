<?php
define('COOKIEFILE', '.cookies.txt');
define('DEBUG_URL', false);
define('DEBUG_HTTP', false);
define('DEBUG_COOKIE', false);
define('DEBUG_POSTDATA', false);
define('DROPBOX', 'C:/Users/sebbu/Dropbox/Apps/Books/.Moon+/Cache/');
define('CWD', getcwd());
$accounts=array(
	'WLNUpdates'=>array(
		'user'=>'',
		'pass'=>'',
	),
	'WebNovel'=>array(
		'user'=>'',
		'pass'=>'',
	),
	'NovelUpdates'=>array(
		'user'=>'',
		'pass'=>'',
	),
	'GoodReads'=>array(
		'user'=>'',
		'pass'=>'',
	),
	'LibraryThing'=>array(
		'user'=>'',
		'pass'=>'',
	),
	'dropbox'=>array(
		'user'=>'',
		'pass'=>'',
	),
	'gdrive'=>array(
		'user'=>'',
		'pass'=>'',
	),
);
if(!isset($key)) $key='';
if(!isset($secret)) $secret='';
define('MOONREADER_DID', '1454083831785');
define('MOONREADER_DID2', '9999999999999');
?>
