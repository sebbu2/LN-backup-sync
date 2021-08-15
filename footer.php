

<?php
$ar=array('dropbox.php','dropbox2.php','retr.php','dropbox.inc.php','webnovel_news.php','update_list.php','webnovel_data.php','watches2.htm','library.htm','webnovel_data.htm');
foreach($ar as $f) {
echo '<div class="b block '.(basename($btf['file'])==$f?'green b-green':'blue b-blue').'"><a href="'.$f.'">'.$f.'</a></div>'."\r\n";
}
?>

</body>
</html>
