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