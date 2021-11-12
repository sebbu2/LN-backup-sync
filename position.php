<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('CJSON.php');
require_once('vendor/autoload.php');

require_once('wlnupdates.php');
require_once('webnovel.php');
require_once('royalroad.php');

if(!defined('MOONREADER_DID')) define('MOONREADER_DID', '1454083831785');
if(!defined('MOONREADER_DID2')) define('MOONREADER_DID2', '9999999999999');

//if(!defined('DROPBOX_DONE')||!DROPBOX_DONE) $sub=true;
if(direct()) include('header.php');

class Position
{
	public const scroll = array('epub', 'mhtml', 'txt');
	public const page = array('cbr', 'cbz', 'pdf');
	private $ar=array();
	private $ar2=array();
	public $ar3=array();
	public $old=array();
	public $others=array();
	public $dev_1=array();
	public $dev_9=array();
	function __construct() {
	}
	public function list() {
		chdir(DROPBOX);
		$ar=glob('*.po');
		natcasesort($ar);
		chdir(CWD);
		$this->ar=$ar;
		return $ar;
	}
	public function parseList(array $ar=NULL) {
		if(is_null($ar)||!is_array($ar)|count($ar)==0) {
			if(is_null($this->ar)||!is_array($this->ar)|count($this->ar)==0) {
				$ar=$this->list();
			}
			else $ar=$this->$ar;
		}
		$ar2=array();
		chdir(DROPBOX);
		foreach($ar as $v) {
			$ar2[$v]=array(
				'content'=>file_get_contents($v),
				'time'=>filemtime($v),
			);
			$ar2[$v]=array_merge( $ar2[$v], $this->parseFilename($v), $this->parseFileContent($ar2[$v]['content']) );
			if(in_array($ar2[$v]['ext'], $this::scroll)) {
				if($ar2[$v][1]==0 && $ar2[$v][3]==0) $ar2[$v]['pos']=0;
				else $ar2[$v]['pos']=$ar2[$v]['min'] + ($ar2[$v]['min']<=-1?1:0) + $ar2[$v][1] - 1 + ($ar2[$v][3]>0?1:0);
				if($ar2[$v][4]=='100' && $ar2[$v][3]=='0') $ar2[$v]['pos']++;
			}
			else if(in_array($ar2[$v]['ext'], $this::page)) {
				if($ar2[$v][1]==0) $ar2[$v]['pos']=0;
				else $ar2[$v]['pos']=$ar2[$v]['min'] + ($ar2[$v]['min']<=-1?1:0) + $ar2[$v][1] -1;
			}
		}
		chdir(CWD);
		$this->ar2=$ar2;
		return $ar2;
	}
	public function parseFilename(string $fn) {
		preg_match('#^(.*)_(-?\d+)-(\d+)(_FIN)?\.epub\.po$#i', $fn, $matches);
		if(!empty($matches)) {
			$fn2=$matches[1];
			$min=$matches[2];
			$max=$matches[3];
		}
		else {
			preg_match('#^(-?\d+)-(\d+)_(.*)\.epub\.po$#i', $fn, $matches);
			if(!empty($matches))
			{
				$min=$matches[1];
				$max=$matches[2];
				$fn2=$matches[3];
			}
		}
		if(empty($matches)) {
			//die('filename not recognized : "'.$fn.'"');
			$min=0;
			$max=null;
			if(strpos($fn, '.epub.po')==strlen($fn)-8) {
				//id, chap, vol, pos_in_chap, per
				$fn2=substr($fn, 0, -8);
			}
			else if(strpos($fn, '.pdf.po')==strlen($fn)-7) {
				//id, page, per
				$fn2=substr($fn, 0, -7);
			}
			else if(strpos($fn, '.cbz.po')==strlen($fn)-7 || strpos($fn, '.cbr.po')==strlen($fn)-7) {
				//id, page, per
				$fn2=substr($fn, 0, -7);
			}
			else if(strpos($fn, '.mhtml.po')==strlen($fn)-9) {
				//id, chap, vol, pos_in_chap, per
				$fn2=substr($fn, 0, -9);
			}
			else if(strpos($fn, '.txt.po')==strlen($fn)-7) {
				//id, chap, vol, pos_in_chap, per
				$fn2=substr($fn, 0, -7);
			}
			else {
				$fn2='test';
				var_dump($fn);die();
			}
		}
		$fn3=name_simplify($fn2, 1);
		$fn3=strtolower($fn3);
		$ext1=strrpos($fn, '.', -4);
		$ext2=strpos($fn, '.', $ext1+1);
		$ext=substr($fn, $ext1+1, $ext2-$ext1-1);
		return array('min'=>$min, 'max'=>$max, 'fn'=>$fn, 'fn2'=>$fn2, 'fn3'=>$fn3, 'ext'=>$ext);
	}
	public function createFilename($fn3, $min, $max) {
		if($min>$max) throw new Exception('chapter range must be an increasing range');
		if(!is_array($this->ar3)||count($this->ar3)==0) $this->parseTitle();
		$fn='';
		if(!array_key_exists($fn3, $this->ar3)) {
			$fn=ucwords($fn3);
			$fn=str_replace(' ', '_', $fn);
		}
		else {
			$fn=$this->ar3['fn2'];
		}
		$fn.='_'.$min;
		if($max>$min) {
			$fn.='-'.$max;
		}
		$fn.='.epub.po';
		return $fn;
	}
	public function createFileContent($min, $pos, $max) {
		if($min>$max) throw new Exception('chapter range must be an increasing range');
		//var_dump($min, $pos, $max);
		if(($pos<$min || $pos>$max) && ($pos!=0)) throw new Exception('position must be between min and max');
		$chp=($min<0?$pos-$min:$pos-$min+($pos>1?1:0));
		if($pos==0) $chp=0;
		//var_dump($chp);
		$per=null;
		if($pos<=$min) $per='0.0';
		else if($pos==$max) $per='100';
		else {
			$per=100*($pos-$min+1+($min<0?1:0))/($max-$min+1);
			$per=number_format($per, 1);
		}
		if(($per=='100'||$per=='100.0') && $pos!=$max) $per=99.99;
		$data=MOONREADER_DID2.//id
			'*'.
			$chp. //chap
			'@'.
			'0'.//vol
			'#'.
			($pos==$max?100:0).//pos_in_chap
			':'.
			$per.//per
			'%';
		return $data;
	}
	public function parseFileContent(string $data) {
		$content=array();
		$content[]=strtok($data, '*@#:%');
		while(($content[]=strtok('*@#:%'))!==FALSE);
		$id=array_search(false, $content, true);
		$content=array_slice($content, 0, $id);
		return $content;
	}
	public function parseTitle(array $ar2) {
		if(is_null($ar2)||!is_array($ar2)|count($ar2)==0) {
			if(is_null($this->ar2)||!is_array($this->ar2)|count($this->ar2)==0) {
				$ar2=$this->parseList();
			}
			else $ar2=$this->$ar2;
		}
/*
1)a) if content[0] MOONREADER_DID
	fns
1)b) if content[0] MOONREADER_DID2
	fns_
2) if max > last (both MOONREADER_DID and MOONREADER_DID2) && pos >=
	replace & delete old
3) if last (MOONREADER_DID and MOONREADER_DID2) > max && pos <=
	delete new
4) if MOONREADER_DID2 < MOONREADER_DID
	delete
*/
		reset($ar2);
		$k=key($ar2);
		$v=current($ar2);
		assert(array_key_exists('fn3', $v)) or die('wrong input');
		$dev_1=array();
		$dev_9=array();
		$old=array();
		$others=array();
		foreach($ar2 as $k=>$v) {
			$k=$v['fn3'];
			$ov=NULL;
			$cpos=$v['pos'];
			$opos=NULL;
			if($v[0]==MOONREADER_DID) {
				if(!array_key_exists($k, $dev_1)) {
					$dev_1[$k]=$v;
				}
				else {
					$ov=$dev_1[$k];
					$opos=$ov['pos'];
					// pos >
					if( ($cpos>0?$cpos:0) > ($opos>0?$opos:0) ) {
						$old[]=$ov['fn'];
						$dev_1[$k]=$v;
						$dev_1[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					else if( ($opos>0?$opos:0) > ($cpos>0?$cpos:0) ) {
						$old[]=$v['fn'];
						$dev_1[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					// pos =, max >
					else if( $ov['max']<$v['max'] ) {
						$old[]=$ov['fn'];
						$dev_1[$k]=$v;
						$dev_1[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					else if( $ov['max']>$v['max'] ) {
						$old[]=$v['fn'];
						$dev_1[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					// pos =, max =, min >
					else if( $ov['min']<$v['min']) {
						$old[]=$ov['fn'];
						$dev_1[$k]=$v;
						$dev_1[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					else if( $ov['min']>$v['min']) {
						$old[]=$v['fn'];
						$dev_1[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					else {
						$others[]=$ov['fn'];
						$dev_1[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
				}
				//clean
				if( array_key_exists($k, $dev_9)) {
					$ov2=$dev_9[$k];
					$opos2=$ov2['pos'];
					if($cpos > $opos2 || (!is_null($opos)&&$opos > $opos2) ) {
						$old[]=$ov2['fn'];
						$dev_1[$k]['fns']=array_merge(
							( is_array($ov)? (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])) : array()),
							(array_key_exists('fns', $ov2)?$ov2['fns']:array($ov2['fn'])),
							array($v['fn'])
						);
					}
				}
			}
			else if($v[0]==MOONREADER_DID2) {
				if(!array_key_exists($k, $dev_9)) {
					$dev_9[$k]=$v;
				}
				else {
					$ov=$dev_9[$k];
					$opos=$ov['pos'];
					// pos >
					if( ($cpos>0?$cpos:0) > ($opos>0?$opos:0) ) {
						$old[]=$ov['fn'];
						$dev_9[$k]=$v;
						$dev_9[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					else if( ($opos>0?$opos:0) > ($cpos>0?$cpos:0) ) {
						$old[]=$v['fn'];
						$dev_9[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					// pos =, max >
					else if( $ov['max']<$v['max'] ) {
						$old[]=$ov['fn'];
						$dev_9[$k]=$v;
						$dev_9[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					else if( $ov['max']>$v['max'] ) {
						$old[]=$v['fn'];
						$dev_9[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					// pos =, max =, min >
					else if( $ov['min']<$v['min']) {
						$old[]=$ov['fn'];
						$dev_9[$k]=$v;
						$dev_9[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					else if( $ov['min']>$v['min']) {
						$old[]=$v['fn'];
						$dev_9[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
					else {
						$others[]=$v['fn'];
						$dev_9[$k]['fns']=array_merge( (array_key_exists('fns', $ov)?$ov['fns']:array($ov['fn'])), array($v['fn']) );
					}
				}
			}
		}
		$this->old=$old;
		$this->others=$others;
		$this->dev_1=$dev_1;
		$this->dev_9=$dev_9;
		$this->ar3=array_merge($dev_9, $dev_1);
		return $this->ar3;
	}
};

if(direct()) {
	$t1=0; $t2=0;
	$pos=new Position;

	$t1=microtime(true);

	$ar=$pos->list();

	$t2=microtime(true);
	var_dump(($t2-$t1)*1000);
	$t1=microtime(true);

	$ar2=$pos->parseList($ar);

	$t2=microtime(true);
	var_dump(($t2-$t1)*1000);
	$t1=microtime(true);

	$ar3=$pos->parseTitle($ar2);

	$t2=microtime(true);
	var_dump(($t2-$t1)*1000);
	$t1=microtime(true);

	var_dump(count($ar));
	var_dump(count($ar2));
	var_dump(count($ar3));
	var_dump(count($pos->old),count($pos->others),count($pos->dev_1),count($pos->dev_9));

	var_dump(count($pos->old)+count($pos->others)+count($pos->dev_1)+count($pos->dev_9)===count($ar2));
	
	var_dump($pos->old);
	
	/*
	//$orig=array_filter($pos->others, function($e) { return ($e[0]==MOONREADER_DID2);});
	//var_dump($orig);
	var_dump($pos->old,$pos->others);
	var_dump($pos->dev_1, $pos->dev_9);//var_dump($ar3);
	//*/

	/*
	$k='pocket hunting dimension';
	//$k='pocket_hunting_dimension';
	var_dump($ar3[$k]);
	//$min=11;$max=14;$pos_=12;
	//$min=2;$max=51;$pos_=2;
	$min=1;$max=5;$pos_=1;
	$fn=$pos->createFilename($ar3[$k]['fn2'], $min, $max);
	$data=$pos->createFileContent($min, $pos_, $max);
	var_dump($fn, $data);
	//*/

	ksort($pos->old, SORT_FLAG_CASE | SORT_NATURAL);
	ksort($pos->others, SORT_FLAG_CASE | SORT_NATURAL);
	ksort($pos->dev_1, SORT_FLAG_CASE | SORT_NATURAL);
	ksort($pos->dev_9, SORT_FLAG_CASE | SORT_NATURAL);
	ksort($ar3, SORT_FLAG_CASE | SORT_NATURAL);

	//var_dump($pos->old);die();
	file_put_contents('pos_old.json',json_encode($pos->old, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
	//var_dump($pos->others);die();
	file_put_contents('pos_others.json',json_encode($pos->others, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
	//var_dump($pos->dev_1);die();
	/*$str=json_encode($pos->dev_1, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING);
	var_dump($str);
	if($str===false) { var_dump(json_last_error(), json_last_error_msg()); }
	die();//*/
	file_put_contents('pos_dev1.json',json_encode($pos->dev_1, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
	//var_dump(count($pos->dev_1));die();
	file_put_contents('pos_dev9.json',json_encode($pos->dev_9, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
	file_put_contents('pos.json',json_encode($ar3, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
}
else {
	$pos_=new Position;
	$pos_old=json_decode(file_get_contents('pos_old.json'), true, 512, JSON_THROW_ON_ERROR);
	$pos_others=json_decode(file_get_contents('pos_others.json'), true, 512, JSON_THROW_ON_ERROR);
	$pos_dev1=json_decode(file_get_contents('pos_dev1.json'), true, 512, JSON_THROW_ON_ERROR);
	$pos_dev9=json_decode(file_get_contents('pos_dev9.json'), true, 512, JSON_THROW_ON_ERROR);
	$pos=json_decode(file_get_contents('pos.json'), true, 512, JSON_THROW_ON_ERROR);
	$pos_->ar3=$pos;
}

if(direct()) include('footer.php');
