<?
include "mesvignettes/common.php";

$initDir = getWithDefault($_GET, "dir", "");
$initFilter = getWithDefault($_GET, "filter", "");

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
var imageDirRawWidth  = 300;
var imageDirRawHeight = 300;

/* nombre de colonnes pour les vignettes de répertoire */
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

function oneMoreImageLoaded() {
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
}

function onFilterCancel() {
	document.getElementById("filterInput").value = "";
	loadDirEntries();
}

/* 



function toggleExifAll() {
	var divArray = document.getElementsByTagName("DIV");
	var prefix = "<  ? echo $exifIdPrefix; ?>";
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





*/

function loadDescription() {
	
	var message = { "dir":currentDir };
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

	var message = { "dir":currentDir, "newDescription": textArea.value };
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

function backCurrentDir() {
	var i = currentDir.lastIndexOf("/");
	if(i > 0) {
		changeCurrentDir(currentDir.substring(0, i));
	}
	else {
		changeCurrentDir("");
	}
}

function changeCurrentDir(dir) {
	currentDir = dir;
	loadDirEntries();
}

function showCurrentDir() {
	var divCurrentDir = document.getElementById("divCurrentDir");
	var style = "text-decoration:none;font-family:Verdana;font-size:100%;font-weight:bold;color:white;opacity:"+opacityOut+";";
	
	var html = "<a href=\"\" onclick=\"changeCurrentDir('')\" style=\""+style+"\" onmouseover=\"this.style.backgroundColor='black';opacityOnMouseOver(this);\" onmouseout=\"this.style.backgroundColor='transparent';opacityOnMouseOut(this);\">/</a> ";

	var changeDirName = "";
	var first = true;
	var splt = currentDir.split("/");
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
			+ "<a href=\"\" onclick=\"changeCurrentDir('"+changeDirName+"');return false;\" style=\""
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
	var currentFilter =	document.getElementById("filterInput").value;
	
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
	
	var message = { "dir":dir, 
					"filterFileNameRegex":".*\\.(jpg|jpeg|png|gif)", 
					"filterDescription":filterDescription,
					"filterExif":filterExif
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
				dirList[i] = {
					name:(dir==null||dir=="" ? "":dir+"/")+reponse["dirEntries"][i]["name"],
					description:reponse["dirEntries"][i]["description"]
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
	//var truc = a.name.substr(a.name.lastIndexOf("/")+1);
		var aDigit = isNaN(a.name.substr(a.name.lastIndexOf("/")+1)) ? false : true;
		var bDigit = isNaN(b.name.substr(b.name.lastIndexOf("/")+1)) ? false : true;
		var ret = 0;
		
		if(aDigit && bDigit) {
			ret = b.name.localeCompare(a.name);
		}
		else if(!aDigit && !bDigit) {
			ret = a.name.localeCompare(b.name);
		}
		else {
			ret = aDigit ? -1 : 1;
		}
		
		return ret;
	});
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

function showImageOne(imgName, imgDescription) {
	var trPhotos = document.getElementById("trImages");
	
	var td = document.createElement("TD");
	var a  = document.createElement("A");
	
	a.href = imgName;
	a.onclick = function() { return false; };
	a.ondblclick = function() { window.open(imgName); return false; };
	a.title = imgDescription;
	
	var url = null;
	if(redimensionneImages) {
		url = "mesvignettes/vignettes.php?sourceimg="+imgName+"&hauteur="+imageRawHeight;
	}
	else {
		url = imgName;
	}
	
	imageToLoadList.push( {
		"elt":a,
		"url":url,
		"height":"window",
		"className":"myImage",
		"status":null
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
	
	imageToLoadList[i].status = "loading";
	var elt    = imageToLoadList[i].elt;
	var url    = imageToLoadList[i].url;
	var height = imageToLoadList[i].height;
	var className = imageToLoadList[i].className;

	var img = document.createElement("IMG");
	img.className = className;
	img.src = url;
	if(height != null) {
		if(height == "window") {
			img.style.height = getImageHeightFromWindowSize()+"px";
		}
		else {
			img.style.height = height;
		}
	}
	img.onload = function() {
		oneMoreImageLoaded();
		removeImageToLoad(url);
		beginOneImageLoad();
	};
	img.onerror = function() {
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

function showImageDirOne(dirName, dirDescription) {
	var tableDir = document.getElementById("tableDir");
	
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
		changeCurrentDir(dirName);
		return false;
	}
	
	imageToLoadList.push( {
		"elt":a,
		"url":"mesvignettes/vignettes_dir.php?dir="+dirName+"&largeur="+imageDirRawWidth+"&hauteur="+imageDirRawHeight,
		"height":null,
		"className":null,
		"status":null
	} );
	
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
		showImageDirOne(dirList[i].name, dirList[i].description);
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

function clearScreen() {
	var tableDir = document.getElementById("tableDir");
	var trPhotos = document.getElementById("trImages");
	document.getElementById("scrollableDiv").scrollLeft = 0;
	
	tableDir.innerHTML = "<tr></tr>";
	
	while(trPhotos.childNodes.length > 3) {
		trPhotos.removeChild(trPhotos.childNodes[3]);
	}
	
}

/* animation pour deplacer un scroll horizontal jusqu'a une position */
var myScrollHorizontalTotalSteps = -1;
var myScrollHorizontalGoTo = -1;
function myScrollHorizontalTo(div, destLeft) {
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
		myScrollHorizontalTotalSteps += 8;
	}
	

}

function myScrollWheel(e) {	
	if(e.deltaY > 0) {
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
			break;
		}

		left += width+2;
	}
}


function myScrollLeftDouble() {
	var div = document.getElementById("scrollableDiv");
	myScrollHorizontalTo(scrollableDiv, (myScrollHorizontalGoTo==-1 ? scrollableDiv.scrollLeft : myScrollHorizontalGoTo) - 6000);

}
function myScrollRightDouble() {
	var div = document.getElementById("scrollableDiv");
	myScrollHorizontalTo(scrollableDiv, (myScrollHorizontalGoTo==-1 ? scrollableDiv.scrollLeft : myScrollHorizontalGoTo) + 6000);
}


function bodyOnLoad() {
	ajusteDir();
	document.getElementById("filterInput").value = "<? echo $initFilter; ?>";
	
	loadDirEntries();
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
	<div id="scrollDir" style="overflow-y:auto;overflow-x:hidden">
	<table id="tableDir" border="0" style="padding:0;border-spacing:0;background-color:#000000;">
		<tr></tr>
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
		<img src="mesvignettes/return2.png" style="opacity:0.4;width:120px;height:40px;transform:scaleY(-1);" onmouseover="opacityOnMouseOver(this);" onmouseout="opacityOnMouseOut(this);"/>
	</a>
	</b>

	<div id="buttonsImageNavigation">
		<a href="" onclick="myScrollLeftDouble();return false;" style="color:white;font-family:arial;size:12;"
			><img src="mesvignettes/left_double.png" style="height:30px;width:30px;opacity:0.3;" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;"/></a>
		<a href="" onclick="myScrollLeft();return false;" style="color:white;font-family:arial;size:12;"
			><img src="mesvignettes/left.png" style="height:30px;width:30px;opacity:0.3;" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;"/></a>
		<a href="" onclick="myScrollRight();return false;" style="color:white;font-family:arial;size:12;"
			><img src="mesvignettes/left.png" style="height:30px;width:30px;opacity:0.3;transform:scaleX(-1);" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;"/></a>
		<a href="" onclick="myScrollRightDouble();return false;" style="color:white;font-family:arial;size:12;"
			><img src="mesvignettes/left_double.png" style="height:30px;width:30px;opacity:0.3;transform:scaleX(-1);" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;"/></a>
	</div>

	<a href="" onclick="toggleFullScreen('globalFullScreen');return false;">
		<img style="height:30px;width:45px;middle;opacity:0.3" src="mesvignettes/fullscreen.png" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" />
	</a>
	
	<div id="divFilters" style="display:block;">
		<a href="" onclick="toggleExifAll();return false;" style="font:Arial;color:grey;font-size:8px;">EXIF</a>
		&nbsp;
		<a href="" onclick="toggleDescription();return false;" style="font:Arial;color:grey;font-size:8px;" title="">DESCR.</a>
		<br/>

		<input id="filterInput" type="text" style="vertical-align: middle;width:100px;font-size:12px;background-color:white;opacity:0.3" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" onkeypress="onFilterKeyPress(event);" onkeydown="event.stopPropagation();"/>
		<img src="mesvignettes/close.png" style="vertical-align: middle;opacity:0.3" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" onclick="onFilterCancel();return false;"/>
		<br/>

	</div>
	
</div>
<div id="descriptionDiv" style="display:none;">
	<textarea id="descriptionTextArea" rows="10" cols="80" style="opacity:0.8;" onkeydown="event.stopPropagation();"/></textarea>
	<button onclick="saveDescription();">enregistrer</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button onclick="cancelDescription();">annuler</button>
</div>
</div>
</div>
</div>
</body>
</html>