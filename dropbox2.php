<?php
require_once('config.php');
require_once('functions.inc.php');
require_once('wlnupdates.php');
require_once('webnovel.php');

if(!defined('MOONREADER_DID')) define('MOONREADER_DID', '1454083831785');
if(!defined('MOONREADER_DID2')) define('MOONREADER_DID2', '9999999999999');

include('header.php');

chdir(DROPBOX);
$ar=glob('*.po');
natcasesort($ar);

chdir(CWD);

$updatedCount=array(
	'wln'=>0,
	'wn'=>0,
);

// TODO : file outdated

$fns=array();
foreach($ar as $fn)
{
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
	if(empty($matches))
	{
		//var_dump($fn);
		echo '<div class="block b b-red">',$fn,'</div>',"\n";
		continue;
	}
	/*$fn2=str_replace(array('_'), ' ', $fn2);
	$fn2=str_replace(array('Retranslated Version'), '', $fn2);
	$fn2=trim($fn2);//*/
	$fn2=name_simplify($fn2, 1);
	$fn3=strtolower($fn2);
	$data=file_get_contents(DROPBOX.$fn);
	$content=array();
	$content[]=strtok($data, '*@#:%');
	while(($content[]=strtok('*@#:%'))!==FALSE);
	unset($data);
	$id=array_search(false, $content, true);
	$content=array_slice($content, 0, $id);
	if($fn==='Unbound_1-7.epub.po') continue;
	if( !array_key_exists($fn3, $fns) )
	{
		$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2);
		$ar2=array_merge($ar2, $content);
		$fns[$fn3]=$ar2;
	}
	else if(
		$max>$fns[$fn3]['max'] && //new last chapter is >
		(($min+($min<0?1:0)+$content[1]+($content[3]>0?1:0)) >= ($fns[$fn3]['min']+($fns[$fn3]['min']<0?1:0)+$fns[$fn3][1]+($fns[$fn3][3]>0?1:0))) // position is same or later (no diff between end of chapter and start of new one)
	) {
		//var_dump($fns[$fn3]['fn']);//die();
		if($content[0]!=MOONREADER_DID2) {
			unlink(DROPBOX.$fns[$fn3]['fn']);//die();
			echo '<div class="block b b-blue">',$fns[$fn3]['fn'],'</div>',"\n";
		}
		else {
			echo '<div class="block b b-green">',$fns[$fn3]['fn'],'</div>',"\n";
		}
		$ar2=array('min'=>(int)$min, 'max'=>(int)$max, 'fn'=>$fn, 'fn2'=>$fn2);
		$ar2=array_merge($ar2, $content);
		$fns[$fn3]=$ar2;
	}
	else if(
		$max < $fns[$fn3]['max'] && //new last chapter is >
		(($min+($min<0?1:0)+$content[1]+($content[3]>0?1:0)) <= ($fns[$fn3]['min']+($fns[$fn3]['min']<0?1:0)+$fns[$fn3][1]+($fns[$fn3][3]>0?1:0))) // position is same or later (no diff between end of chapter and start of new one)
	) {
		//var_dump($fn);//die();
		if($fns[$fn3][0]!=MOONREADER_DID2) {
			unlink(DROPBOX.$fn);//die();
			echo '<div class="block b b-blue">',$fn,'</div>',"\n";
		}
		else {
			echo '<div class="block b b-green">',$fn,'</div>',"\n";
		}
	}
}
//var_dump(count($fns));
//require_once('watches.inc.php');
$wln=new WLNUpdates();
$wn=new WebNovel;
$watches=json_decode(file_get_contents($wln::FOLDER.'_books.json'));
//var_dump(count($watches));//die();
$books=json_decode(file_get_contents($wn::FOLDER.'_books.json'));
//var_dump(count($books));//die();

