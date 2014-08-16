<?
$initDir = "";
if(array_key_exists("dir", $_GET)) {
	$initDir=$_GET["dir"];
}
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

/* gestion de l'opacite quand la souris passe au dessus des boutons */
var opacityOver = "0.8";
var opacityOut  = "0.4";

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

/* todo
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
	var prefix = "<? echo $exifIdPrefix; ?>";
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
	xmlhttp.open("POST","mesvignettes/saveDescription.php",false);
	xmlhttp.send(json);
	if (xmlhttp.status != 200) {
		alert(xmlhttp.status + " : " + xmlhttp.statusText);
	}
	backupDescription = textArea.value;
	toggleDescription();
	
}
*/
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

var currentDir = "<? echo $initDir ?>";

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
	loadDirEntries(currentDir);
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

function loadDirEntries(dir) {
	var message = { "dir":dir, filterFileNameRegex:".*\\.(jpg|jpeg|png|gif)" };
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
				imageList[i] = (dir==null||dir==""?"":dir+"/")+reponse["fileEntries"][i];
			}
			for(i=0; i!=reponse["dirEntries"].length; i++) {
				dirList[i] = (dir==null||dir==""?"":dir+"/")+reponse["dirEntries"][i];
			}
			
			sortImageList();
			sortDirList();
			
			refreshScreen();
		}
	}
	
	// send the collected data as JSON
	xhr.send(JSON.stringify(message));

}

function sortImageList() {
	imageList.sort();
}

function sortDirList() {
	dirList.sort( function (a, b) {
	var truc = a.substr(a.lastIndexOf("/")+1);
		var aDigit = isNaN(a.substr(a.lastIndexOf("/")+1)) ? false : true;
		var bDigit = isNaN(b.substr(b.lastIndexOf("/")+1)) ? false : true;
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
	});
}

function ajusteDir() {
	var div = document.getElementById("scrollDir");
	div.style.height = getImageHeightFromWindowSize() + "px";
}

function ajusteImages() {
	var trPhotos = document.getElementById("trImages");
	var childList = trPhotos.childNodes;
	var height = getImageHeightFromWindowSize();

	for(var i=0; i!=childList.length; i++) {
		for(var j=0; j!=childList[i].childNodes.length; j++) {
			var child = childList[i].childNodes[j];
			if(child.tagName == "IMG") {
				child.style.height = height+"px";
			}
		}
	}
}

