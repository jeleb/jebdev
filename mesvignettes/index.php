<?
include "mesvignettes/common.php";
include "mesvignettes/config.php";

$initDir = getWithDefault($_GET, "dir", "");
$initFilter = getWithDefault($_GET, "filter", "");
$joinSubDir = getWithDefault($_GET, "joinSubDir", "0");

$initDir = parameterReplace($initDir);


?><!DOCTYPE html>
<html style="height:100%">
<head>
<TITLE>Mes photos</TITLE>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta http-equiv="bulletin-date" content="17/09/2004">
<!--meta content="height=device-height, width=device-width, initial-scale=1.0, user-scalable=yes, target-densitydpi=device-dpi" name="viewport" /-->

<!--meta content="height=device-height, width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=yes, target-densitydpi=device-dpi" name="viewport" / -->
<!-- meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" -->
<!--meta name="viewport" content="height=device-height" / -->

<script src="mesvignettes/common.js"></script>
<script language="javascript">


/* dimensions des vignettes des repertoires */
var imageDirRawWidth  = 270;
var imageDirRawHeight = 270;

/* nombre de colonnes pour les vignettes de répertoire quand on arrive pas à la calculer en fonction de la taille de l'écran*/
var nbDirColumns='3';

/* prefix de l'id des div qui contiennent les exifs complets */
var exifIdPrefix = "image_exif_all_";

/* taille des images telechargées (resizée en php) */
var imageRawHeight = 1100;

/* nombre d'images à charger en parrallèle */
var nbImageToLoadAtTheSameTime = 4;

/* redimensionne les images cote serveur */
/* en local ça va un peu plus vite sans redimensionner, par contre le  */
/* browser utilise 700Mo de + pour un répertoire de 100 photos O_o */
var redimensionneImages = true;

/* gestion de l'opacite quand la souris passe au dessus des boutons */
var opacityOver = "0.8";
var opacityOut  = "0.4";

/* préfixe à rajouter dans l'input des filtres pour qu'il soit appliqué aux exifs */
var exifFilterPrefix = "exif:";
 
/* timeout de mise a jour des multi-vignettes de répertoire */
var rollingDivTimeout = 5000; // en millisecondes
var rollingDivTransitionDelay = 50; // en millisecondes
var rollingDivTransitionNbSteps = 10;

/* offset à partir duquel on attend avant d'afficher les vignettes de répertoire */
var dontShowDirImgWhenOffScreenBy = 1000;

/* offset à partir duquel recommence à charger les images de répertoires quand on scroll vers le bas */
var restartDirImgLoadWhenBottomNear = 500;


function opacityOnMouseOver(e) {
	e.style.opacity = opacityOver;
}
function opacityOnMouseOut(e) {
	e.style.opacity = opacityOut;
}

function getNbDirColumns() {
	if(imageList.length == 0) {
		return getWindwWidth()/imageDirRawWidth - 1;
	}
	else {
		return nbDirColumns;
	}
}

function getCanonicalPath(path) {
	return path.replace(/\/\//g, "/");
}

function oneMoreImageLoaded(img, dirImgName) {
	if(scrollDirGoto >= 0) {
		var scroll = document.getElementById("scrollDir");
		if(scroll.scrollHeight >= scrollDirGoto+scroll.clientHeight) {
			scroll.scrollTop = scrollDirGoto;
			scrollDirGoto = -1;
		}
	}
	
	if(dirImgGoto!=null && dirImgName!=null) {
		if(dirImgGoto == dirImgName) {
			var scroll = document.getElementById("scrollDir");
			//scroll.scrollTop = scroll.scrollHeight - scroll.clientHeight;
			
			var top = 0;
			var elt = img;
			while(elt!=null && elt!=scroll) {
				top += elt.offsetTop;
				elt = elt.offsetParent;
			}
			top = top - 100;
			if(top < 0) {
				top = 0;
			}
			
			scroll.scrollTop = top;
			dirImgGoto = null;
		}
	}

	var nbSpan = document.getElementById("spanNbImg");
	var nbTotalSpan = document.getElementById("spanNbTotalImg");
	if( nbSpan.innerHTML != null && nbSpan.innerHTML != "" ) {
		nbSpan.innerHTML = "" + (parseInt(nbSpan.innerHTML)+1);
		if(nbSpan.innerHTML == nbTotalSpan.innerHTML) {
			nbSpan.innerHTML = "";
			nbTotalSpan.innerHTML = "";
			document.getElementById("spanNbImgSep").innerHTML = "";
		}
	}
}

function initImageLoadedCount() {
	var nbSpan = document.getElementById("spanNbImg");
	var nbTotalSpan = document.getElementById("spanNbTotalImg");
	nbSpan.innerHTML = "0";
	nbTotalSpan.innerHTML = ""+(imageList.length + dirList.length);
	document.getElementById("spanNbImgSep").innerHTML = " / ";

}

function onFilterKeyPress(evt) {
	if(evt.keyCode == 13) {
		loadDirEntries();
	}
	/* todo : if(evt.keyCode != 9) {
		loadDirEntries();
	}*/
}

function onFilterCancel() {
	document.getElementById("filterInput").value = "";
	loadDirEntries();
}


function loadDescription() {
	
	var message = { "dir":getCanonicalPath(currentDir) };
	var json = JSON.stringify(message);
	
	// exceptionnellement on travaille en synchrone
	var xhr = new XMLHttpRequest();
	xhr.open("POST","mesvignettes/getDescription.php",true);
	xhr.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');

	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4) {
			if (xhr.status != 200) {
				alert(xhr.status + " : " + xhr.statusText);
			}
			else {
				var reponse = JSON.parse(xhr.responseText);
				document.getElementById("descriptionTextArea").value = reponse["description"];
			}
		}
	}
	
	xhr.send(json);
}

