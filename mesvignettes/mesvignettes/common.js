
/* met un div en fullscreen */
function requestFullScreen(id) {
	var elem = document.getElementById(id);

	if (elem.requestFullScreen) {
	  elem.requestFullScreen();
	} else if (elem.msRequestFullscreen) {
	  elem.msRequestFullscreen();
	} else if (elem.mozRequestFullScreen) {
	   elem.mozRequestFullScreen();
	} else if (elem.webkitRequestFullscreen) {
	  elem.webkitRequestFullscreen();
	}
}

/* sortir du fullscreen */
function exitFullScreen(id) {
	var elem = document.getElementById(id);
	
	if(document.exitFullscreen) {
		document.exitFullscreen();
	} else if(document.mozCancelFullScreen) {
		document.mozCancelFullScreen();
	} else if(document.webkitExitFullscreen) {
		document.webkitExitFullscreen();
	} else if (elem.exitFullScreen) {
	  elem.exitFullscreen();
	} else if (elem.msCancelFullScreen) {
	  elem.msCancelFullScreen();
	} else if (elem.mozCancelFullScreen) {
	  elem.mozCancelFullScreen();
	} else if (elem.webkitExitFullscreen) {
	  elem.webkitExitFullscreen();
	}
}

/* récupérer la hauteur de la fenêtre */
function getWindwHeight() {
  var myHeight = 0;
  if( typeof( window.innerHeight ) == 'number' ) {
    myHeight = window.innerHeight;
  } else if( document.documentElement && document.documentElement.clientHeight  ) {
    myHeight = document.documentElement.clientHeight;
  } else if( document.body && document.body.clientHeight ) {
    myHeight = document.body.clientHeight;
  }
  return myHeight;
}

/* récupérer la largeur de la fenêtre */
function getWindwWidth() {
  var myWidth = 0;
  if( typeof( window.innerWidth ) == 'number' ) {
    myWidth = window.innerWidth;
  } else if( document.documentElement && document.documentElement.clientWidth  ) {
    myWidth = document.documentElement.clientWidth;
  } else if( document.body && document.body.clientWidth ) {
    myWidth = document.body.clientWidth;
  }
  return myWidth;
}

/* affiche un élément s'il est masqué, et le masque sinon */
function toggleDisplay(id) {
	var elt = document.getElementById(id);
	
	if(elt.style.display == "none") {
		elt.style.display = "block";
	}
	else {
		elt.style.display = "none";
	}
}

/* lance une animation sur la base d'une simple callback */
function animationOne(step, callback) {
	var delayMilliSecondes = callback(step+1);

	if(delayMilliSecondes > 0) {
		setTimeout(function() {
			animationOne(step+1, callback);
		}
		, delayMilliSecondes);
	}
}

function beginAnimation(callback) {
	var h = null;
	var delayMilliSecondes = callback(1);
	
	if(delayMilliSecondes > 0) {
		h = setTimeout(function() {
			animationOne(2, callback);
		}
		, delayMilliSecondes);
	}
	return h;
}