/* function showImageOne(imgName, height) {
	var trPhotos = document.getElementById("trImages");
	
	var td = document.createElement("TD");
	var img = document.createElement("IMG");
	img.src = "mesvignettes/vignettes.php?sourceimg="+imgName+"&hauteur="+imageRawHeight;
	img.style.height = height+"px";
	
	td.appendChild(img);
	trPhotos.appendChild(td);
} */
function showImageOne(imgName, height) {
	var trPhotos = document.getElementById("trImages");
	
	var td = document.createElement("TD");
	
	imageToLoadList.push( {
		"elt":td,
		"url":"mesvignettes/vignettes.php?sourceimg="+imgName+"&hauteur="+imageRawHeight,
		"height":height+"px",
		"status":null
	} );
	
	/* var img = document.createElement("IMG");
	img.src = "mesvignettes/vignettes.php?sourceimg="+imgName+"&hauteur="+imageRawHeight;
	img.style.height = height+"px";
	td.appendChild(img);*/
	
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
	
	var img = document.createElement("IMG");
	img.src = url;
	if(height != null) {
		img.style.height = height;
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
	var height = getImageHeightFromWindowSize();
	
	for(var i=0; i!=imageList.length; i++) {
		showImageOne(imageList[i], height);
	}
	
}

function showImageDirOne(dirName) {
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
	a.onclick = function() {
		changeCurrentDir(dirName);
		return false;
	}
	//td.innerHTML = "<img src=\"mesvignettes/vignettes_dir.php?dir="+dirName+"&largeur="+imageDirRawWidth+"&hauteur="+imageDirRawHeight+"\" "
	//	+ "onclick=\"changeCurrentDir('"+dirName+"');\"/>";
	
	imageToLoadList.push( {
		"elt":a,
		"url":"mesvignettes/vignettes_dir.php?dir="+dirName+"&largeur="+imageDirRawWidth+"&hauteur="+imageDirRawHeight,
		"height":null,
		"status":null
	} );
	
	td.appendChild(a);
	tr.appendChild(td);
}


function showDirList() {
	
	for(var i=0; i!=dirList.length; i++) {
		showImageDirOne(dirList[i]);
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
		//alert(trPhotos.childNodes[3].tagName);
		trPhotos.removeChild(trPhotos.childNodes[3]);
	}
	
}

/* animation pour deplacer un scroll horizontal jusqu'a une position */
function myScrollHorizontalTo(div, destLeft, nbStep) {
	var intervalMs = 1/25;
	var startLeft = div.scrollLeft;
	
	beginAnimation(function(step) {
		if(step >= nbStep) {
			div.scrollLeft = destLeft ;
			return 0;
		}
		
		div.scrollLeft = startLeft + (destLeft - startLeft)*step/nbStep;
		return intervalMs;
	});
}

function myScrollLeft() {
	var tr = document.getElementById("trImages");
	var scrollableDiv = document.getElementById("scrollableDiv");
	var tdList = tr.childNodes;
	var left = 0;
	
	var trWidth = tr.clientWidth;
	var screenWidth = scrollableDiv.clientWidth;
	var currentPos = scrollableDiv.scrollLeft + screenWidth/2;

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
			//scrollableDiv.scrollLeft = previousTdPosN2 - screenWidth/2;
			myScrollHorizontalTo(scrollableDiv, previousTdPosN2 - screenWidth/2, 15)
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
	var currentPos = scrollableDiv.scrollLeft + screenWidth/2;
	
	for(var i=0; i!=tdList.length; i++) {
		var td = tdList[i];
		if(td.tagName != "TD") {
			continue;
		}
		
		var width = td.clientWidth;

		var tdPos = left + width/2;
		if(tdPos > currentPos+1) {
			//scrollableDiv.scrollLeft = tdPos - screenWidth/2;
			myScrollHorizontalTo(scrollableDiv, tdPos - screenWidth/2, 15)
			break;
		}

		left += width+2;
	}
}


function myScrollLeftDouble() {
	var div = document.getElementById("scrollableDiv");
	//div.scrollLeft -= 6000;
	myScrollHorizontalTo(scrollableDiv, div.scrollLeft - 6000, 15)

}
function myScrollRightDouble() {
	var div = document.getElementById("scrollableDiv");
	//div.scrollLeft += 6000;
	myScrollHorizontalTo(scrollableDiv, div.scrollLeft + 6000, 15)
}

function bodyOnLoad() {
	ajusteDir();
	
	loadDirEntries(currentDir);
}

window.onresize = function(event) {
	ajusteImages();
	ajusteDir();
};

</script>
</head>
<body style="background-color:#000000;height:97%:hidden"" onload="bodyOnLoad();">
<div id="globalFullScreen" style="height:100%;">
<div id="scrollableDiv" style="overflow-x: scroll;;overflow-y:hidden;scrollbar-face-color: black;height:100%;">
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
		<img src="mesvignettes/return2.png" style="opacity:0.4;width:80px;height:40px;transform:scaleY(-1);" onmouseover="opacityOnMouseOver(this);" onmouseout="opacityOnMouseOut(this);"/>
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
	<a href="" onclick="toggleDisplay('divFilters');return false;">
		<img style="height:30px;width:45px;middle;opacity:0.3" src="mesvignettes/filter.png" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" />
	</a>
	
	<div id="divFilters" style="display:none;">
		<a href="" onclick="toggleExifAll();return false;" style="font:Arial;color:grey;font-size:8px;">EXIF</a>
		<br/>

		<input id="filtreInput" type="text" style="vertical-align: middle;width:100px;font-size:12px;background-color:white;opacity:0.3" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" onkeypress="onFilterKeyPress(event);" onkeydown="event.stopPropagation();" value="<? echo $filtre; ?>" />
		<img src="mesvignettes/close.png" style="vertical-align: middle;opacity:0.3" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" onclick="gotourl('<?echo $url; ?>', null, '<? echo $filtreDescription; ?>');return false;"/>
		<br/>

		<div><a href="" onclick="toggleDescription();return false;" style="font:Arial;color:grey;font-size:8px;" title="<? if($currentDirDescription!=null) { echo $currentDirDescription->getDescription(); } ?>">DESCR.</a></div>
		<input id="filtreDescriptionInput" type="text" style="vertical-align: middle;width:100px;font-size:12px;background-color:white;opacity:0.3" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" onkeypress="onFilterKeyPress(event);" onkeydown="event.stopPropagation();" value="<? echo $filtreDescription; ?>" />
		<img src="mesvignettes/close.png" style="vertical-align: middle;opacity:0.3" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=0.3;" onclick="gotourl('<?echo $url; ?>', '<? echo $filtre; ?>', null);return false;"/>
	</div>
	
</div>
<div id="descriptionDiv" style="display:none;">
	<textarea id="descriptionTextArea" rows="10" cols="80" style="opacity:0.8;"/></textarea>
	<button onclick="saveDescription();">enregistrer</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button onclick="cancelDescription();">annuler</button>
</div>
</div>
</div>
</div>
</body>
</html>