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

// verifie qu'un path ne contient pas de caracteres "dangereux"
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

// remplace les variables standard
// ${now:format} remplace par la date courante en utilisant le formatage :
//    YYYY : années sur 4 chiffres
//    YY   : années sur 2 chiffres
//    MM   : mois de l'année sur 2 chiffres
//    DD   : jour du mois sur deux chiffres
function parameterReplace($str) {
	$matches = array();
	error_log(preg_match ( "/\\$\\{now\:.*\\}/" , $str));
	if( 1 === preg_match ( "/\\$\\{now\:.*\\}/" , $str , $matches ) ) {
		foreach($matches as $m) {
			$m2 = substr($m, 6, strlen($m)-7);
			$replacement = str_replace("YYYY", date("Y"), $m2);
			$replacement = str_replace("YY", date("y"), $replacement);
			$replacement = str_replace("MM", date("m"), $replacement);
			$replacement = str_replace("DD", date("d"), $replacement);

			$str = preg_replace("/\\$\\{now\:".$m2."\\}/", $replacement, $str);
		}
	}
	
	return $str;
}

?>