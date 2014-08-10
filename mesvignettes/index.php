<?
include "common.php";

// fucking useless comment again
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

/* prefix de l'id des div qui contiennent les exifs complets */
$exif_id_prefix = "image_exif_all_";


/* description à afficher pour le répertoire courant */
//$descriptionTitle = "";
$currentDirDescription = null;

/* Récupération des variables */
$hautscreen=$_GET["hautscreen"];
$imglargoz=$_GET["imglargo"];
$imghautoz=$_GET["imghauto"];
$sourceimg=$_GET["sourceimg"];
$url=$_GET["url"];
//$urlancien=$_GET["urlancien"];
$filtre=$_GET["filtre"];
$filtreLC = "";
if($filtre != null) {
	$filtreLC = strtolower($filtre);
}
$filtreDescription=$_GET["filtreDescr"];
$filtreDescriptionLC = "";
if($filtreDescription != null) {
	$filtreDescriptionLC = strtolower($filtreDescription);
}
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

function backUrl(theurl) {
	var i = theurl.lastIndexOf("/");
	if(i > 0) {
		return theurl.substr(0, i);
	}
	else {
		return "";
	}
}

function onFilterKeyPress(evt) {
	if(evt.keyCode == 13) {
		var myfilter = document.getElementById("filtreInput").value;
		var myfilterDescription = document.getElementById("filtreDescriptionInput").value;
		gotourl('<?echo $url; ?>', myfilter, myfilterDescription);
	}
}

function toggleExifAll() {
	var divArray = document.getElementsByTagName("DIV");
	var prefix = "<? echo $exif_id_prefix; ?>";
	var toggleState = -1;
	for(var i=0; i!=divArray.length; i++) { 
		var mondiv = divArray[i];
		if(mondiv.id.substr(0, prefix.length) == prefix) {
			if(toggleState == -1) {
				toggleState = (mondiv.style.display == "none" ? 0 : 1);
			}
			
			mondiv.style.display = (toggleState==0 ? "block" : "none");
		}
	} 

}

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

function gotourl(myurl, myfiltre, myfiltredescription) {
	var str = ""+window.location;
	var i = str.lastIndexOf('?');
	str = str.substring(0, i);
	i = str.lastIndexOf('/');
	str = str.substring(0, i+1);
	if(myfiltre != null && myfiltre=="") {
		myfiltre = null
	}
	if(myfiltredescription != null && myfiltredescription=="") {
		myfiltredescription = null
	}
	
	window.location = str+"index.php?url="+myurl+
		(myfiltre!=null ? "&filtre="+myfiltre : "") +
		(myfiltredescription!=null ? "&filtreDescr="+myfiltredescription : "") +
		"&hautscreen="+ getWindwHeight(); //screen.height;
}

var backupDescription = null;
function toggleDescription() {
	var textDiv  = document.getElementById("descriptionDiv");
	var textArea = document.getElementById("descriptionTextArea");
	
	if(backupDescription == null) {
		backupDescription = textArea.value;
		if(backupDescription == null) {
			backupDescription = "";
		}
	}
	
	var disp = textDiv.style.display;
	if(disp == "none") {
		textDiv.style.display="block";
	}
	else {
		textDiv.style.display="none";
	}
}

function cancelDescription(event) {
	var textArea = document.getElementById("descriptionTextArea");
	if(backupDescription != null) {
		textArea.value = backupDescription;
	}
	else {
		textArea.value = "";
	}
	toggleDescription();
}

