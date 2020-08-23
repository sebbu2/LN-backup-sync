<?php
function print_table($ar)
{
	echo '<table border="1">'."\r\n";
	echo "\t".'<tr>'."\r\n";
	foreach(array_keys($ar[0]) as $k2=>$v2) {
		echo "\t\t".'<th>'.$v2.'</th>'."\r\n";
	}
	echo "\t".'</tr>'."\r\n";
	foreach($ar as $k=>$v)
	{
		echo "\t".'<tr>'."\r\n";
		foreach($v as $k2=>$v2)
		{
			echo "\t\t".'<td>'.$v2.'</td>'."\r\n";
		}
		echo "\t".'</tr>'."\r\n";
	}
	echo '</table>'."\r\n";
}
function name_compare($name1, $name2)
{
	static $ar1=array('_','-', ':', ',', '  ');
	static $ar2=array('\'', '&#39;', '?', '!', '(', ')', 'Retranslated Version');
	$name1=str_replace($ar1, ' ', $name1);
	$name1=str_replace($ar2, '', $name1);
	$name2=str_replace($ar1, ' ', $name2);
	$name2=str_replace($ar2, '', $name2);
	$name1=trim($name1);
	$name2=trim($name2);
	$name1=strtolower($name1);
	$name2=strtolower($name2);
	return (strcasecmp($name1, $name2)==0);
}