function toggleDescription() {
	var textDiv  = document.getElementById("descriptionDiv");
	var textArea = document.getElementById("descriptionTextArea");
	
	var disp = textDiv.style.display;
	if(disp == "none") {
		textDiv.style.display="block";
		loadDescription();
	}
	else {
		textDiv.style.display="none";
		textArea.value = "";
	}
}

function cancelDescription(event) {
	var textArea = document.getElementById("descriptionTextArea");
	textArea.value = "";
	toggleDisplay("descriptionDiv");
}

function saveDescription() {
	var textArea = document.getElementById("descriptionTextArea");

	var message = { "dir":getCanonicalPath(currentDir), "newDescription": textArea.value };
	var json = JSON.stringify(message);
	
	// exceptionnellement on travaille en synchrone
	var xhr = new XMLHttpRequest();
	xhr.open("POST","mesvignettes/saveDescription.php",true);
	xhr.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');

	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4) {
			if (xhr.status != 200) {
				alert(xhr.status + " : " + xhr.statusText);
			}
			else {
				toggleDisplay("descriptionDiv");
			}
		}
	}
	
	xhr.send(json);
}

// raccourcis :
// v : va 'loin' à gauche
// f : va 'loin' à droite
// b : va 'loin' à gauche
// g : va 'loin' à droite
document.onkeydown = function(evt) {
    evt = evt || window.event;

	var c = String.fromCharCode(evt.keyCode).toLowerCase();
	if(evt.keyCode == 37 ||
		evt.keyCode == 40 ||
		c == 'b') {
		myScrollLeft();
	}
	else if(evt.keyCode == 39 ||
		evt.keyCode == 38 ||
		c == 'g') {
		myScrollRight();
	}
	else if(evt.keyCode == 34 ||
		c == 'v') {
		myScrollLeftDouble();
	}
	else if(evt.keyCode == 33 ||
		c == 'f') {
		myScrollRightDouble();
	}

};

function rollingDivStart() {
	
	beginAnimation(function (step) {
		if(step <=1) {
			return rollingDivTimeout;
		}

		var imagesToSwitch = [];
		var divList = document.getElementsByClassName("rollingDiv");
		for(var i=0; i!=divList.length; i++) {
			var current = 0;
			/*if(divList[i] == null || divList[i].childNodes == null) {
				continue;
			}*/
			var imgList = divList[i].childNodes;
			if(imgList!=null && imgList.length > 1) {
				for(current=0; current!=imgList.length; current++) {
					if(imgList[current].style.display == "block") {
						break;
					}
				}
				
				var toShow = current+1;
				if(toShow >= imgList.length) {
					toShow = 0;
				}
				
				//imgList[current].style.display = "none";
				//imgList[toShow].style.display = "block";
				imagesToSwitch.push({ "from":imgList[current],
									  "to":imgList[toShow] });
			}
		}
		
		beginAnimation(function(step) {
			if(step <= rollingDivTransitionNbSteps) {
				for(var i=0; i!=imagesToSwitch.length; i++) {
					imagesToSwitch[i].from.style.opacity = 1-step/rollingDivTransitionNbSteps;
				}
			}
			else if(step == rollingDivTransitionNbSteps+1) {
				for(var i=0; i!=imagesToSwitch.length; i++) {
					imagesToSwitch[i].from.style.display = "none";
					imagesToSwitch[i].from.style.opacity = 1;
					imagesToSwitch[i].to.style.opacity = 0;
					imagesToSwitch[i].to.style.display = "block";
				}
			}
			else if(step < 2*rollingDivTransitionNbSteps+1) {
				for(var i=0; i!=imagesToSwitch.length; i++) {
					imagesToSwitch[i].to.style.opacity = (step-rollingDivTransitionNbSteps+1)/rollingDivTransitionNbSteps;
				}
			}
			else {
				for(var i=0; i!=imagesToSwitch.length; i++) {
					imagesToSwitch[i].to.style.opacity = 1;
				}
				return 0;
			}
			return rollingDivTransitionDelay;
		});
		
		return rollingDivTimeout;
	});
}