function saveDescription() {
	var textArea = document.getElementById("descriptionTextArea");

	var message = { "dir":"2014/20140711", "newDescription": textArea.value };
	var json = JSON.stringify(message);
	//alert(json);
	
	// exceptionnellement on travaille en synchrone
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("POST","saveDescription.php",false);
	xmlhttp.send(json);
	if (xmlhttp.status != 200) {
		alert(xmlhttp.status + " : " + xmlhttp.statusText);
	}
	backupDescription = textArea.value;
	toggleDescription();
	
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

/* construction du title pour les images à partir des données d'exif */
function computeExifTitle($exif, $eol) {
	$model = "";
	$focalLength = "";
	$aperture = "";
	$expoTime = "";
	$isoSpeed = "";
	$userComment = "";
	$keyWords = "";

	if(isset($exif["IFD0"])) {
		if(isset($exif["IFD0"]["Comments"])) {
			$userComment = utf8_encode(preg_replace('/[\x00-\x1F]/', '', $exif["IFD0"]["Comments"]));
		}
		if(isset($exif["IFD0"]["Model"])) {
			$model = $exif["IFD0"]["Model"];
		}
		if(isset($exif["IFD0"]["Keywords"])) {
			$keyWords = utf8_encode(preg_replace('/[\x00-\x1F]/', '', $exif["IFD0"]["Keywords"]));
		}
	}
	
	if(isset($exif["COMPUTED"])) {
		if(isset($exif["COMPUTED"]["ApertureFNumber"])) {
			$aperture = $exif["COMPUTED"]["ApertureFNumber"];
		}
	}
	
	if($exif["EXIF"]) {
		if(isset($exif["EXIF"]["FocalLength"])) {
			$focalLength = $exif["EXIF"]["FocalLength"];
			$diviserInstr = strpos($focalLength, "/");
			if ($diviserInstr !== false) {
				$d = substr($focalLength, 0, $diviserInstr);
				$D = substr($focalLength, $diviserInstr+1);
				$focalLength = floor((intval($d)/intval($D))*100)/100;
			}
			
			$focalLength = $focalLength . "mm";
		}
		if(isset($exif["EXIF"]["ExposureTime"])) {
			$expoTime = $exif["EXIF"]["ExposureTime"] . "s";
		}
		if(isset($exif["EXIF"]["ISOSpeedRatings"])) {
			$isoSpeed = $exif["EXIF"]["ISOSpeedRatings"] . "ISO";
		}
	}
	
	$title = $model . "\n" . 
			$aperture . " " . $expoTime . " " . $isoSpeed . $eol .
			$focalLength . "\n" .
			$userComment . "\n" .
			$keyWords;
			
	return $title;
}

function exifMatch($filtre, $exif) {

	if(isset($exif["IFD0"])) {
		if(isset($exif["IFD0"]["Comments"])) {
			$userComment = strtolower(utf8_encode(preg_replace('/[\x00-\x1F]/', '', $exif["IFD0"]["Comments"])));
			
			if(strpos($userComment, $filtre) !== false) {
				return true;
			}
		}
		if(isset($exif["IFD0"]["Model"])) {
			$model = strtolower($exif["IFD0"]["Model"]);

			if(strpos($model, $filtre) !== false) {
				return true;
			}
		}
		if(isset($exif["IFD0"]["Keywords"])) {
			$keyWords = strtolower(utf8_encode(preg_replace('/[\x00-\x1F]/', '', $exif["IFD0"]["Keywords"])));
			
			if(strpos($keyWords, $filtre) !== false) {
				return true;
			}
		}
	}

	
	return false;
}

function computeExifAll($exif, $eol) {
	$str = "";
	
	foreach ($exif as $key => $section) {
		foreach ($section as $name => $val) {
			$str = $str . $key . "." . $name . ":" . $val . $eol;
		}
	}
	
	return $str;
}

/* Fonction d'affichage des photos miniatures */
function affichimgs($larimage,$hautimage,$url,$redimvoz,$cadrak,$epaiscadretable,$coulcadretable){
global $nbImg, $dont_show_image_prefix, $exif_id_prefix, $filtre, $filtreLC, $filtreDescription, $filtreDescriptionLC/*, $descriptionTitle*/, $currentDirDescription;
	$start = 0;

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

	$dirDescr = new dirDescription($url);
	if($dirDescr->exists()) {
		$dirDescr->read();
		//$descriptionTitle = $dirDescr->getDescription();
		$currentDirDescription = $dirDescr;
	}

	$images = array();
	while($fichier = readdir($dossier)){
		$extent=substr($fichier,strrpos($fichier,"."));
		$extensaj=strtoupper($extent);
		$showimg = true;
		
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
		$imageBaseFileName = $images[$i];
		$imagesource=$urlt.$imageBaseFileName;
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
			
			/* filtre par description */
			$hideImg = false;
			if($filtreDescription != null && $filtreDescription != "") {
				if($dirDescr->containsForImg($imageBaseFileName, $filtreDescriptionLC)==FALSE) {
					$hideImg = true;
				}
			}
			
			/* fitlre par Exif */
			$exif_title = "no Exif tag found";
			$exif = exif_read_data($imagesource, 0, true);
			if( ! ($exif === false)) {
				$exif_title = computeExifTitle($exif, "\n");
			}

			if($hideImg == false && $filtre != null && $filtre != "") {
				if(! exifMatch($filtreLC, $exif)) {
					$hideImg = true;
				}
			}
			
			if($hideImg == false) {
				/* Affichage de l'image */
				$nbImg ++;
				?>
				<td bgcolor="#000000" valign="middle" align="center">
				<div style="position:relative" onmousedown="return false">
				<a href="<? echo $imagesource; ?>" onclick="return false;" ondblclick="javascript:window.open('<? echo $imagesource; ?>');return false;" title="<? echo $imagesource."\n\n".$exif_title ?>">
				<?

				/* Redimensionnement à la volée */
				if ($redimvoz=='1'){
					?><img src="vignettes.php?cadrak=<? echo $cadrak; ?>&extensaj=<? echo $extensaj; ?>&sourceimg=<? echo $imagesource; ?>&largeuro=<? echo $imglargo; ?>&hauteuro=<? echo $imghauto; ?>&largeur=<? echo $imglargoz; ?>&hauteur=<? echo $imghautoz; ?>" border="0" onload="oneMoreImageLoaded();"><?
				}
				else{
					?><img src="<? echo $imagesource; ?>" border="0" width="<? echo $imglargoz; ?>" height="<? echo $imghautoz; ?>"><?
				}

				?>
				<div id="<? echo $exif_id_prefix; ?><? echo $k; ?>" style="display:none;height:<? echo $imghautoz - 20 ?>px;overflow-y:scroll;z-index:10;position:absolute;left:10px;top:10px;font-size:12px;font:Arial;">
				<?
					echo computeExifAll($exif, "<br/>");
				?>
				</div>
				</a>
				</td>
				<!-- /td -->
				<?
				
				$k++;
			}
		}
		$i++;
	}
	?></table></td>
	</tr>
	</table><?
}

