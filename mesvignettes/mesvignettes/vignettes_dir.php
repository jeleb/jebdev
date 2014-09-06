<?
include "common.php";

$dir=strtoupper($_GET["dir"]);
$largeur=strtoupper($_GET["largeur"]);
$hauteur=strtoupper($_GET["hauteur"]);
$cadrak = "0";
$cover = getWithDefault($_GET, "cover", null);

// taille des bord arrondis (pour l'instant, ils sont juste coupés)
//$border_rounded_radius = 15;

// Set the content-type
header('Content-Type: image/jpeg');

header('Pragma: public');
header('Cache-Control: max-age=86400');
header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

/* recherche de la premiere image du repertoire */
$dirname = '';
if($dir!=''){
	$urlt=$dir.'/';
	$dossier = opendir($file_lookup_prefix."/".$urlt);
	$dirname = $dir;
	$i = strrpos($dir,"/");
	if($i > 0) {
		$dirname=substr($urlt,strrpos($dir,"/")+1);
	}
	$dirname=str_replace('/', '', $dirname);
}
else{
	$dossier = opendir($file_lookup_prefix);
}

$sourceimg = null;
$extensaj = null;
if($cover == null) {
	while($fichier = readdir($dossier)){
		$extent=substr($fichier,strrpos($fichier,"."));
		$extensaj=strtoupper($extent);
		if($extensaj=='.JPG' || $extensaj=='.JPEG' || $extensaj=='.GIF' || $extensaj=='.PNG'){
			$sourceimg=$fichier;
			break;
		}
	}
}
else {
	$sourceimg = $cover;
	$extent=substr($sourceimg,strrpos($sourceimg,"."));
	$extensaj=strtoupper($extent);
}

$imxz = NULL;
if($sourceimg != NULL) {
	if($extensaj=='.JPG' || $extensaj=='.JPEG'){
		header("Content-Type: image/JPEG");
		$imxz=@imagecreatefromjpeg($file_lookup_prefix."/".$urlt.$sourceimg);
	}
	if($extensaj=='.GIF'){
		header("Content-Type: image/PNG");
		$imxz=@imagecreatefromgif($file_lookup_prefix."/".$urlt.$sourceimg);
	}
	if($extensaj=='.PNG'){
		header("Content-Type: image/PNG");
		$imxz=@imagecreatefrompng($file_lookup_prefix."/".$urlt.$sourceimg);
	}
	
	$sizeimgo=getimagesize($file_lookup_prefix."/".$urlt.$sourceimg);
	$largeuro=$sizeimgo[0];
	$hauteuro=$sizeimgo[1];
	
	/*if($largeuro*$hauteur > $largeur*$hauteuro) {
	  $hauteur = $largeur*$hauteuro/$largeuro;
	}
	else if($largeuro*$hauteur < $largeur*$hauteuro) {
	  $largeur = $largeuro*$hauteur/$hauteuro;
	}*/
	
}

/* ### Création de l'image à la longeur et la largeur de la vignette ### */
// $im = @imagecreate ($largeur, $hauteur);
$im = @imagecreatetruecolor ($largeur, $hauteur);

/* ### On applique un fond noir ### */
imagefill ($im, 0, 0, imagecolorallocate($im, 0, 0, 0));	

/* ### Construction d'un cadre autour de l'image ### */
$pos_src_x=0;
$pos_src_y=0;
$posixe=0;
$posigrek=0;
if($cadrak=='1'){
	$largeur=$largeur-2;
	$hauteur=$hauteur-2;
	$posixe='1';
	$posigrek='1';
}
else{
	
	if($largeuro*$hauteur > $largeur*$hauteuro) {
		$pos_src_x = ($largeuro - $largeur*$hauteuro/$hauteur);
		$largeuro = $largeur*$hauteuro/$hauteur;
	}
	else if($largeuro*$hauteur < $largeur*$hauteuro) {
		$pos_src_y = ($hauteuro - $largeuro*$hauteur/$largeur);
		$hauteuro = $largeuro*$hauteur/$largeur;
	}
}

/* ### On colle et on redimensionne l'image sur la vignette ### */
if($imxz != NULL) {
	ImageCopyResampled($im,$imxz,$posixe,$posigrek,$pos_src_x,$pos_src_y,$largeur,$hauteur,$largeuro,$hauteuro);
}

/* ajout du texte */
//$cor = imagecolorallocate($im, 0, 0, 0);
//imagestring($im,5,5,5,$dirname,$cor);
$white = imagecolorallocate($im, 255, 255, 255);
$grey = imagecolorallocate($im, 128, 128, 128);
$black = imagecolorallocate($im, 0, 0, 0);
//imagefilledrectangle($im, 0, 0, 399, 29, $white);
if($imxz == NULL) {
	$fontsize = 16;
	$y = ($hauteur-$fontsize)/2;
	imagettftext($im, $fontsize, 0, 10, $y, $white, 'arialbd.ttf', $dirname);
	imagettftext($im, $fontsize, 0, 11, $y+1, $grey, 'arialbd.ttf', $dirname);
}
else {
	$fontsize = 16;
	imagettftext($im, $fontsize, 0, 10, 10+$fontsize, $white, 'arialbd.ttf', $dirname);
	imagettftext($im, $fontsize, 0, 11, 11+$fontsize, $black, 'arialbd.ttf', $dirname);
}


// bords coupes haut gauche
/*imagefilledpolygon($im, array(0, 0, 0, $border_rounded_radius, $border_rounded_radius, 0), 3, $black);
// bords coupes haut droite
imagefilledpolygon($im, array($largeur, 0, $largeur-$border_rounded_radius, 0, $largeur, $border_rounded_radius), 3, $black);
// bords coupes bas gauche
imagefilledpolygon($im, array(0, $hauteur, 0, $hauteur-$border_rounded_radius, $border_rounded_radius, $hauteur), 3, $black);
// bords coupes haut droite
imagefilledpolygon($im, array($largeur, $hauteur, $largeur, $hauteur-$border_rounded_radius, $largeur-$border_rounded_radius, $hauteur), 3, $black);
*/

imagejpeg($im); /* image compressée à un taux de 90% */

/* ### Destruction de la source ### */
imagedestroy ($im);
?>