var slideShow = false;
function toggleSlideShow() {
	if(slideShow) {
		slideShow = false;
	}
	else {
		slideShow = true;

		beginAnimation(function(step) {
			if(slideShow == false) {
				return 0;
			}
			
			if(myScrollRight() == false) {
				return 0;
			}
			
			return 2000;
		});
	}
}

var fullScreen = false;
function toggleFullScreen(id) {
	if(fullScreen) {
		exitFullScreen(id);
		fullScreen = false;
	}
	else {
		requestFullScreen(id);
		fullScreen = true;
	}
}

var currentDir = "<? echo $initDir; ?>";
//var previousDir = null;
var joinSubDir = "<? echo $joinSubDir; ?>";
// <? echo count($join_sub_dir_list) ?>

var joinSubDirList = [<? 
 if(count($join_sub_dir_list)!=0) { 
	echo "'".implode("','", $join_sub_dir_list)."'"; 
}
?>];
var scrollDirGoto = -1;
var dirImgGoto = null;

function backCurrentDir() {
	var newdir = currentDir;
	while(1) {
		var i = newdir.lastIndexOf("/");
		if(i <= 0) {
			newdir = "";
			break;
		}
		
		if(newdir.charAt(i-1) != "/") {
			newdir = newdir.substring(0, i);
			break;
		}
		
		newdir = newdir.substring(0, i-1);
	}
	
	var thePreviousDir = getCanonicalPath(currentDir);
	changeCurrentDir(newdir, false);
	dirImgGoto = thePreviousDir;
}

function getUrlParams(dir, joinSubDir) {
	return "dir="+dir+(joinSubDir=="1"?"&joinSubDir=1":"");
}

function changeCurrentDir(dir, first) {
	scrollDirGoto = -1;
	dirImgGoto = null;
	currentDir = dir;
	
	if(history.pushState) {
		var scroll = document.getElementById("scrollDir");
	
		//history.state.thescrollv = scroll.scrollTop;
		if(history.state) {
			history.replaceState({ thedir: history.state.thedir,  thejoinsubdir: history.state.thejoinsubdir, thescrollv:scroll.scrollTop},
				"photos", "index.php?"+getUrlParams(history.state.thedir, history.state.thejoinsubdir));
		}
		history.pushState({ thedir: dir,  thejoinsubdir: joinSubDir, thescrollv:-1},
			"photos", "index.php?"+getUrlParams(dir, null));
		loadDirEntries();
	}
	else {
		if(first) {
			loadDirEntries();
			return;
		}
	
		var newhref = window.location.href;
		var i = newhref.indexOf("?");
		if(i>0) {
			newhref = newhref.substr(0, i);
		}
		newhref = newhref + "?" + getUrlParams(dir, null);
		window.location.href = newhref;
	}
	
}

window.onpopstate = function(event) {
	if(event.state != null) {
		currentDir = event.state.thedir;
		joinSubDir = event.state.thejoinsubdir;
		scrollDirGoto = event.state.thescrollv;
		loadDirEntries();
	}
};

function showCurrentDir() {
	var divCurrentDir = document.getElementById("divCurrentDir");
	var style = "text-decoration:none;font-family:Verdana;font-size:100%;font-weight:bold;color:white;opacity:"+opacityOut+";";
	
	var html = "<a href=\"\" onclick=\"changeCurrentDir('', false)\" style=\""+style+"\" onmouseover=\"this.style.backgroundColor='black';opacityOnMouseOver(this);\" onmouseout=\"this.style.backgroundColor='transparent';opacityOnMouseOut(this);\">/</a> ";

	var changeDirName = "";
	var first = true;
	var splt = getCanonicalPath(currentDir).split("/");
	for(var i=0; i!=splt.length; i++) {
		if(first) {	
			first = false;
		}
		else {
			html = html + " <span style=\""+style+"\">/</span> ";
			changeDirName = changeDirName + "/";
		}
		changeDirName = changeDirName+splt[i];
		html = html 
			+ "<a href=\"\" onclick=\"changeCurrentDir('"+changeDirName+"', false);return false;\" style=\""
			+ style + "\" onmouseover=\"this.style.backgroundColor='black';opacityOnMouseOver(this);\" onmouseout=\"this.style.backgroundColor='transparent';opacityOnMouseOut(this);\">" 
			+ splt[i]+"</a>";
	}
	
	divCurrentDir.innerHTML = html;
}

function getImageHeightFromWindowSize() {
  return getWindwHeight()-50;
}

var imageList = [
];
var dirList = [
];

var imageToLoadList = [
];

