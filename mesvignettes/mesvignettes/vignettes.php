<?
include "common.php";
include "cache.php";

$sourceimg=$_GET["sourceimg"];
$largeur = 0;
if(array_key_exists("largeur", $_GET)) {
	$largeur=intval($_GET["largeur"]);
}
$hauteur = 0;
if(array_key_exists("hauteur", $_GET)) {
	$hauteur=strtoupper($_GET["hauteur"]);
}

$cadrak = "0";
if(array_key_exists("cadrak", $_GET)) {
	$cadrak=strtoupper($_GET["cadrak"]);
}

$img_cached_filename = cache_get_filename($sourceimg);
$img_date = filemtime($file_lookup_prefix."/".$sourceimg);
$img_cached = cache_check($img_cached_filename, $img_date);
if($img_cached !== FALSE) {
  echo $img_cached;
  exit(0);
}

$extent=substr($sourceimg,strrpos($sourceimg,"."));
$extensaj=strtoupper($extent);

$sizeimgo=getimagesize($file_lookup_prefix."/".$sourceimg);
$largeuro=$sizeimgo[0];
$hauteuro=$sizeimgo[1];

if($hauteur == 0 && $largeur == 0) {
	$hauteur = 100;
}

if($hauteur == 0) {
	$hauteur = $largeur*$hauteuro/$largeuro;
}
else if($largeur == 0) {
	$largeur = $largeuro*$hauteur/$hauteuro;
}
else {
	if($largeuro*$hauteur > $largeur*$hauteuro) {
	  $hauteur = $largeur*$hauteuro/$largeuro;
	}
	else if($largeuro*$hauteur < $largeur*$hauteuro) {
	  $largeur = $largeuro*$hauteur/$hauteuro;
	}
}

/* ### Type d'image ### */
if($extensaj=='.JPG' || $extensaj=='.JPEG'){
	header("Content-Type: image/JPEG");
	$imxz=@imagecreatefromjpeg($file_lookup_prefix."/".$sourceimg);
}
if($extensaj=='.GIF'){
	header("Content-Type: image/PNG");
	$imxz=@imagecreatefromgif($file_lookup_prefix."/".$sourceimg);
}
if($extensaj=='.PNG'){
	header("Content-Type: image/PNG");
	$imxz=@imagecreatefrompng($file_lookup_prefix."/".$sourceimg);
}
header('Pragma: public');
header('Cache-Control: max-age=86400');
// 100 jours de cache
header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 8640000));


/* ### Création de l'image à la longeur et la largeur de la vignette ### */
// $im = @imagecreate ($largeur, $hauteur);
$im = @imagecreatetruecolor ($largeur, $hauteur);


/* ### On applique un fond noir ### */
imagefill ($im, 0, 0, imagecolorallocate($im, 0, 0, 0));	


/* ### Construction d'un cadre autour de l'image ### */
if($cadrak=='1'){
	$largeur=$largeur-2;
	$hauteur=$hauteur-2;
	$posixe='1';
	$posigrek='1';
}
else{
	$posixe='0';
	$posigrek='0';
}


/* ### On colle et on redimensionne l'image sur la vignette ### */
ImageCopyResampled($im,$imxz,$posixe,$posigrek,0,0,$largeur,$hauteur,$largeuro,$hauteuro);


/* ### Affichage de l'image ### */
if($extensaj=='.JPG' || $extensaj=='.JPEG'){
	imagejpeg($im); /* image compressée à un taux de 90% */
}
if($extensaj=='.GIF' || $extensaj=='.PNG'){
	imagepng ($im);
}

cache_save($img_cached_filename, $im);

/* ### Destruction de la source ### */
imagedestroy ($im);
?>