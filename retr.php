<?php
require('config.php');
require('WLNUpdates.php');
require('functions.inc.php');
$wln=new WLNUpdates();
$res=$wln->login( $accounts['WLNUpdates']['user'], $accounts['WLNUpdates']['pass'] );
assert($res==true) or die('you need to log in.');
$res = $wln->watches();
if($res==='{"error":true,"message":"Not yet implemented","reload":true}'."\n") $res = false;
if($res===false) {
	$res = $wln->watches2();
	$watches = $wln->watches2_lists($res);
	file_put_contents('watches.inc.php', '<?php'."\r\n".'$watches = '.var_export($watches, true).';' );
}
ob_start();
foreach($watches as $id=>$list)
{
	echo '<h4>'.$id.'</h4>'."\r\n";
	print_table($list);
}
$data=ob_get_flush();
file_put_contents('watches2.htm', $data);