echo '<h2>Counts</h2>',"\n";
print_table(array(array('files'=>count($fns), 'WLNUpdates'=>count($watches),'WebNovel'=>count($books))));
echo '<br/>'."\r\n";
if(ob_get_level()>0) { ob_end_flush(); ob_flush(); }
flush();
foreach($fns as $name=>$fn)
{
	//wlnupdates
	$key='';
	$id=-1;
	$found1=false;
	{
		/*foreach($watches as $_key=>$ar1)
		{
			foreach($ar1 as $_id=>$ar2)
			{
				if(name_compare($name, $ar2['title']))
				{
					$key=$_key;
					$id=$_id;
					$found1=true;
					break(2);
				}
			}
		}//*/
		foreach($watches as $_key=>$book)
		{
			if($book[3]!==NULL && name_compare($name, $book[3], 1))
			{
				$key=$_key;
				$id=$book[0]->id;
				$found1=true;
				break;
			}
		}
		if(!$found1) {
			foreach($watches as $_key=>$book)
			{
				if(name_compare($name, $book[0]->name, 1))
				{
					$key=$_key;
					$id=$book[0]->id;
					$found1=true;
					break;
				}
			}
		}
		if($found1) {
			$fns[$name]['watches']=$watches[$key];
		}
		else {
			var_dump($name.' not found in WLNUpdates.');
		}
	}
	if($found1) {
		// DO NOTHING
	}
	else {
		// THIS FILES search the missing novels to add them
		//var_dump($name);
		//continue;
		$res=$wln->search($name);
		//var_dump($res);
		//$res=json_decode($res);
		//var_dump($res);
		$found=false;
		$sid=-1;
		$accuracy=0;
		foreach($res->data->results as $m1) {
			if(count($m1->match)>1) {
				foreach($m1->match as $m2) {
					if($m2[0]>=0.9) {
						$found=true;
						var_dump($m2[1]);
						$sid=$m1->sid;
						$accuracy=$m2[0];
					}
				}
			}
			else {
				if($m1->match[0][0]>=0.9) {
					$found=true;
					var_dump($m1->match[0][1]);
					$sid=$m1->sid;
					$accuracy=$m1->match[0][0];
				}
			}
			if($found) break;
		}
		if($found) {
			var_dump($sid, $accuracy);
			if($accuracy===1) {
				$res=$wln->add_novel($sid, 'QIDIAN');
				//$res=json_decode($res);
				var_dump($res);
				if($res->error) die('error');
				var_dump($name. ' added to WLNUpdates.');
				$updatedCount['wln']++;
				continue; // TODO : fix following code (get_info)
				$res=$wln->info($sid);
				//$res=json_decode($res);
				var_dump($res);
				$fns[$name]['watches']=$res;
				$found1=true;
			}
			die();
		}
		else {
			var_dump($name.' not found in WLNUpdates search');
			//var_dump($res->data->results[0]->match[0]);
			$res=$wn->checkLogin();
			//var_dump($res);
			if($res->code!=0) {
				var_dump($res->code, $res->msg);
				$res=$wn->login( $accounts['WebNovel']['user'], $accounts['WebNovel']['pass']);
				var_dump($res);
			}
			$res=$wn->search2($name);
			assert($res==false || $res->code==0);
			if($res==false || !property_exists($res->data, 'books') || count($res->data->books)!=1 || (count($res->data->books)==1 && !name_compare($name, $res->data->books[0]->name, 1)) ) {
				var_dump($res);
				$res=$wn->search($name);
				var_dump(count($res));//die();
				foreach($res as $k=>$v) {
					if(name_compare($v['title'], $name, 1)) {
						//var_dump($v);die();
						$res2=$wn->get_info_cached($v['data-bookid']);
						$tl='';
						if($res2[0]->Data->Type==1) $tl='translated';
						if($res2[0]->Data->Type==2) $tl='oel';
						assert(in_array($tl, array('translated', 'oel'))) or die('wrong tl type');
						var_dump('wn get_info',$res2[0]->Result, $res2[0]->Message, $res2[1]->Result, $res2[1]->Message);
						$res=$wln->add($v['title'], $tl);
						var_dump($res);
						if(is_numeric($res)) {
							$res=$wln->add_novel($res);
							var_dump($res);
						}
						//die();
						$updatedCount['wln']++;
					}
				}
				//die();
			}
			else { // good
				assert(name_compare($name, $res->data->books[0]->name, 1)) or die('name "'.$name.'" and "'.$res->data->books[0]->name.'" doesn\'t match.');
				$res2=$wn->get_info_cached($res->data->books[0]->id);
				$tl='';
				if($res2[0]->Data->Type==1) $tl='translated';
				if($res2[0]->Data->Type==2) $tl='oel';
				assert(in_array($tl, array('translated', 'oel'))) or die('wrong tl type');
				//var_dump($res2);
				var_dump('wn get_info',$res2[0]->Result, $res2[0]->Message, $res2[1]->Result, $res2[1]->Message);
				$res=$wln->add($res->data->books[0]->name, $tl);
				var_dump($res);
				$updatedCount['wln']++;
				//die();
			}
			//*/
		}
		//die();
	}
	
	// webnovel
	$key='';
	$id=-1;
	$found2=false;
	{
		foreach($books as $key=>$book) {
			if(!is_object($book)) {
				var_dump($key,$book);die();
			}
			
			if($book->novelType==0 && name_compare($name, @$book->bookName, 1))
			{
				$id=$book->bookId;
				$found2=true;
				break;
			}
		}
		if($found2) {
			// DO NOTHING
		}
		else {
			// TODO
			var_dump($name.' not found in WebNovel.');
		}
	}
	if($found2) {
		// DO NOTHINGs
	}
	else {
		// THIS FILES search the missing novels to add them
		$res=$wn->checkLogin();
		//var_dump($res);
		if($res->code!=0) {
			var_dump($res->code, $res->msg);
			$res=$wn->login( $accounts['WebNovel']['user'], $accounts['WebNovel']['pass']);
			var_dump($res);
		}
		/*$res=$wn->search($name);
		var_dump($res);//*/
		//var_dump($name);
		$res=$wn->search2($name);
		assert($res->code==0);
		var_dump($res);
		if(!property_exists($res->data, 'books')) {
			//if(!$found1) die('novel is in neither wlnupdate nor webnovel');
			$name1='';
			$name2='';
			if($found1) {
				if(is_array($fns[$name]['watches'])) {
					$name1=$fns[$name]['watches'][3];
					$name2=$fns[$name]['watches'][0]->name;
				}
				else if(is_object($fns[$name]['watches'])) {
					$name1=$fns[$name]['watches']->data->title;
					$name2=$fns[$name]['watches']->data->alternatenames[0];
				}
			}
			if( !is_null($name1) && strlen($name1)>0 ) {
				$res=$wn->search2($name1);
				assert($res->code==0);
				var_dump($res);//die();
			}
			if( !is_null($name2) && strlen($name2)>0 && strcasecmp($name,$name2)!=0) {
				$res=$wn->search2($name2);
				assert($res->code==0);
				var_dump($res);//die();
			}
			if(!property_exists($res->data, 'books')) {
				$res=$wn->search($name);
				assert(is_array($res) && count($res)>0);
				//var_dump($res);//die();
				$res2=array_map(function($e) { return $e['title']; }, $res);
				var_dump($res2);
			}
		}
		if(!is_array($res) && (is_object($res) && !property_exists($res->data, 'books')) ) die('error');
		if( (is_array($res)&&count($res)==1) || (is_object($res)&&count($res->data->books)==1) ) {
			$id=NULL;
			if(is_object($res)) $name2=$res->data->books[0]->name;
			elseif(is_array($res)) $name2=$res[0]['title'];
			if(!name_compare($name, $name2, 1)) {
				$res=$wn->search($name);
				var_dump($res);
				foreach($res as $k=>$v) {
					if(name_compare($v['title'], $name, 1)) {
						//var_dump($v);die();
						$id=$v['data-bookid'];
					}
				}
			}
			else {
				if(is_object($res)) $id=$res->data->books[0]->id;
				elseif(is_array($res)) $id=$res[0]['data-bookid'];
			}
			if($id===NULL) {
				var_dump($name.' not found in WebNovel search');
			}
			else {
				$res=$wn->add_watch($id, 0);
				var_dump($res);
				/*$res=$wn->info($res->data->books[0]->id);
				//var_dump($res);
				var_dump('wn get_info',$res[0]->Result, $res[0]->Message, $res[1]->Result, $res[1]->Message);//*/
			}
		}
		else {
			$id=-1;
			$ids=array();
			$found=0;
			if(is_object($res)) {
				foreach($res->data->books as $book) {
					if(name_compare($name, $book->name)) {
						$found++;
						$ids[]=$book->id;
						$id=$book->id;
					}
				}
			}
			elseif(is_array($res)) {
				foreach($res as $book) {
					if(name_compare($name, $book['data-bookname'])) {
						$found++;
						$ids[]=$book['data-bookid'];
						$id=$book['data-bookid'];
					}
				}
			}
			var_dump($found);
			if($found>=1) {
				if($found!==1) {
					print('Multiple novel found with that name');
					continue;
				}
				else {
					$res=$wn->add_watch($id, 0);
					var_dump($res);
				}
			}
			else {
				var_dump('not found');
			}
		}
		//$res=json_decode($res);
		if(is_object($res)) {
			assert($res->code==0);
			assert($res->msg=='Success');
			$updatedCount['wn']++;
		}
		//die();
	}
	if(ob_get_level()>0) { ob_end_flush(); ob_flush(); }
	flush();
}
if($updatedCount['wln']>0 || $updatedCount['wn']>0) {
	define('DROPBOX_DONE', true);
	include_once('retr.php');
}
//echo '<br/><a href="retr.php">retr</a><br/>'."\r\n";

include('footer.php');
