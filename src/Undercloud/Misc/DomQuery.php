<?php
set_time_limit(0);
error_reporting(-1);
ini_set('display_errors','On');

require __DIR__ . '/lib.php';

$url = 'https://lightaudio.ru/album/source/?data=3d7d0a22fe-e2a176-2e276d84c77-2a30e7480';//$_GET['url'];

$page = file_get_contents($url);
$doc = new DomDocument;
$doc->loadHTML($page);

$doc = Undercloud\Misc\DomQuery::load($doc);

$artist = $doc->findByClass('info')->findByTag('a')->text();
$album  = $doc->findByClass('info')->findByTag('h1')->text();
$logo   = $doc->findByClass('cover')->findByTag('img')->attr('src');
$year   = $doc->findByClass('info')->findByTag('div')->findByTag('span')->eq(1)->text(); 

var_dump($artist,$album,$logo,$year);

$songs = $doc->findById('result')->findByClass('down')->map(function($node){
	$link = 'https:' . $node->attr('href');

	return (object) array(
		'link' => $link,
		'name' => urldecode(basename($link))
	);
});

var_dump(
	$songs,
	$doc->findById('result')->length(),
	$doc->findById('result')->findByClass('down')->length()
);

return;
$dir = __DIR__ . '/' .$artist . '/' . $year . ' ' . $album;
if(false == is_dir($dir)){
//	mkdir($dir,0777,true);
}

//copy($logo, $dir . '/logo.jpg');
foreach($songs as $song){
	//copy($song->link, $dir . '/' . $song->name);
}