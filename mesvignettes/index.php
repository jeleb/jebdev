<?

/* largeur MAX des miniatures  */
$larimage='2000';

/* hauteur MAX des miniatures */
$hautimage='500';

/* Epaisseur du cadre du tableau d'affichage des vignettes */
$epaiscadretable='2';

/* Couleur du cadre du tableau d'affichage des vignettes (valeur h�xa) */
$coulcadretable='000000';

/* Affichage d'un cadre autour des vignettes ou non, 1 pour oui, 0 pour non */
$cadrak='0';

/* $redimvoz = Redimension � la vol�e (n�cesite GD2), 1 pour oui, 0 pour non */
/* Le redimensionnement � la vol�e n�cesite beaucoup de resources serveur mais permet de consid�rablement acc�lerer l'affichage des vignettes */
$redimvoz='1';

/* dimensions des vignettes des repertoires */
/* */
$vignette_rep_max_hauteur='300';
$vignette_rep_max_largeur='300';

/* nombre de colonnes pour les vignettes de r�pertoire */
$nb_dir_columns='3';


?><!DOCTYPE html>
<html>
<head>
<TITLE>Mes photos</TITLE>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta http-equiv="bulletin-date" content="17/09/2004">
<meta content="height=device-height, width=device-width, initial-scale=1.0, user-scalable=yes, target-densitydpi=device-dpi" name="viewport" / -->
<!--meta content="height=device-height, width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=yes, target-densitydpi=device-dpi" name="viewport" / -->
<!-- meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" -->
<!--meta name="viewport" content="height=device-height" / -->

<script language="javascript">
function getWindwHeight() {
  var myHeight = 0;
  if( typeof( window.innerWidth ) == 'number' ) {
    myHeight = window.innerHeight;
  } else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
    myHeight = document.documentElement.clientHeight;
  } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
    myHeight = document.body.clientHeight;
  }
  return myHeight;
}

function gotourl(myurlmemo, myurl) {
	var str = ""+window.location;
	var i = str.lastIndexOf('?');
	str = str.substring(0, i);
	i = str.lastIndexOf('/');
	str = str.substring(0, i+1);
	
	window.location = str+"index.php?urlancien="+myurlmemo+"&url="+myurl+"&hautscreen="+ getWindwHeight(); //screen.height;
}

// raccourcis :
// b : va 'loin' � gauche
// g : va 'loin' � droite
document.onkeydown = function(evt) {
    evt = evt || window.event;

	var c = String.fromCharCode(evt.keyCode).toLowerCase();
	if(c == 'b') {
		window.scrollBy(-3000,0);
	}
	else if(c == 'g') {
		window.scrollBy(3000,0);
	}

};

</script>
</head>
<body text="#000000" link="#000000" alink="#000000" vlink="#000000" bgcolor="#000000" leftmargin="10" marginwidth="5" topmargin="10" marginheight="5">
<div align="center"><?

/* Fonction d'affichage des photos miniatures */
function affichimgs($nblignes,$larimage,$hautimage,$nbcols,$url,$urlancien,$redimvoz,$cadrak,$epaiscadretable,$coulcadretable){

	if (isset($_REQUEST['start'])){
		$start = $_REQUEST['start'];
	}
	if(is_null($start)){
		$start = 0;
	}

	if($url!=''){
		$urlt=$url.'/';
		$dossier = opendir($urlt);
	}
	else{
		$dossier = opendir('.');
	}


	$images = array();
	while($fichier = readdir($dossier)){
		$extent=substr($fichier,strrpos($fichier,"."));
		$extensaj=strtoupper($extent);
		if($extensaj=='.JPG' || $extensaj=='.JPEG' || $extensaj=='.GIF' || $extensaj=='.PNG'){
			array_push($images, $fichier);
		}
		$extensaj='';
	}
	closedir($dossier);
	$nb = sizeof($images);
	
	if($start==''){
		$start=0;
	}
	$i=$start;
	$k=0;
	$stopboucle='no';

	?><table border="0" cellpadding="0" cellspacing="0" bgcolor="#<? echo $coulcadretable; ?>">
	<tr>
	<td bgcolor="#<? echo $coulcadretable; ?>" colspan="2"><table border="0" cellpadding="4" cellspacing="<? echo $epaiscadretable; ?>" width="100%"><?

	while($stopboucle=='no'){

		/* ### Extraction de l'extension ### */
		$imagesource=$urlt.$images[$i];
		$extent=substr($imagesource,strrpos($imagesource,"."));
		$extensaj=strtoupper($extent);

		/* ### Arret de la boucle si plus rien ### */
		if($extensaj==''){
			$stopboucle='ok';
		}
		
		/* ### Arret de la boucle si nb images = nb d�fini pour une page ### */
		if($i >= $nb){
			$stopboucle='ok';
		}


		/* ### Nouveau test pour v�rifier que seules les images seront affich�es ### */
		if($extensaj=='.JPG' || $extensaj=='.JPEG' || $extensaj=='.GIF' || $extensaj=='.PNG'){



			/* ### Extraction des dimensions de l'image ### */
			$sizeimgo=getimagesize($imagesource);
			$imglargo=$sizeimgo[0];
			$imghauto=$sizeimgo[1];


			/* ### Recalcul des dimensions MAX des vignettes ### */
			if ($imglargo>$larimage){
				$imghautoz=$imghauto*$larimage/$imglargo;
				$imghautoz=round($imghautoz);
				$imglargoz=$larimage;
			}
			else{
				$imglargoz=$imglargo;
				$imghautoz=$imghauto;
			}
			if ($imghautoz>$hautimage){
				$imglargoz=$imglargoz*$hautimage/$imghautoz;
				$imglargoz=round($imglargoz);
				$imghautoz=$hautimage;
			}

			/* Affichage de l'image ### */
			?><td bgcolor="#000000" valign="middle" align="center"><a href="#" onclick="javascript:window.open('<? echo $imagesource; ?>');" title="Cliquez pour agrandir l\'image"><?


			/* ### Redimensionnement � la vol�e ### */
			if ($redimvoz=='1'){
				?><img src="vignettes.php?cadrak=<? echo $cadrak; ?>&extensaj=<? echo $extensaj; ?>&sourceimg=<? echo $imagesource; ?>&largeuro=<? echo $imglargo; ?>&hauteuro=<? echo $imghauto; ?>&largeur=<? echo $imglargoz; ?>&hauteur=<? echo $imghautoz; ?>" border="0"><?
			}
			else{
				?><img src="<? echo $imagesource; ?>" border="0" width="<? echo $imglargoz; ?>" height="<? echo $imghautoz; ?>"><?
			}

			$k++;
		}
		$i++;
	}
	?></table></td>
	</tr>
	</table><?
}

