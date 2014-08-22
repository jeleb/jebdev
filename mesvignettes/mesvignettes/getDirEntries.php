<?
include "common.php";
include "dirDescriptionClass.php";

$myjson = file_get_contents('php://input');
$message = json_decode($myjson, true);

$dir = $message["dir"];
$filterFileNameRegex = getWithDefault($message, "filterFileNameRegex", null);
$filterFileNameRegexList = getWithDefault($message, "filterFileNameRegexList", null);
$filterDescription = getWithDefault($message, "filterDescription", null);
$recurseDir = getWithDefault($message, "recurseDir", false);


if($recurseDir == null) {
	$recurseDir = false;
}

// todo : filtre exif 

if($log_json) {
	error_log($myjson);
}

$all_dir_entries = array();
$all_file_entries = array();
$dirFullName = $file_lookup_prefix."/".$dir;

$dirDescr = new DirDescription($dirFullName);
if($dirDescr->exists()) {
	$dirDescr->read();
}
else {
	$dirDescr = null;
}


if ($handle = opendir($dirFullName)) {
	while (false !== ($entry = readdir($handle))) {
		if($entry == "." ||
			$entry == "..") {
			continue;
		}
		
		$entryLC = strtolower($entry);
		
		$entryFullName = $dirFullName.'/'.$entry;
		
		if ( is_dir($entryFullName) ) {
			if($dont_show_dir == $entry) {
				continue;
			}
			
			if($filterDescription != null && $filterDescription != "") {
				$subDirDescr = new DirDescription($entryFullName);
				if($subDirDescr->exists()) {
					$subDirDescr->read();
					if( ! $subDirDescr->contains($filterDescription)) {
						continue;
					}
				}
				else {
					continue;
				}
				$subDirDescr = null;
			}

			array_push($all_dir_entries, $entry);
		}
		else {
			if($entry == $descriptionFileName ||
				preg_match("/^".$dont_show_image_prefix.".*$/", $entryLC)) {
				continue;
			}
			
			if($filterFileNameRegex != null &&
				! preg_match("/^".$filterFileNameRegex."$/", $entryLC)) {
				continue;
			}

			if($filterFileNameRegexList != null) {
				foreach($filterFileNameRegexList as $filterRegex) {
					if( ! preg_match("/^".$filterRegex."$/", $entryLC)) {
						continue;
					}
				}
			}
			
			if($filterDescription != null && $filterDescription != "" && $dirDescr != null) {
				if( ! $dirDescr->containsForImg($entryLC, $filterDescription)) {
					continue;
				}
			}
		
			array_push($all_file_entries, $entry);
		}
	}
	closedir($handle);
}

$return_message = array("fileEntries" => $all_file_entries, "dirEntries" => $all_dir_entries);
$return_message_json = json_encode($return_message);
if($log_json) {
	error_log($return_message_json);
}
echo $return_message_json;

?>