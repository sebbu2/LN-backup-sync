<?php
require_once('config.php');

require_once('tables.inc.php');
require_once('SitePlugin.inc.php');
/*
0 to name (simplify)
1 from fn
2 to fn (regex)
3 to fn
4 to fn (glob)
*/
function name_simplify($name, $type=0) {
	//'-'
	static $ar1=array('_', ':', ',', '/', '  ');
	//'+'
	static $ar2=array(',', '\'', '&#39;', '\u2019', "\u2019", '’', '´', "\xE2\x80\x99", '&rsquo;', '&lsquo;', '?', '!', '(', ')', '[', ']', 'Retranslated Version', 'Retranslated_Version', 'retranslated version', '.');
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
	else if($type==4) {
		$name=str_replace(array('+'), '', $name);
		$name=str_replace(array(' ', '_'), '*', $name);
	}
	//$name=strtolower($name);
	return $name;
}
function name_compare($name1, $name2, $type=0)
{
	$name1=name_simplify($name1, $type);
	$name2=name_simplify($name2, $type);
	//var_dump($name1,$name2);
	//if($name1=='lord of the mysteries') var_dump($name1, $name2, strcasecmp($name1, $name2));
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
function normalize($str) {
	$str=transliterator_transliterate("Hex-Any", $str);
	$str=str_replace('\\u0026', '&', $str);
	$str=html_entity_decode($str, ENT_NOQUOTES, 'UTF-8');
	$str=html_entity_decode($str, ENT_NOQUOTES, 'UTF-8');
	$str=transliterator_transliterate("[:^ASCII:] Any-Hex", $str);
	return $str;
}
if ( !function_exists( 'is_iterable' ) )
{
	function is_iterable( $obj )
	{
		return is_array( $obj ) || ( is_object( $obj ) && ( $obj instanceof \Traversable ) );
	}
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
function parse_publishTimeFormat($str) {
	$str=str_replace('yr', ' year', $str);
	$str=str_replace('mth', ' month', $str);
	$str=str_replace('d', ' day', $str);
	$str=str_replace('h', ' hour', $str);
	$str.=' ago';
	return $str;
}
function &get($obj, $attr) {
	if(is_array($obj)) {
		if(array_key_exists($attr, $obj)) {
			return ($obj[$attr]);
		}
		else throw new OutOfBoundsException();
	}
	else if(is_object($obj)) {
		if(property_exists($obj, $attr)) {
			return ($obj->{$attr});
		}
		else throw new OutOfBoundsException();
	}
	else throw new UnexpectedValueException();
}
function &set($obj, $attr, $value) {
	$var=&get($obj, $attr);
	$var=$value;
	return $var;
}
/**
 * Format a timestamp to display its age (5 days ago, in 3 days, etc.).
 *
 * @param   int     $timestamp
 * @param   int     $now
 * @return  string
 */
function timetostr($timestamp, $now = null) {
	$age = ($now ?: time()) - $timestamp;
	$future = ($age < 0);
	$age = abs($age);

	$age = (int)($age / 60); // minutes ago
	if ($age == 0) return $future ? "momentarily" : "just now";

	$scales = [
		["minute", "minutes", 60],
		["hour", "hours", 24],
		["day", "days", 7],
		["week", "weeks", 4.348214286], // average with leap year every 4 years
		["month", "months", 12],
		["year", "years", 10],
		["decade", "decades", 10],
		["century", "centuries", 1000],
		["millenium", "millenia", PHP_INT_MAX]
	];

	foreach ($scales as list($singular, $plural, $factor)) {
		if ($age == 0)
			return $future
				? "in less than 1 $singular"
				: "less than 1 $singular ago";
		if ($age == 1)
			return $future
				? "in 1 $singular"
				: "1 $singular ago";
		if ($age < $factor)
			return $future
				? "in $age $plural"
				: "$age $plural ago";
		$age = (int)($age / $factor);
	}
}
function iglob($pattern) {
	if( ($handle=opendir('.'))!==false) {
		$ar=array();
		while( ($entry=readdir($handle))!==false ) {
			if(fnmatch($pattern, $entry, FNM_CASEFOLD)) {
				$ar[]=$entry;
			}
		}
		natcasesort($ar);
		closedir($handle);
		return $ar;
	}
	else {
		return array();
	}
}