function loadDirEntries() {
	var dir = currentDir;
	var dirCanonical = getCanonicalPath(dir);
	var currentFilter =	document.getElementById("filterInput").value;
	var localJoinSubDir = joinSubDir;
	if(localJoinSubDir !== "1" && joinSubDirList.indexOf(dirCanonical)>=0) {
		localJoinSubDir = "1";
	}
	
	var splt = currentFilter.split(" ");
	var filterDescription = "";
	var filterExif = "";
	
	for(var i=0; i!=splt.length; i++) {
		var str = splt[i];
		if(str.substring(0, exifFilterPrefix.length) == exifFilterPrefix) {
			filterExif = " " + str.substring(exifFilterPrefix.length).trim();
		}
		else {
			filterDescription = " " + str;
		}
	}
	
	filterDescription = filterDescription.trim();
	filterExif = filterExif.trim();
	
	var message = { "dir":dirCanonical, 
					"filterFileNameRegex":".*\\.(jpg|jpeg|png|gif)", 
					"filterDescription":filterDescription,
					"filterExif":filterExif,
					"joinSubDir":localJoinSubDir
					};
	var xhr = new XMLHttpRequest();
	xhr.open("POST", "mesvignettes/getDirEntries.php", true);
	xhr.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');

	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4) {
			var reponse = JSON.parse(xhr.responseText);
			
			imageList = [];
			dirList = [];
			imageToLoadList = [];
			
			var i=0;
			for(i=0; i!=reponse["fileEntries"].length; i++) {
				imageList[i] = { 
					name:(dir==null||dir=="" ? "":dir+"/")+reponse["fileEntries"][i]["name"],
					description:reponse["fileEntries"][i]["description"]
				};
			}
			for(i=0; i!=reponse["dirEntries"].length; i++) {
				var thedir = (dir==null||dir=="" ? "":dir+"/");
				thedir = thedir + (reponse["dirEntries"][i]["name"]).replace(/\//g, "//");
				dirList[i] = {
					name:thedir,
					description:reponse["dirEntries"][i]["description"],
					cover:reponse["dirEntries"][i]["cover"]
				}
			}
			
			sortImageList();
			sortDirList();
			
			refreshScreen();
		}
	}
	
	xhr.send(JSON.stringify(message));

}

function sortImageList() {
	imageList.sort();
}

function sortDirList() {
	dirList.sort( function (a, b) {
		var stra = a.name.split("/");
		var strb = b.name.split("/");
		for(var i=0; i<stra.length && i<strb.length; i++) {
			var cmp = compareNaturalOne(stra[i], strb[i]);
			if(cmp != 0) {
				return cmp;
			}
		}
	
		if(stra.length==strb.length) {
			return 0;
		}
		else {
			return stra.length<strb.length?-1:+1;
		}
	});
}

function compareNaturalOne(a, b) {
	var aDigit = isNaN(a) ? false : true;
	var bDigit = isNaN(b) ? false : true;
	var ret = 0;
		
	if(aDigit && bDigit) {
		ret = b.localeCompare(a);
	}
	else if(!aDigit && !bDigit) {
		ret = a.localeCompare(b);
	}
	else {
		ret = aDigit ? -1 : 1;
	}
	return ret;
}

function ajusteDir() {
	var div = document.getElementById("scrollDir");
	div.style.height = getImageHeightFromWindowSize() + "px";
}

function ajusteImages() {
	var trPhotos = document.getElementById("trImages");
	var height = getImageHeightFromWindowSize();

	var l = document.getElementsByClassName("myImage");
	for(var i=0; i!=l.length; i++) {
		l[i].style.height = height+"px";
	}
}

function getSdImageUrl(imgName) {
	return "mesvignettes/vignettes.php?sourceimg="+imgName+"&hauteur="+imageRawHeight;
}
function getSdImageName(imgName) {
	var i = imgName.lastIndexOf("/");
	if(i >= 0) {
		return "p_"+imgName.substring(i);
	}	
	return "p_"+imgName;
}

function showImageOne(imgName, imgDescription) {
	var trPhotos = document.getElementById("trImages");
	
	var td = document.createElement("TD");
	var a  = document.createElement("A");
	
	a.href = imgName;
	a.onclick = function(e) { toggleImageMenu(imgName, e.clientX, e.clientY, false);	return false; };
	a.ondblclick = function() { zoomImage(imgName); return false; };
	a.title = imgDescription;
	
	var url = null;
	if(redimensionneImages) {
		url = getSdImageUrl(imgName);
	}
	else {
		// TODO : variabiliser
		url = "mesvignettes/image.php?sourceimg="+imgName;
	}
	
	imageToLoadList.push( {
		"elt":a,
		"url":url,
		"height":"window",
		"className":"myImage",
		"status":null,
		"style":null,
		"isdir":false,
		"dirname":null
	} );
	

	td.appendChild(a);
	trPhotos.appendChild(td);
}

