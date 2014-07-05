<?

/* largeur MAX des miniatures  */
$larimage='2000';

/* hauteur MAX des miniatures */
$hautimage='500';

/* Epaisseur du cadre du tableau d'affichage des vignettes */
$epaiscadretable='2';

/* Couleur du cadre du tableau d'affichage des vignettes (valeur héxa) */
$coulcadretable='000000';

/* Affichage d'un cadre autour des vignettes ou non, 1 pour oui, 0 pour non */
$cadrak='0';

/* $redimvoz = Redimension à la volée (nécesite GD2), 1 pour oui, 0 pour non */
/* Le redimensionnement à la volée nécesite beaucoup de resources serveur mais permet de considérablement accélerer l'affichage des vignettes */
$redimvoz='1';

/* dimensions des vignettes des repertoires */
/* */
$vignette_rep_max_hauteur='300';
$vignette_rep_max_largeur='300';

/* nombre de colonnes pour les vignettes de répertoire */
$nb_dir_columns='3';

/* prefixes des images qu'il ne faut pas afficher (celles de mesvignettes principalement) */
$dont_show_image_prefix = "mesvignettes_";

/* Récupération des variables */
$hautscreen=$_GET[hautscreen];
$imglargoz=$_GET[imglargo];
$imghautoz=$_GET[imghauto];
$sourceimg=$_GET[sourceimg];
$url=$_GET[url];
$urlancien=$_GET[urlancien];
if($hautscreen != '') {
	$hautimage = $hautscreen-70;
}


$nbImg = 0;

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

function oneMoreImageLoaded() {
	var nbSpan = document.getElementById("spanNbImg");
	var nbTotalSpan = document.getElementById("spanNbTotalImg");
	nbSpan.innerHTML = "" + (parseInt(nbSpan.innerHTML)+1);
	if(nbSpan.innerHTML == nbTotalSpan.innerHTML) {
		nbSpan.innerHTML = "";
		nbTotalSpan.innerHTML = "";
		document.getElementById("spanNbImgSep").innerHTML = "";
	}
}

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
// b : va 'loin' à gauche
// g : va 'loin' à droite
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
global $nbImg, $dont_show_image_prefix;

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
		
		if(substr($fichier, 0, strlen($dont_show_image_prefix)) != $dont_show_image_prefix ) {
			if($extensaj=='.JPG' || $extensaj=='.JPEG' || $extensaj=='.GIF' || $extensaj=='.PNG'){
				array_push($images, $fichier);
			}
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
		
		/* ### Arret de la boucle si nb images = nb défini pour une page ### */
		if($i >= $nb){
			$stopboucle='ok';
		}


		/* ### Nouveau test pour vérifier que seules les images seront affichées ### */
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
			$nbImg ++;
			?><td bgcolor="#000000" valign="middle" align="center"><a href="#" onclick="return false;" ondblclick="javascript:window.open('<? echo $imagesource; ?>');return false;" title="Cliquez pour agrandir l\'image"><?


			/* ### Redimensionnement à la volée ### */
			if ($redimvoz=='1'){
				?><img src="vignettes.php?cadrak=<? echo $cadrak; ?>&extensaj=<? echo $extensaj; ?>&sourceimg=<? echo $imagesource; ?>&largeuro=<? echo $imglargo; ?>&hauteuro=<? echo $imghauto; ?>&largeur=<? echo $imglargoz; ?>&hauteur=<? echo $imghautoz; ?>" border="0" onload="oneMoreImageLoaded();"><?
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

// fonction d'affichage des vignettes de répertoire
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
		<td bgcolor="#000000" style="text-align:center;vertical-align:middle;" ><font face="arial" size="2"><a href="#" onclick="gotourl('<? echo $urlmemo; ?>','<?echo $urlmemot.$theDir; ?>');"><?
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


/* Si on clique sur une image alors affichage en taille réelle */
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

	
	/* Mise en place d'un arret pour empècher de redescendre plus bas que de raison ;) */
	/*if($url!=''){
		?><tr>
		<td bgcolor="#000000"><b><a href="index.php?url=<?
		echo $urlancien;
		?>" style="color:white;font-family:arial;size:4;"><-&nbsp;retour</a></b></td>
		</tr><?
	}*/
	

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
		// ensuite on affichage les autres répertoires
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
	</table>
	
	<div style="position:fixed;top:10px;left:10px">
	<?
	//ici en haut à gauche, le menu qui reste meme quand on scroll
	if($nbImg > 0) {
		echo "<span id=\"spanNbImg\">0</span><span id=\"spanNbImgSep\"> / </span><span id=\"spanNbTotalImg\">" . $nbImg . "</span><br/>";
	}

	if($url != '' && $url != '.'){
		?>
		<b><a href="index.php?url=<?
		echo $urlancien;
		?>" style="color:white;font-family:arial;size:4;"><img src="mesvignettes_return.png" style="opacity:0.4;width:100px;height:50px;transform:scaleY(-1);" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.4;"/></a></b><br/>
		<?
	}
	if($nbImg > 0) {
	?>
	
	<b><a href="" onclick="window.scrollBy(-3000,0);return false;" style="color:white;font-family:arial;size:12;"><img src="mesvignettes_left.png" style="opacity:0.4;" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.4;"/></a></b>
	&nbsp;
	<b><a href="" onclick="window.scrollBy(3000,0);return false;" style="color:white;font-family:arial;size:12;"><img src="mesvignettes_left.png" style="opacity:0.4;transform:scaleX(-1);" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.4;"/></a></b>
	<br/>
	<?
	}

	?>
	
	</div>

	<?

}


?></div>
</body>
</html>