// regarde si un sous-repertoire contient des sous-sous-répertoire ou des images avec des exif qui matchent le filtre
function dirHasMatchingExifImage($theDir, $filtre) {
	$ret = false;
	
	if ($handle = opendir($theDir)) {
		while (false !== ($file = readdir($handle))) {
			if($file == "." || $file == "..") {
				continue;
			}
		
			$fileName = $theDir.$file;

			if (is_dir($fileName)) {
				$ret = true;
				break;
			}
			
			$exif = exif_read_data($fileName, 0, true);
			if( ! ($exif === false)) {
				if(exifMatch($filtre, $exif)) {
					$ret = true;
					break;
				}
			}

			
		}
		
		closedir($handle);
	}

	return $ret;
}

// classe pour gérer les descriptions par image
class DirDescriptionPerImage {
	var $firstImage;
	var $lastImage;
	var $description;
	
	function DirDescriptionPerimage($aFirstImage, $aLastImage) {
		$this->description = "";
		$this->firstImage = $aFirstImage;
		$this->lastImage = $aLastImage;
	}
	
	function addDescription($line) {
		$this->description = $this->description.$line;
	}
	
}

// classe pour gérer les fichiers de description
class DirDescription {
	var $fileName;
	var $globalDescription;
	var $perImageDescriptionArray;
	
	function DirDescription($dir) {
		global $descriptionFileName;
		$this->fileName = $dir."/".$descriptionFileName;
		$this->perImageDescriptionArray = array();
		$this->globalDescription = "";
	}
	