function removeImageToLoad(url) {
	for(var j=0; j!=imageToLoadList.length; j++) {
		if(imageToLoadList[j].url == url) {
			imageToLoadList.splice(j, 1);
			break;
		}
	}
	
}

function beginOneImageLoad() {
	for(var i=0; i!=imageToLoadList.length; i++) {
		if(imageToLoadList[i].status == null) {
			break;
		}
	}
	
	if(i >= imageToLoadList.length) {
		return;
	}
	
	var elt    = imageToLoadList[i].elt;
	var url    = imageToLoadList[i].url;
	var height = imageToLoadList[i].height;
	var className = imageToLoadList[i].className;
	var style = imageToLoadList[i].style;
	var isdir = imageToLoadList[i].isdir;
	var dirname = imageToLoadList[i].dirname;

	if(scrollDirGoto<0 && dirImgGoto==null &&
	   isdir && dontShowDirImgWhenOffScreenBy >= 0) {
		var scroll = document.getElementById("scrollDir");
		if(elt.offsetTop > scroll.scrollTop+scroll.clientHeight+dontShowDirImgWhenOffScreenBy) {
			return;
		}
	}
	
	imageToLoadList[i].status = "loading";

	var img = document.createElement("IMG");
	img.className = className;
	img.src = getCanonicalPath(url);
	if(style != null) {
		img.setAttribute("style", style);
	}
	if(height != null) {
		if(height == "window") {
			img.style.height = getImageHeightFromWindowSize()+"px";
		}
		else {
			img.style.height = height;
		}
	}
	img.onerror = img.onload = function() {
		oneMoreImageLoaded(img, dirname);
		removeImageToLoad(url);
		beginOneImageLoad();
	};
	
	elt.appendChild(img);
}

function showImageList() {
	
	for(var i=0; i!=imageList.length; i++) {
		showImageOne(imageList[i].name, imageList[i].description);
	}
	
}

function showImageDirOne(dirName, dirDescription, dirCover) {
	var tableDir = document.getElementById("tableDir");
	var dirNameCanonical = getCanonicalPath(dirName);
	
	var tr = null;
	if(tableDir.childNodes == null ||
		tableDir.childNodes.length==0) {
		tr = document.createElement("TR");
		tableDir.appendChild(tr);
	}
	else {
		var lastTr = tableDir.childNodes[tableDir.childNodes.length-1];

		var nbTd = 0;
		for(var i=0; i!=lastTr.childNodes.length && nbTd< getNbDirColumns(); i++) {
			if(lastTr.childNodes[i].tagName == "TD") {
				nbTd++;
			}
		}

		if(nbTd >= getNbDirColumns()) {
			tr = document.createElement("TR");
			tableDir.appendChild(tr);
		}
		else {
			tr = lastTr;
		}
	}
	
	
	var td = document.createElement("TD");
	var a = document.createElement("A");
	a.href = "";
	a.title = dirDescription;
	a.onclick = function() {
		changeCurrentDir(dirName, false);
		return false;
	}
	var stackDiv = document.createElement("Div");
	stackDiv.style.position = "relative";
	stackDiv.style.verticalAlign = "top";
	//stackDiv.setAttribute("style", "position:relative;vertical-align:top;-webkit-border-radius:20px;border-radius:20px;");
	
	imageToLoadList.push( {
		"elt":stackDiv,
		"url":"mesvignettes/vignettes_dir.php?dir="+dirNameCanonical+
				"&largeur="+imageDirRawWidth+
				"&hauteur="+imageDirRawHeight+
				(dirCover==null||dirCover.length==0?"":"&cover="+dirCover[0]),
		"height":null,
		"className":null,
		"status":null,
		"style": "-webkit-border-radius:20px;border-radius:20px;border-width:0;display:block;",
		"isdir":true,
		"dirname":dirNameCanonical
	} );
	
	if(dirCover!=null && dirCover.length>1) {
		stackDiv.className = "rollingDiv";
		for(var i=1; i<dirCover.length; i++) {
			imageToLoadList.push( {
				"elt":stackDiv,
				"url":"mesvignettes/vignettes_dir.php?dir="+dirNameCanonical+
						"&largeur="+imageDirRawWidth+
						"&hauteur="+imageDirRawHeight+
						"&cover="+dirCover[i],
				"height":null,
				"className":null,
				"status":null,
				"style": "-webkit-border-radius:20px;border-radius:20px;border-width:0;top:-"+(imageDirRawHeight/2)+"px;left:0px;position:absolute;display:none;", 
				"isdir":true,
				"dirname":dirNameCanonical
				} );
		}
	}
		
	a.appendChild(stackDiv);
	td.appendChild(a);
	tr.appendChild(td);
}


