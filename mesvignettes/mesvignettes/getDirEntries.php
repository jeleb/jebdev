<?
include "common.php";

$myjson = file_get_contents('php://input');
$message = json_decode($myjson, true);

$dir = $message["dir"];
$filterFileNameRegex = $message["filterFileNameRegex"];

// todo : filtre de nom de fichier JPEG, JPG, PNG, etc.
// todo : filtre exif 
// todo : filtre description

if($log_json) {
	error_log($myjson);
}

$all_dir_entries = array();
$all_file_entries = array();

if ($handle = opendir($file_lookup_prefix."/".$dir)) {
	while (false !== ($entry = readdir($handle))) {
//		error_log($entry);
		if($entry == "." ||
			$entry == "..") {
			continue;
		}
		
		if ((is_dir($file_lookup_prefix."/".$dir.'/'.$entry)) && $entry != "." && $entry != "..") {
			if($dont_show_dir == $entry) {
				continue;
			}

			array_push($all_dir_entries, $entry);
		}
		else {
			if($entry == $descriptionFileName ||
				preg_match("/^".$dont_show_image_prefix.".*$/", $entry)) {
				continue;
			}
			
			if($filterFileNameRegex != null &&
				! preg_match("/^".$filterFileNameRegex."$/", strtolower($entry))) {
				continue;
			}
		
			array_push($all_file_entries, $entry);
		}
	}
	closedir($handle);
}

$return_message = array("fileEntries" => $all_file_entries, "dirEntries" => $all_dir_entries);
echo json_encode($return_message);

?>