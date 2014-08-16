<?
include "common.php";

$sourceimg=$_GET["sourceimg"];
$largeur = 0;
if(array_key_exists("largeur", $_GET)) {
	$largeur=intval($_GET["largeur"]);
}
$hauteur = 0;
if(array_key_exists("hauteur", $_GET)) {
	$hauteur=strtoupper($_GET["hauteur"]);
}
//$extensaj=strtoupper($_GET["extensaj"]);
//$largeuro=strtoupper($_GET["largeuro"]);
//$hauteuro=strtoupper($_GET["hauteuro"]);
$cadrak = "0";
if(array_key_exists("cadrak", $_GET)) {
	$cadrak=strtoupper($_GET["cadrak"]);
}



//error_log($file_lookup_prefix."/".$sourceimg);

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
header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));


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


/* ### Destruction de la source ### */
imagedestroy ($im);
?>