function showDirList() {
	var scrollDir = document.getElementById("scrollDir");
	var tableDir = document.getElementById("tableDir");
	if(imageList.length > 0) {
		scrollDir.style.width = "";
	}
	else {
		scrollDir.style.width = (getWindwWidth()-20)+"px";
	}
	
	for(var i=0; i!=dirList.length; i++) {
		showImageDirOne(dirList[i].name, dirList[i].description, dirList[i].cover);
	}
}

function showHideButtons() {
	var buttonBack = document.getElementById("buttonBack");
	var buttonsImageNavigation = document.getElementById("buttonsImageNavigation");
	
	if(currentDir == null || currentDir == "") {
		buttonBack.style.display = "none";
	}
	else {
		buttonBack.style.display = "block";
	}
	
	if(imageList.length == 0) {
		buttonsImageNavigation.style.display = "none";
	}
	else {
		buttonsImageNavigation.style.display = "block";
	}
	
}

var menuCurrentImage = null;
function toggleImageMenu(imgName, x, y, hideOnly) {
	var menu = document.getElementById("imgMenu");
	if(menu.style.display == "none") {
		if(!hideOnly) {
			menu.style.left = (x+1)+"px";
			menu.style.top = (y+1)+"px";
			menu.style.display = "block";
			menuCurrentImage = imgName;
			document.getElementById("imageMenuHdDownload").href = "mesvignettes/image.php?sourceimg="+imgName; // TODO : variabiliser + trouver un nom pour remplacer lors du download
			var dlLastSlash = imgName.lastIndexOf("/");
			var dlName = imgName;
			if(dlLastSlash > 0) {
				dlName = imgName.substr(dlLastSlash+1);
			}
			document.getElementById("imageMenuHdDownload").download = dlName; 
			document.getElementById("imageMenuSdDownload").href = getSdImageUrl(imgName);
			document.getElementById("imageMenuSdDownload").download = getSdImageName(imgName);
			
			opacityOnMouseOver(document.getElementById("imgReturn"));
			opacityOnMouseOver(document.getElementById("imgLeftDouble"));
			opacityOnMouseOver(document.getElementById("imgLeft"));
			opacityOnMouseOver(document.getElementById("imgRight"));
			opacityOnMouseOver(document.getElementById("imgRightDouble"));
			opacityOnMouseOver(document.getElementById("imgFullScreen"));
			opacityOnMouseOver(document.getElementById("imgSlideShow"));
		}
	}
	else {
	
		opacityOnMouseOut(document.getElementById("imgReturn"));
		opacityOnMouseOut(document.getElementById("imgLeftDouble"));
		opacityOnMouseOut(document.getElementById("imgLeft"));
		opacityOnMouseOut(document.getElementById("imgRight"));
		opacityOnMouseOut(document.getElementById("imgRightDouble"));
		opacityOnMouseOut(document.getElementById("imgFullScreen"));
		opacityOnMouseOut(document.getElementById("imgSlideShow"));
	
		menu.style.display = "none";
		menuCurrentImage = null;
	}
}
function hideImageMenu() {
	toggleImageMenu(null, 0, 0, true);
}

function refreshScreen() {
	clearScreen();
	showImageList();
	showDirList();
	showCurrentDir();
	initImageLoadedCount();
	showHideButtons();
	
	for(var i=0; i!=nbImageToLoadAtTheSameTime; i++) {
		beginOneImageLoad();
	}
}

function dirOnScroll() {
	var scroll = document.getElementById("scrollDir");

	if(scroll.scrollTop+scroll.clientHeight+restartDirImgLoadWhenBottomNear>=scroll.scrollHeight) {
		var nbToCreate = nbImageToLoadAtTheSameTime;
		
		for(var i=0; i!=imageToLoadList.length; i++) {
			if(imageToLoadList[i].status != null) {
				nbToCreate --;
				if(nbToCreate <= 0) {
					return;
				}
			}
		}
		
		for(var i=0; i!=nbToCreate; i++) {
			beginOneImageLoad();
		}
	}
}

function clearScreen() {
	var tableDir = document.getElementById("tableDir");
	var trPhotos = document.getElementById("trImages");
	document.getElementById("scrollableDiv").scrollLeft = 0;
	
	// erroeur dans ie
	//tableDir.innerHTML = "<tr></tr>";
	// a remplacer par :
	var newTbody = document.createElement('tbody');
	newTbody.id = "tableDir";
	tableDir.parentNode.replaceChild(newTbody, tableDir)
	
	while(trPhotos.childNodes.length > 3) {
		trPhotos.removeChild(trPhotos.childNodes[3]);
	}
	
}

