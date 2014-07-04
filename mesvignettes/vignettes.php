<?
$sourceimg=strtoupper($_GET[sourceimg]);
$largeur=strtoupper($_GET[largeur]);
$hauteur=strtoupper($_GET[hauteur]);
$extensaj=strtoupper($_GET[extensaj]);
$largeuro=strtoupper($_GET[largeuro]);
$hauteuro=strtoupper($_GET[hauteuro]);
$cadrak=strtoupper($_GET[cadrak]);

// Set the content-type
header('Content-Type: image/jpeg');

header('Pragma: public');
header('Cache-Control: max-age=86400');
header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

/* ### Type d'image ### */
if($extensaj=='.JPG' || $extensaj=='.JPEG'){
	header("Content-Type: image/JPEG");
	$imxz=@imagecreatefromjpeg($sourceimg);
}
if($extensaj=='.GIF'){
	header("Content-Type: image/PNG");
	$imxz=@imagecreatefromgif($sourceimg);
}
if($extensaj=='.PNG'){
	header("Content-Type: image/PNG");
	$imxz=@imagecreatefrompng($sourceimg);
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