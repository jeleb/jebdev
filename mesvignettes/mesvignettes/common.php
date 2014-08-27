<?
/* ailleurs que dans index.php, les fichiers sont à rechercher dans .. car ces fichiers sont dans un sous-répertorie "mesvignettes"*/ 
$file_lookup_prefix = "..";

/* nom des fichiers de description */
$descriptionFileName = "_description.txt";

/* prefixes des images qu'il ne faut pas afficher (celles de mesvignettes principalement) */
$dont_show_image_prefix = "mesvignettes_";
$dont_show_dir = "mesvignettes";


/* indique s'il faut logguer le json */
$log_json = true;

function getWithDefault($tab, $key, $defaultValue) {
	if(array_key_exists($key, $tab)) {
		return $tab[$key];
	}
	else {
		return $defaultValue;
	}
}

function securityCheckPath($path) {
	if(substr($path, 0, 1) == "/") {
		throw new Exception("invalid Path : ".$path);
	}
	
	if(strpos($path, "\\") !== false) {
		throw new Exception("invalid Path : ".$path);
	}

	if(strpos($path, "//") !== false) {
		throw new Exception("invalid Path : ".$path);
	}

	if(strpos($path, "~") !== false) {
		throw new Exception("invalid Path : ".$path);
	}

	if(strpos($path, "?") !== false) {
		throw new Exception("invalid Path : ".$path);
	}

	if(strpos($path, ":") !== false) {
		throw new Exception("invalid Path : ".$path);
	}
}
?>