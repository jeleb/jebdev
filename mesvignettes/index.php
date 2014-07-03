<?
/*
###############################################
Album photos express V 1.2
Philippe HALICKI
philippe@exconcept.com
www.exconcept.com
Script écrit le 17/09/2004

Supporte les formats JPG, GIF et PNG.

Cet album photos permet de lister toutes les images de répertoires, de remonter et de descendre à volonté dans l'arborescence jusqu'au point d'arret corrspondant à l'emplacement du script.
Voici la liste des éléments que vous pouvez définir :

- Nombre de lignes et de colonnes par page
- Largeur MAX des vignettes
- Hauteur MAX des vignettes
- Affichage d'un cadre autour des vignettes ou non
- Redimensionnement à la volée ou non
- Epaisseur du cadre du tableau d'affichage des vignettes 
- Couleur du cadre du tableau d'affichage des vignettes 


Installation : Placez les fichiers index.php et vignettes.php dans le répertoire racine contenant les images. C'est tout !

L'appel se fait par index.php
###############################################
*/


/* ############################# */
/* ### Variables utilisateur ### */
/* ############################# */


/* ### $nbimages = nombre de lignes à afficher ### */
//$nblignes='5';


/* ### $nbcols = nombre de colonnes à afficher ### */
//$nbcols='5';


/* ### $larimage = largeur MAX des miniatures ### */
$larimage='2000';


/* ### $larimage = hauteur MAX des miniatures ### */
$hautimage='500';


/* ### $epaiscadretable = Epaisseur du cadre du tableau d'affichage des vignettes ### */
$epaiscadretable='2';


/* ### $epaiscadretable = Couleur du cadre du tableau d'affichage des vignettes (valeur héxa) ### */
//$coulcadretable='FADF72';
$coulcadretable='000000';


/* ### $cadrak = Affichage d'un cadre autour des vignettes ou non, 1 pour oui, 0 pour non ### */
$cadrak='0';


/* ### $redimvoz = Redimension à la volée (nécesite GD2), 1 pour oui, 0 pour non ### */
/* ### Le redimensionnement à la volée nécesite beaucoup de resources serveur mais permet de considérablement accélerer l'affichage des vignettes ### */
$redimvoz='1';

/* ### dimensions des vignettes des repertoires */
/* ### */
$vignette_rep_max_hauteur='300';
$vignette_rep_max_largeur='300';

/* ### nombre de colonnes pour les vignettes de répertoire */
/* ### */
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
  //var myWidth = 0, myHeight = 0;
  var myHeight = 0;
  if( typeof( window.innerWidth ) == 'number' ) {
    //Non-IE
    //myWidth = window.innerWidth;
    myHeight = window.innerHeight;
  } else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
    //IE 6+ in 'standards compliant mode'
    //myWidth = document.documentElement.clientWidth;
    myHeight = document.documentElement.clientHeight;
  } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
    //IE 4 compatible
    //myWidth = document.body.clientWidth;
    myHeight = document.body.clientHeight;
  }
  // window.alert( 'Width = ' + myWidth );
  // window.alert( 'Height = ' + myHeight );
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

