<?php
require_once('config.php');

require_once('tables.inc.php');
require_once('SitePlugin.inc.php');
/*
0 to name (simplify)
1 from fn
2 to fn (glob)
3 to fn
*/
function name_simplify($name, $type=0) {
	//'-'
	static $ar1=array('_', ':', ',', '  ');
	//'+'
	static $ar2=array('\'', '&#39;', '\u2019', "\u2019", '’', '´', "\xE2\x80\x99", '&rsquo;', '&lsquo;', '?', '!', '(', ')', 'Retranslated Version', 'Retranslated_Version', 'retranslated version');
	$name1=mb_convert_encoding($name, 'HTML-ENTITIES',  'UTF-8');
	$name=str_replace($ar2, '', $name);
	$name=str_replace($ar1, ' ', $name);
	$name=trim($name);
	if($type==1) {
		$name=str_replace(array('+'), '', $name);
	}
	else if($type==2) {
		$name=str_replace(array('+'), '', $name);
		$name=str_replace(array(' ', '_'), '.*', $name);
	}
	else if($type==3) {
		$name=str_replace(array('+'), '', $name);
		$name=str_replace(array(' ', '_'), '_', $name);
	}
	//$name=strtolower($name);
	return $name;
}
function name_compare($name1, $name2, $type=0)
{
	$name1=name_simplify($name1, $type);
	$name2=name_simplify($name2, $type);
	//var_dump($name1,$name2);
	return (strcasecmp($name1, $name2)==0);
}
function case_count($name)
{
	$name=trim($name);
	$len=strlen($name);
	$res=array( 'low'=>0, 'up'=>0, 'dig'=>0, 'symb'=>0, 'esp'=>0 );
	for($i=0;$i<$len;++$i)
	{
		if($name[$i]>='a' && $name[$i]<='z') $res['low']++;
		else if($name[$i]>='A' && $name[$i]<='Z') $res['up' ]++;
		else if($name[$i]>='0' && $name[$i]<='9') $res['dig']++;
		else if($name[$i]==' ' || $name[$i]=='_') $res['esp']++;
		else $res['symb']++;
	}
	return $res;
}
function startswith($haystack, $needle) {
	return (strncasecmp($haystack, $needle, strlen($needle))==0);
}
function endswith($haystack, $needle) {
	return (strncasecmp(substr($haystack, -strlen($needle)), $needle, strlen($needle))==0);
}
function millitime() {
	list($usec, $sec) = explode(' ', microtime());
	return $sec.substr($usec, 2, 3);
}
function simplexml_load_html($html) {
	libxml_use_internal_errors(true);
	$doc = new DOMDocument();
	$doc->strictErrorChecking = FALSE;
	$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES',  'UTF-8'));
	libxml_use_internal_errors(false);
	$xml = simplexml_import_dom($doc);
	return $xml;
}
