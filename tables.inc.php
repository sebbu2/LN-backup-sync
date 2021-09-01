<?php
require_once('functions.inc.php');

function print_thead_key($key,$value,$prefix='') {
	if(is_string($value)||is_numeric($value)||is_bool($value)||is_null($value)) {
		if($prefix===0 && $key==='extra_metadata') {
			echo "\t\t".'<th>0->extra_metadata->is_yaoi</th>'."\r\n";
			echo "\t\t".'<th>0->extra_metadata->is_yuri</th>'."\r\n";
		}
		else echo "\t\t".'<th>'.(strlen($prefix)>0?$prefix.'->':'').$key.'</th>'."\r\n";
	}
	else if(is_array($value)) {
		foreach($value as $k2=>$v2)
		{
			print_thead_key($k2,$v2,(strlen($prefix>0)?$prefix.'->':'').$key);
		}
	}
	else if(is_object($value)) {
		foreach(get_object_vars($value) as $k2=>$v2)
		{
			print_thead_key($k2,$v2,(strlen($prefix)>0?$prefix.'->':'').$key);
		}
	}
	else {
		var_dump($key,$value,$prefix);die();
	}
}
function print_thead_value($key,$value,$prefix='') {
	if(is_string($value)||is_numeric($value)||is_bool($value)||is_null($value)) {
		echo "\t\t".'<th>'.$value.'</th>'."\r\n";
	}
	else if(is_array($value)) {
		foreach($value as $k2=>$v2)
		{
			print_thead_key($k2,$v2,(strlen($prefix>0)?$prefix.'->':'').$key);
		}
	}
	else if(is_object($value)) {
		foreach(get_object_vars($value) as $k2=>$v2)
		{
			print_thead_key($k2,$v2,(strlen($prefix)>0?$prefix.'->':'').$key);
		}
	}
	else {
		var_dump($key,$value,$prefix);die();
	}
}
function print_tbody_value($key,$value,$prefix='') {
	//var_dump($value, is_string($value), is_numeric($value), is_bool($value), is_null($value), is_array($value), is_object($value));
	if(is_string($value)||is_numeric($value)||is_bool($value)||is_null($value)) {
		if($prefix===0 && $key==='extra_metadata') {
			echo "\t\t".'<td></td>'."\r\n";
			echo "\t\t".'<td></td>'."\r\n";
		}
		else echo "\t\t".'<td>'.strval($value).'</td>'."\r\n";
	}
	else if(is_array($value)) {
		foreach($value as $k2=>$v2)
		{
			print_tbody_value($k2,$v2,(strlen($prefix>0)?$prefix.'->':'').$key);
		}
	}
	else if(is_object($value)) {
		foreach(get_object_vars($value) as $k2=>$v2)
		{
			print_tbody_value($k2,$v2,(strlen($prefix>0)?$prefix.'->':'').$key);
		}
	}
	else {
		var_dump($value);die();
	}
}
function print_thead_k($ar) {
	$keys=NULL;
	//if(is_array($ar)) $keys=array_keys($ar);
	//else if( is_object($ar) && (get_class($ar)=='stdClass') ) $keys=array_keys(get_object_vars($ar));
	if(is_array($ar)) $keys=$ar;
	else if( is_object($ar) && (get_class($ar)=='stdClass') ) $keys=get_object_vars($ar);
	
	//keys
	echo "\t".'<tr>'."\r\n";
	foreach($keys as $k2=>$v2) {
		print_thead_key($k2,$v2);
	}
	echo "\t".'</tr>'."\r\n";
}
function print_thead_v($ar) {
	$keys=NULL;
	//if(is_array($ar)) $keys=array_keys($ar);
	//else if( is_object($ar) && (get_class($ar)=='stdClass') ) $keys=array_keys(get_object_vars($ar));
	if(is_array($ar)) $keys=$ar;
	else if( is_object($ar) && (get_class($ar)=='stdClass') ) $keys=get_object_vars($ar);
	
	//keys
	echo "\t".'<tr>'."\r\n";
	foreach($keys as $k2=>$v2) {
		print_thead_value($k2,$v2);
	}
	echo "\t".'</tr>'."\r\n";
}
function print_tbody($ar,$keys=NULL) {
	if($keys==NULL) {
		if(is_array($ar)) {
			$values=$ar;
			$keys=array_keys($ar);
		}
		else if( is_object($ar) && (get_class($ar)=='stdClass') ) {
			$values=get_object_vars($ar);
			$keys=array_keys($values);
		}
	}
	else {
		$values=$ar;
	}
	assert(count(array_diff_key($values,array_combine($keys,range(1,count($keys)))))==0) or die('wrong keys.');
	
	//values
	echo "\t".'<tr>'."\r\n";
	foreach($keys as $k)
	{
		if(array_key_exists($k, $values)) $v=$values[$k]; else $v='';
		print_tbody_value($k,$v);
	}
	echo "\t".'</tr>'."\r\n";
}
function print_table($ar)
{
	if(!is_array($ar)) return;
	if(count($ar)==0) return;
	
	echo '<table border="1">'."\r\n";
	
	reset($ar);
	$k1=key($ar);
	
	//keys
	print_thead_k($ar[$k1]);
	
	//values
	foreach($ar as $k=>$v)
	{
		print_tbody($v);
	}
	
	echo '</table>'."\r\n";
}
?>