/* ### Fonction d'affichage de l'album ### */
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


		/* ### Enregistrement de la liste du noms des images dans la table $images ### */
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

	/* ### Affichage de la table ### */
	?><table border="0" cellpadding="0" cellspacing="0" bgcolor="#<? echo $coulcadretable; ?>">
	<tr>
	<td bgcolor="#<? echo $coulcadretable; ?>" colspan="2"><table border="0" cellpadding="4" cellspacing="<? echo $epaiscadretable; ?>" width="100%"><?

	/* ### Début de boucle ### */
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


			/* ### Poids de l'image ### */
			$taille=filesize($imagesource);
			$taille=$taille/1024;
			$taille=round ($taille);


			/* ### Affichage de l'image ### */
			/*?><td bgcolor="#000000" valign="middle" align="center"><a href="#" onclick="javascript:window.open('index.php?imglargo=<? echo $imglargo; ?>&imghauto=<? echo $imghauto; ?>&sourceimg=<? echo $imagesource; ?>','ZOOM<? echo $k; ?>','toolbar=0,location=0,directories=0,status=0,resizable=1,copyhistory=0,scrollbars=0,menuBar=0,width=<? echo $imglargo+5; ?>,height=<? echo $imghauto+5; ?>');" title="Cliquez pour agrandir l\'image"><?
			*/
			?><td bgcolor="#000000" valign="middle" align="center"><a href="#" onclick="javascript:window.open('<? echo $imagesource; ?>');" title="Cliquez pour agrandir l\'image"><?


			/* ### Redimension à la volée ### */
			if ($redimvoz=='1'){
				?><img src="vignettes.php?cadrak=<? echo $cadrak; ?>&extensaj=<? echo $extensaj; ?>&sourceimg=<? echo $imagesource; ?>&largeuro=<? echo $imglargo; ?>&hauteuro=<? echo $imghauto; ?>&largeur=<? echo $imglargoz; ?>&hauteur=<? echo $imghautoz; ?>" border="0"><?
			}
			else{
				?><img src="<? echo $imagesource; ?>" border="0" width="<? echo $imglargoz; ?>" height="<? echo $imghautoz; ?>"><?
			}


			/* ### Affichage des infos sur l'image ### */
			/*?></a><br>
			<font face="arial" size="1">L=<? 
			echo $imglargo; 
			?> X H=<? 
			echo $imghauto; 
			?> <?
			echo $taille;
			?>Ko.<br><?
			echo $imagesource;
			?></font></td><?*/


			/* ### Fermeture de la ligne ### */
			$k++;
		}
		$i++;
	}
	?></table></td>
	</tr>
	</table><?
}

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

/* ### Récupération des variables ### */
$hautscreen=$_GET[hautscreen];
$imglargoz=$_GET[imglargo];
$imghautoz=$_GET[imghauto];
$sourceimg=$_GET[sourceimg];
$url=$_GET[url];
$urlancien=$_GET[urlancien];
if($hautscreen != '') {
	$hautimage = $hautscreen-70;
}

/* ### Si on clique sur une image alors affichage en taille réelle ### */
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


	/* ### Affichage du menu de navigation des dossiers ### */
	?><table border="0" cellpadding="0" cellspacing="0" bgcolor="#000000">
	<tr>
	<td bgcolor="#000000"><table border="0" cellpadding="4" cellspacing="1">
	<!--tr>
	<td bgcolor="#CB9900"><font face="arial" size="2" color="#FFFFFF"><b>Dossiers :</b></font></td>
	</tr--><?

	
	/* ### Mise en place d'un arret pour empècher de redescendre plus bas que de raison ;) ### */
	if($url!=''){
		?><tr>
		<td bgcolor="#000000"><b><a href="index.php?url=<?
		echo $urlancien;
		?>" style="color:white;font-family:arial;size:4;"><-&nbsp;retour</a></b></td>
		</tr><?
	}
	

	/* ### Initialisation des variables dossiers ### */
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

	/* ### Affichage des dossiers ### */
	if ($handle = opendir($url.'/')) {
		
		$dirTab = array();
		
	    while (false !== ($file = readdir($handle))) {
			//echo $file;
			
	        if ((is_dir($url.'/'.$file)) && $file != "." && $file != "..") {
				array_push($dirTab, $file);
	        }
	    }
	    closedir($handle);
		
		displayDir($urlmemo, $dirTab, 0);
		displayDir($urlmemo, $dirTab, 1);

	}
	?></table></td>
	</tr>
	</table><br><?
	/* ### Fin d'affichage du menu de navigation des dossiers ### */


	?></td>
	<td valign="top" align="center"><?


	/* ### Appel de la fonction pour l'affichage des images ### */
	affichimgs($nblignes,$larimage,$hautimage,$nbcols,$url,$urlancien,$redimvoz,$cadrak,$epaiscadretable,$coulcadretable);

	?></td>
	</tr><?
	

	/* ### Affichage des crédits ### */
	?>
	</table><?

}


?></div>
</body>
</html>