<?
$dir=strtoupper($_GET["dir"]);
$largeur=strtoupper($_GET["largeur"]);
$hauteur=strtoupper($_GET["hauteur"]);
$cadrak=strtoupper($_GET["cadrak"]);

// Set the content-type
header('Content-Type: image/jpeg');

header('Pragma: public');
header('Cache-Control: max-age=86400');
header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

/* recherche de la premiere image du repertoire */
$dirname = '';
if($dir!=''){
	$urlt=$dir.'/';
	$dossier = opendir($urlt);
	$dirname = $dir;
	$i = strrpos($dir,"/");
	if($i > 0) {
		$dirname=substr($urlt,strrpos($dir,"/")+1);
	}
	$dirname=str_replace('/', '', $dirname);
}
else{
	$dossier = opendir('.');
}

$sourceimg = NULL;
while($fichier = readdir($dossier)){
	$extent=substr($fichier,strrpos($fichier,"."));
	$extensaj=strtoupper($extent);
	if($extensaj=='.JPG' || $extensaj=='.JPEG' || $extensaj=='.GIF' || $extensaj=='.PNG'){
		$sourceimg=$fichier;
		break;
	}
}

$imxz = NULL;
//$text_delta_x = 0;
//$text_delta_y = 0;
if($sourceimg != NULL) {
	/* ### Type d'image ### */
	if($extensaj=='.JPG' || $extensaj=='.JPEG'){
		header("Content-Type: image/JPEG");
		$imxz=@imagecreatefromjpeg($urlt.$sourceimg);
	}
	if($extensaj=='.GIF'){
		header("Content-Type: image/PNG");
		$imxz=@imagecreatefromgif($urlt.$sourceimg);
	}
	if($extensaj=='.PNG'){
		header("Content-Type: image/PNG");
		$imxz=@imagecreatefrompng($urlt.$sourceimg);
	}
	
	$sizeimgo=getimagesize($urlt.$sourceimg);
	$largeuro=$sizeimgo[0];
	$hauteuro=$sizeimgo[1];
	if($largeuro*$hauteur > $largeur*$hauteuro) {
	  //$text_delta_y = ($hauteur - $largeur*$hauteuro/$largeuro)/2;
	  $hauteur = $largeur*$hauteuro/$largeuro;
	}
	else if($largeuro*$hauteur < $largeur*$hauteuro) {
	  //$text_delta_x = ($largeur - $largeuro*$hauteur/$hauteuro)/2;
	  $largeur = $largeuro*$hauteur/$hauteuro;
	}
}

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
if($imxz != NULL) {
	ImageCopyResampled($im,$imxz,$posixe,$posigrek,0,0,$largeur,$hauteur,$largeuro,$hauteuro);
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

/* ### Affichage de l'image ### */
//if($extensaj=='.JPG' || $extensaj=='.JPEG'){
	imagejpeg($im); /* image compressée à un taux de 90% */
//}
//if($extensaj=='.GIF' || $extensaj=='.PNG'){
//	imagepng ($im);
//}


/* ### Destruction de la source ### */
imagedestroy ($im);
?>