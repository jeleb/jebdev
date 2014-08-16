<?
include "common.php";


$myjson = file_get_contents('php://input');

$message = json_decode($myjson, true);
$dir = $message["dir"];
$newDescription = $message["newDescription"];

$filename = $dir."/".$descriptionFileName;

if($log_json) {
	error_log($myjson);
}

$h = fopen($file_lookup_prefix."/".$filename,"w");
if($h === FALSE) {
	throw new Exception("Ouverture du fichier '$filename' en echec");
}

$write_length = fwrite($h, $newDescription);
fclose($h);

if($write_length != strlen($newDescription)) {
	throw new Exception("Ecriture du fichier '$filename' en echec");
}

?>
