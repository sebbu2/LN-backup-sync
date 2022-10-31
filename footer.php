

<?php
$ar=array('clean_files.php','dropbox.php','dropbox2.php','retr.php','dropbox.inc.php','news.php','webnovel_news.php','position.php','correspondances.php','list_add_to_main.php','update_list.php','webnovel_data.php','wlnupdates.htm','webnovel.htm','royalroad.htm','webnovel_data.htm');
$bt=debug_backtrace();
if(count($bt)>0) {
	$idx=count($bt)-1;
	$btf=$bt[$idx];
} else {
	$btf=array('file'=>__FILE__);
}
foreach($ar as $f) {
echo '<div class="b block '.(basename($btf['file'])==$f?'green b-green':'blue b-blue').'"><a href="'.$f.'">'.$f.'</a></div>'."\r\n";
}
?>

</body>
</html>
