<?
include "config.php";
include "common.php";

$sourceimg=$_GET["sourceimg"];

$imgfile = $file_lookup_prefix."/".$sourceimg;
$extent=substr($sourceimg,strrpos($sourceimg,"."));
$extensaj=strtoupper($extent);
error_log("imgfile=$imgfile ext=$extensaj");


/* ### Type d'image ### */
if($extensaj=='.JPG' || $extensaj=='.JPEG'){
	header("Content-Type: image/JPEG");
	$imxz=imagecreatefromjpeg($file_lookup_prefix."/".$sourceimg);
}
if($extensaj=='.GIF'){
	header("Content-Type: image/PNG");
	$imxz=imagecreatefromgif($file_lookup_prefix."/".$sourceimg);
}
if($extensaj=='.PNG'){
	header("Content-Type: image/PNG");
	$imxz=imagecreatefrompng($file_lookup_prefix."/".$sourceimg);
}
header('Pragma: public');
header('Cache-Control: max-age=86400');
// 100 jours de cache
header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 8640000));

// todo : streamer ?
//echo(file_get_contents($imgfile));
readfile($imgfile);


?>