/* animation pour deplacer un scroll horizontal jusqu'a une position */
var myScrollHorizontalTotalSteps = -1;
var myScrollHorizontalGoTo = -1;
function myScrollHorizontalTo(div, destLeft) {
	hideImageMenu();

	var intervalMs = 1/25*1000;
	var startLeft = div.scrollLeft;
	myScrollHorizontalGoTo = destLeft;

	if(myScrollHorizontalTotalSteps == -1) {
		myScrollHorizontalTotalSteps = 8;
		
		beginAnimation(function(step) {
			if(step >= myScrollHorizontalTotalSteps) {
				div.scrollLeft = myScrollHorizontalGoTo ;
				myScrollHorizontalTotalSteps = -1;
				myScrollHorizontalGoTo = -1;
				return 0;
			}
			
			div.scrollLeft = startLeft + (myScrollHorizontalGoTo - startLeft)*step/myScrollHorizontalTotalSteps;
			return intervalMs;
		});
	}
	else {
		//myScrollHorizontalTotalSteps += 8;
	}
	

}

function myScrollWheel(e) {	
	if(e.deltaY < 0) {
		myScrollLeft();
	}
	else {
		myScrollRight();
	}
}

function myScrollLeft() {
	var tr = document.getElementById("trImages");
	var scrollableDiv = document.getElementById("scrollableDiv");
	var tdList = tr.childNodes;
	var left = 0;
	
	var trWidth = tr.clientWidth;
	var screenWidth = scrollableDiv.clientWidth;
	var currentPos = (myScrollHorizontalGoTo==-1 ? scrollableDiv.scrollLeft : myScrollHorizontalGoTo) + screenWidth/2;

	var previousTdPosN1 = -1;
	var previousTdPosN2 = -1;
	
	for(var i=0; i!=tdList.length; i++) {
		var td = tdList[i];
		if(td.tagName != "TD") {
			continue;
		}
		
		var width = td.clientWidth;

		var tdPos = left + width/2;
		if(tdPos > currentPos+1) {
			myScrollHorizontalTo(scrollableDiv, previousTdPosN2 - screenWidth/2)
			break;
		}

		previousTdPosN2 = previousTdPosN1;
		previousTdPosN1 = tdPos;
		left += width+2;
	}
	
}



function myScrollRight() {
	var found = false;
	var tr = document.getElementById("trImages");
	var scrollableDiv = document.getElementById("scrollableDiv");
	var tdList = tr.childNodes;
	var left = 0;
	
	var trWidth = tr.clientWidth;
	var screenWidth = scrollableDiv.clientWidth;
	var currentPos = (myScrollHorizontalGoTo==-1 ? scrollableDiv.scrollLeft : myScrollHorizontalGoTo) + screenWidth/2;
	
	for(var i=0; i!=tdList.length; i++) {
		var td = tdList[i];
		if(td.tagName != "TD") {
			continue;
		}
		
		var width = td.clientWidth;

		var tdPos = left + width/2;
		if(tdPos > currentPos+1) {
			myScrollHorizontalTo(scrollableDiv, tdPos - screenWidth/2)
			found = true;
			break;
		}

		left += width+2;
	}

	return found;
}


function myScrollLeftDouble() {
	var div = document.getElementById("scrollableDiv");
	myScrollHorizontalTo(scrollableDiv, (myScrollHorizontalGoTo==-1 ? scrollableDiv.scrollLeft : myScrollHorizontalGoTo) - 6000);

}
function myScrollRightDouble() {
	var div = document.getElementById("scrollableDiv");
	myScrollHorizontalTo(scrollableDiv, (myScrollHorizontalGoTo==-1 ? scrollableDiv.scrollLeft : myScrollHorizontalGoTo) + 6000);
}

function zoomImage(imgName) {
// TODO : variabiliser
	window.open("mesvignettes/image.php?sourceimg="+imgName);
}

function bodyOnLoad() {
	ajusteDir();
	document.getElementById("filterInput").value = "<? echo $initFilter; ?>";
	
	changeCurrentDir(currentDir, true);
	rollingDivStart();
}

window.onresize = function(event) {
	ajusteImages();
	ajusteDir();
};

</script>
</head>
<body style="background-color:#000000;height:97%:hidden" onload="bodyOnLoad();" >
<div id="globalFullScreen" style="height:100%;">
<div id="scrollableDiv" style="overflow-x: scroll;;overflow-y:hidden;scrollbar-face-color: black;height:100%;" onwheel="myScrollWheel(event);">
<table  border="0" style="padding:0;border-spacing:4;background-color:#000000;height:100%;">
<tbody style="height:100%;">
	<tr id="trImages" style="height:100%;">
	<td>
	
	<!-- menu de navigation des dossiers -->
	<div id="scrollDir" style="overflow-y:auto;overflow-x:hidden" onscroll="dirOnScroll();">
	<table border="0" style="padding:0;border-spacing:0;background-color:#000000;">
		<tbody id="tableDir" >
		</tbody>
	</table>
	</td>
	</div>
	

	</tr>
