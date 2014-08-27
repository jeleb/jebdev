<?
include "common.php";


$myjson = file_get_contents('php://input');

$message = json_decode($myjson, true);
$dir = $message["dir"];

$filename = $dir."/".$descriptionFileName;

if($log_json) {
	error_log($myjson);
}

securityCheckPath($dir);

$description = file_get_contents($file_lookup_prefix."/".$filename);
if($description === false) {
	$description = "";
}

$return_message = array("description" => $description);
$return_message_json = json_encode($return_message);
if($log_json) {
	error_log($return_message_json);
}

echo $return_message_json;

?>
