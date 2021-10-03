

<?php
$ar=array('dropbox.php','dropbox2.php','retr.php','dropbox.inc.php','webnovel_news.php','position.php','correspondances.php','update_list.php','webnovel_data.php','wlnupdates.htm','webnovel.htm','royalroad.htm','webnovel_data.htm');
foreach($ar as $f) {
echo '<div class="b block '.(basename($btf['file'])==$f?'green b-green':'blue b-blue').'"><a href="'.$f.'">'.$f.'</a></div>'."\r\n";
}
?>

</body>
</html>