</tbody>
</table>
<div style="position:fixed;top:10px;left:10px;z-index:100;">
<div style="position:relative;text-align:center;float:left;">
	<div id="divCurrentDir"></div>

	<span id="spanNbImg">0</span><span id="spanNbImgSep"> / </span><span id="spanNbTotalImg">0</span><br/>
	<b>
	<a id="buttonBack" href="" style="color:white;font-family:arial;size:4;" onclick="backCurrentDir();return false;">
		<img id="imgReturn" src="mesvignettes/return2.png" style="opacity:0.4;width:120px;height:40px;transform:scaleY(-1);" onmouseover="opacityOnMouseOver(this);" onmouseout="opacityOnMouseOut(this);"/>
	</a>
	</b>

	<div id="buttonsImageNavigation">
		<a href="" onclick="myScrollLeftDouble();return false;" style="color:white;font-family:arial;size:12;"
			><img id="imgLeftDouble" src="mesvignettes/left_double.png" style="height:30px;width:30px;opacity:0.3;" onmouseover="opacityOnMouseOver(this);" onmouseout="this.style.opacity=0.3;"/></a>
		<a href="" onclick="myScrollLeft();return false;" style="color:white;font-family:arial;size:12;"
			><img id="imgLeft" src="mesvignettes/left.png" style="height:30px;width:30px;opacity:0.3;" onmouseover="opacityOnMouseOver(this);" onmouseout="opacityOnMouseOut(this);;"/></a>
		<a href="" onclick="myScrollRight();return false;" style="color:white;font-family:arial;size:12;"
			><img id="imgRight" src="mesvignettes/left.png" style="height:30px;width:30px;opacity:0.3;transform:scaleX(-1);-ms-transform:scaleX(-1);" onmouseover="opacityOnMouseOver(this);" onmouseout="opacityOnMouseOut(this);"/></a>
		<a href="" onclick="myScrollRightDouble();return false;" style="color:white;font-family:arial;size:12;"
			><img id="imgRightDouble" src="mesvignettes/left_double.png" style="height:30px;width:30px;opacity:0.3;transform:scaleX(-1);-ms-transform:scaleX(-1);" onmouseover="opacityOnMouseOver(this);" onmouseout="opacityOnMouseOut(this);"/></a>
	</div>

	<a href="" onclick="toggleFullScreen('globalFullScreen');return false;">
		<img id="imgFullScreen" style="border-width:0;height:30px;width:45px;middle;opacity:0.3" src="mesvignettes/fullscreen.png" onmouseover="opacityOnMouseOver(this);" onmouseout="opacityOnMouseOut(this);" />
	</a>
	<a href="" onclick="toggleSlideShow();return false;">
		<img id="imgSlideShow" style="border-width:0;height:30px;width:30px;middle;opacity:0.3" src="mesvignettes/slide.png" onmouseover="opacityOnMouseOver(this);" onmouseout="opacityOnMouseOut(this);" />
	</a>
	
	<div id="divFilters" style="display:block;">
		<a href="" onclick="toggleExifAll();return false;" style="font:Arial;color:grey;font-size:8px;">EXIF</a>
		&nbsp;
		<a href="" onclick="toggleDescription();return false;" style="font:Arial;color:grey;font-size:8px;" title="">DESCR.</a>
		<br/>

		<input id="filterInput" type="text" style="vertical-align: middle;width:100px;font-size:12px;background-color:white;opacity:0.3" onmouseover="opacityOnMouseOver(this);" onmouseout="opacityOnMouseOut(this);" onkeypress="onFilterKeyPress(event);" onkeydown="event.stopPropagation();"/>
		<img src="mesvignettes/close.png" style="vertical-align: middle;opacity:0.3" onmouseover="opacityOnMouseOver(this);" onmouseout="opacityOnMouseOut(this);" onclick="onFilterCancel();return false;"/>
		<br/>

	</div>
	
</div>
<div id="descriptionDiv" style="display:none;">
	<textarea id="descriptionTextArea" rows="10" cols="80" style="opacity:0.8;" onkeydown="event.stopPropagation();"/></textarea>
	<button onclick="saveDescription();">enregistrer</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button onclick="cancelDescription();">annuler</button>
</div>
</div>
</div>
<div id="imgMenu" style="position:absolute;top:1px;left:1px;display:none;">
<a id="imageMenuSdDownload" href="" onclick="hideImageMenu();" download="">
	<img src="mesvignettes/dl_sd.png" style="height:50px"/>
</a>
<br/>
<!--a href="" onclick="return false;"-->
<a id="imageMenuHdDownload" href="" onclick="hideImageMenu();" download>
	<img src="mesvignettes/dl_hd.png" style="height:50px"/>
</a>
<br/>
<a href="" onclick="zoomImage(menuCurrentImage);return false;">
	<img src="mesvignettes/zoom_in.png" style="height:50px"/>
</a>
</div>
</div>
</body>
</html>