// fonction d'affichage des vignettes de r�pertoire
function displayDir($urlmemo, $dirTab, $numDirName) {
	global $cadrak, $vignette_rep_max_largeur, $vignette_rep_max_hauteur, $nb_dir_columns;

	$i = 0;
	$nb = sizeof($dirTab);
	for($j=0; $j < $nb; $j++) {
		$theDir = $dirTab[$nb-$j-1];

		if($numDirName == is_numeric(substr($theDir, 0, 1))) {
			continue;
		}

		$urlmemot = $urlmemo;
		if($urlmemo != "") {
			$urlmemot = $urlmemo.'/';
		}
		
		if($i == 0) {
			echo("<tr>");
		}
		
		?>
		<td bgcolor="#000000" style="text-align:center;vertical-align:middle;" ><font face="arial" size="2"><a href="#" onclick="gotourl('<? echo $urlmemo; ?>','<?echo $urlmemot.$theDir; ?>')"><?
		?>
		<img src="vignettes_dir.php?cadrak=<? echo $cadrak; ?>&dir=<? echo $urlmemot.$theDir; ?>&largeur=<? echo $vignette_rep_max_largeur; ?>&hauteur=<? echo $vignette_rep_max_hauteur; ?>"/>
		</a></font></td>
		<?
		
		$i++;
		if($i == $nb_dir_columns) {
			echo("</tr>");
			$i = 0;
		}
	}
}

/* R�cup�ration des variables */
$hautscreen=$_GET[hautscreen];
$imglargoz=$_GET[imglargo];
$imghautoz=$_GET[imghauto];
$sourceimg=$_GET[sourceimg];
$url=$_GET[url];
$urlancien=$_GET[urlancien];
if($hautscreen != '') {
	$hautimage = $hautscreen-70;
}

/* Si on clique sur une image alors affichage en taille r�elle */
if($sourceimg!=''){
	?><table border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF" width="100%">
	<tr>
	<td><img src="<? echo $sourceimg; ?>" border="0" width="<? echo $imglargo; ?>" height="<? echo $imghauto; ?>"></td>
	</tr>
	</table><?
}
else{
	?><table border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
	<td valign="top" align="left"><?


	/* Affichage du menu de navigation des dossiers */
	?><table border="0" cellpadding="0" cellspacing="0" bgcolor="#000000">
	<tr>
	<td bgcolor="#000000"><table border="0" cellpadding="4" cellspacing="1">
	<?

	
	/* Mise en place d'un arret pour emp�cher de redescendre plus bas que de raison ;) */
	if($url!=''){
		?><tr>
		<td bgcolor="#000000"><b><a href="index.php?url=<?
		echo $urlancien;
		?>" style="color:white;font-family:arial;size:4;"><-&nbsp;retour</a></b></td>
		</tr><?
	}
	

	/* Initialisation des variables dossiers */
	if($url!=''){
		$url=$url;
		$urlmemo=$url;

	}
	else{
		$url='.';
		$urlmemo='';
	}
	$AllFiles = array();
	$AllDir = array();

	//echo $url;

	/* Affichage des vignette de dossier */
	if ($handle = opendir($url.'/')) {
		
		$dirTab = array();
		
	    while (false !== ($file = readdir($handle))) {
			//echo $file;
			
	        if ((is_dir($url.'/'.$file)) && $file != "." && $file != "..") {
				array_push($dirTab, $file);
	        }
	    }
	    closedir($handle);
		
		// affichage en premier des repertoires commencant par un chiffre
		displayDir($urlmemo, $dirTab, 0);
		// ensuite on affichage les autres r�pertoires
		displayDir($urlmemo, $dirTab, 1);

	}
	?></table></td>
	</tr>
	</table><br><?
	/* Fin d'affichage du menu de navigation des dossiers */


	?></td>
	<td valign="top" align="center"><?


	/* Appel de la fonction pour l'affichage des images */
	affichimgs($nblignes,$larimage,$hautimage,$nbcols,$url,$urlancien,$redimvoz,$cadrak,$epaiscadretable,$coulcadretable);

	?></td>
	</tr><?

	?>
	</table><?

}


?></div>
</body>
</html>