	function exists() {
		return file_exists($this->fileName);
	}
	
	function read() {
		// $this->globalDescription = file_get_contents($this->fileName);
		$perImageCurrent = null;
		$h = fopen($this->fileName,"r");

		while(! feof($h))  {
			$line = trim(fgets($h));
			
			if(substr($line, 0, 1) == "*") {
				$separatorPos = strpos($line, ":", 1);
				$first = null;
				$last  = null;
				if($separatorPos === FALSE) {
					$first = substr($line, 1);
					$last  = $first;
				}
				else {
					$first = substr($line, 1, $separatorPos-1);
					$last  = substr($line, $separatorPos+1);
				}
				$perImageCurrent = new DirDescriptionPerImage($first, $last);
				array_push($this->perImageDescriptionArray, $perImageCurrent);
			}
			else {
				if($perImageCurrent != null) {
					$perImageCurrent->addDescription($line . "\n");
				}
				else {
						$this->globalDescription = $this->globalDescription . $line . "\n";
				}
			}
		}

		fclose($h);
	}
	
	function contains($text) {
		if(strpos(strtolower($this->globalDescription), $text) !== FALSE) {
			return TRUE;
		}
		
		foreach($this->perImageDescriptionArray as $descr) {
			if(strpos(strtolower($descr->description), $text) !== FALSE) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	function containsForImg($img, $text) {
		if(strpos(strtolower($this->globalDescription), $text) !== FALSE ) {
			return TRUE;
		}
	
		$imgLC = strtolower($img);
		foreach($this->perImageDescriptionArray as $descr) {
			if( strcmp($imgLC, strtolower($descr->firstImage)) >= 0 && 
				strcmp($imgLC, strtolower($descr->lastImage)) <= 0 &&
				strpos(strtolower($descr->description), $text) !== FALSE ) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	function getDescription() {
		$str = $this->globalDescription /*. "\n"*/;
		
		foreach($this->perImageDescriptionArray as $descr) {
			if($descr->lastImage == null || $descr->lastImage===$descr->firstImage) {
				$str = $str . "*" . $descr->firstImage . "\n";
			}
			else {
				$str = $str . "*" . $descr->firstImage . ":" . $descr->lastImage . ":\n";
			}
			$str = $str . $descr->description /*. "\n"*/;
		}
		
		return $str;
	}
}

// fonction d'affichage des vignettes de répertoire
function displayDir($urlmemo, $dirTab, $currentDirName, $numDirName) {
	global $cadrak, $vignette_rep_max_largeur, $vignette_rep_max_hauteur, $nb_dir_columns, $filtre, $filtreLC, $filtreDescription, $filtreDescriptionLC, $currentDirDescription;

	$i = 0;
	$nb = sizeof($dirTab);
	for($j=0; $j < $nb; $j++) {
		$theDir = $dirTab[$nb-$j-1];

		if($numDirName == is_numeric(substr($theDir, 0, 1))) {
			continue;
		}
		
		if($filtre != null && $filtre != "" && !dirHasMatchingExifImage($currentDirName.$theDir."/", $filtreLC)) {
			continue;
		}
		
		$title = $theDir;
		$dirDescr = new DirDescription($currentDirName.$theDir);
		if($dirDescr->exists()) {
			$dirDescr->read();
			
			$title = $dirDescr->getDescription();
		}

		if($filtreDescription != null && $filtreDescription != "" && $dirDescr->contains($filtreDescriptionLC)==FALSE) {
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
		<td bgcolor="#000000" style="text-align:center;vertical-align:middle;" ><font face="arial" size="2">
		<a href="#" onclick="gotourl('<?echo $urlmemot.$theDir; ?>', '<? echo $filtre; ?>', '<? echo $filtreDescription; ?>');return false;"><?
		?>
		<img title="<? echo $title; ?>" src="vignettes_dir.php?cadrak=<? echo $cadrak; ?>&dir=<? echo $urlmemot.$theDir; ?>&largeur=<? echo $vignette_rep_max_largeur; ?>&hauteur=<? echo $vignette_rep_max_hauteur; ?>"/>
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
		displayDir($urlmemo, $dirTab, $url.'/', 0);
		// ensuite on affichage les autres répertoires
		displayDir($urlmemo, $dirTab, $url.'/', 1);

	}
	?></table></td>
	</tr>
	</table><br><?
	/* Fin d'affichage du menu de navigation des dossiers */


	?></td>
	<td valign="top" align="center"><?


	/* Appel de la fonction pour l'affichage des images */
	affichimgs($larimage,$hautimage,$url,$redimvoz,$cadrak,$epaiscadretable,$coulcadretable);

	?></td>
	</tr><?

	?>
	</table>
	
	<div style="position:fixed;top:10px;left:10px;z-index:100;">
	<div style="position:relative;text-align:center;float:left;">
	<?
	//ici en haut à gauche, le menu qui reste meme quand on scroll
	if($nbImg > 0) {
		echo "<span id=\"spanNbImg\">0</span><span id=\"spanNbImgSep\"> / </span><span id=\"spanNbTotalImg\">" . $nbImg . "</span><br/>";
	}

	if($url != '' && $url != '.'){
		?>
		<b>
		<? /* <a href="index.php?url=<? echo $urlancien; ?>" style="color:white;font-family:arial;size:4;"> */
		?>
		<a href="" style="color:white;font-family:arial;size:4;" onclick="gotourl(backUrl('<? echo $url; ?>'), '<? echo $filtre; ?>', '<? echo $filtreDescription; ?>');return false;">
		<img src="mesvignettes_return.png" style="opacity:0.4;width:100px;height:50px;transform:scaleY(-1);" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.4;"/>
		</a></b><br/>
		<?
	}
	if($nbImg > 0) {
	?>
	
	<a href="" onclick="window.scrollBy(-3000,0);return false;" style="color:white;font-family:arial;size:12;"><img src="mesvignettes_left.png" style="opacity:0.3;" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;"/></a>
	&nbsp;
	<a href="" onclick="window.scrollBy(3000,0);return false;" style="color:white;font-family:arial;size:12;"><img src="mesvignettes_left.png" style="opacity:0.3;transform:scaleX(-1);" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;"/></a>
	<br/>
	<a href="" onclick="toggleExifAll();return false;" style="font:Arial;color:grey;font-size:8px;">EXIF</a>
	<br/>
	<?
	}

	?>
	<input id="filtreInput" type="text" style="vertical-align: middle;width:100px;font-size:12px;background-color:white;opacity:0.3" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" onkeypress="onFilterKeyPress(event);" value="<? echo $filtre; ?>" />
	<img src="mesvignettes_close.png" style="vertical-align: middle;opacity:0.3" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" onclick="gotourl('<?echo $url; ?>', null, '<? echo $filtreDescription; ?>');return false;"/>
	<br/>
	<div><a href="" onclick="toggleDescription();return false;" style="font:Arial;color:grey;font-size:8px;" title="<? if($currentDirDescription!=null) { echo $currentDirDescription->getDescription(); } ?>">DESCR.</a></div>
	<input id="filtreDescriptionInput" type="text" style="vertical-align: middle;width:100px;font-size:12px;background-color:white;opacity:0.3" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" onkeypress="onFilterKeyPress(event);" value="<? echo $filtreDescription; ?>" />
	<img src="mesvignettes_close.png" style="vertical-align: middle;opacity:0.3" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" onclick="gotourl('<?echo $url; ?>', '<? echo $filtre; ?>', null);return false;"/>
	<br/>
	</div>
	<div id="descriptionDiv" style="display:none;">
	<textarea id="descriptionTextArea" rows="10" cols="80" style="opacity:0.8;"/><?
	if($currentDirDescription != null) {
		echo $currentDirDescription->getDescription(); 
	}
?></textarea>
	<button onclick="saveDescription();">enregistrer</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button onclick="cancelDescription();">annuler</button>
	</div>
	</div>
	</div>

	<?

}


?></div>
</body>
</html>