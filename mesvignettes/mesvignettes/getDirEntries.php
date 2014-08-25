<?
include "common.php";
include "dirDescriptionClass.php";

$myjson = file_get_contents('php://input');
$message = json_decode($myjson, true);

$dir = $message["dir"];
$filterFileNameRegex = getWithDefault($message, "filterFileNameRegex", null);
$filterFileNameRegexList = getWithDefault($message, "filterFileNameRegexList", null);
$filterDescription = getWithDefault($message, "filterDescription", null);
$filterExif = getWithDefault($message, "filterExif", null);

$filterExif = strtolower($filterExif);

// todo : security sur ., .., /

if($log_json) {
	error_log($myjson);
}

function exifFileMatch($filtre, $file) {
	$exif = exif_read_data($file, 0, true);
	if($exif === false) {
		return false;
	}
	return exifMatch($filtre, $exif);
}

function exifMatch($filtre, $exif) {


	if(isset($exif["IFD0"])) {
		if(isset($exif["IFD0"]["Comments"])) {
			$userComment = strtolower(utf8_encode(preg_replace('/[\x00-\x1F]/', '', $exif["IFD0"]["Comments"])));
			
			if(strpos($userComment, $filtre) !== false) {
				return true;
			}
		}
		if(isset($exif["IFD0"]["Model"])) {
			$model = strtolower($exif["IFD0"]["Model"]);

			if(strpos($model, $filtre) !== false) {
				return true;
			}
		}
		if(isset($exif["IFD0"]["Keywords"])) {
			$keyWords = strtolower(utf8_encode(preg_replace('/[\x00-\x1F]/', '', $exif["IFD0"]["Keywords"])));
			
			if(strpos($keyWords, $filtre) !== false) {
				return true;
			}
		}
	}
	
	return false;
}

function filterDirContainingFiles($dir, $dirFilterDescription, $dirFilterExif, $depth, $subDirDescr) {

	if($dirFilterDescription != null && $dirFilterDescription != "") {
		if($subDirDescr != null) {
			$subDirDescr = new DirDescription($dir);
			if($subDirDescr->exists()) {
				$subDirDescr->read();
			}
			else {
				$subDirDescr = null;
			}
		}
		if( $subDirDescr != null) {
			if( $subDirDescr->contains($dirFilterDescription)) {
				return true;
			}
		}
		$subDirDescr = null;
	}

	$ret = false;
	if ($handle = opendir($dir)) {
	
		while (false !== ($subEntry = readdir($handle))) {
			if($subEntry == "." || $subEntry == "..") {
				continue;
			}
			$subEntryLC = strToLower($subEntry);
		
			$subEntryFullName = $dir.'/'.$subEntry;
			
			if ( is_dir($subEntryFullName) ) {
			
				if( filterDirContainingFiles($subEntryFullName, $dirFilterDescription, $dirFilterExif, $depth+1, null) ) {
					$ret = true;
					break;
				}
			}
			else {
				if($depth == 1 && // pas de récursion pour l'exif sinon c'est trop long ... pas très intuitif, que trouver de mieux ?
					$dirFilterExif != null  && $dirFilterExif != "" &&
					preg_match("/^.*\\.(jpg|jpeg|png|gif)$/", $subEntryLC)) {
					if( exifFileMatch($dirFilterExif, $subEntryFullName)) {
						$ret = true;
						break;
					}
				}				
			}
		}
		
		closedir($handle);
	}
	
	return $ret;
}

function computeExifTitle($exif) {
	$model = "";
	$focalLength = "";
	$aperture = "";
	$expoTime = "";
	$isoSpeed = "";
	$userComment = "";
	$keyWords = "";

	if(isset($exif["IFD0"])) {
		if(isset($exif["IFD0"]["Comments"])) {
			$userComment = utf8_encode(preg_replace('/[\x00-\x1F]/', '', $exif["IFD0"]["Comments"]));
		}
		if(isset($exif["IFD0"]["Model"])) {
			$model = $exif["IFD0"]["Model"];
		}
		if(isset($exif["IFD0"]["Keywords"])) {
			$keyWords = utf8_encode(preg_replace('/[\x00-\x1F]/', '', $exif["IFD0"]["Keywords"]));
		}
	}
	
	if(isset($exif["COMPUTED"])) {
		if(isset($exif["COMPUTED"]["ApertureFNumber"])) {
			$aperture = $exif["COMPUTED"]["ApertureFNumber"];
		}
	}
	
	if($exif["EXIF"]) {
		if(isset($exif["EXIF"]["FocalLength"])) {
			$focalLength = $exif["EXIF"]["FocalLength"];
			$diviserInstr = strpos($focalLength, "/");
			if ($diviserInstr !== false) {
				$d = substr($focalLength, 0, $diviserInstr);
				$D = substr($focalLength, $diviserInstr+1);
				$focalLength = floor((intval($d)/intval($D))*100)/100;
			}
			
			$focalLength = $focalLength . "mm";
		}
		if(isset($exif["EXIF"]["ExposureTime"])) {
			$expoTime = $exif["EXIF"]["ExposureTime"] . "s";
		}
		if(isset($exif["EXIF"]["ISOSpeedRatings"])) {
			$isoSpeed = $exif["EXIF"]["ISOSpeedRatings"] . "ISO";
		}
	}
	
	$title = $model . "\n" . 
			$aperture . " " . $expoTime . " " . $isoSpeed . "\n" .
			$focalLength . "\n" .
			$userComment . "\n" .
			$keyWords;
			
	return $title;
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
		$description = $entry;
		
		if ( is_dir($entryFullName) ) {
			if($dont_show_dir == $entry) {
				continue;
			}
			
			$subDirDescr = new DirDescription($entryFullName);
			if($subDirDescr->exists()) {
				$subDirDescr->read();
				$description = $description . "\n\n" . $subDirDescr->getGlobalDescription();
			}
			else {
				$subDirDescr = null;
			}

			
			if( ( $filterDescription != null && $filterDescription != "") ||
				( $filterExif != null && $filterExif != "") ) {
				if( ! filterDirContainingFiles($entryFullName, $filterDescription, $filterExif, 1, $subDirDescr) ) {
					continue;
				}
			}
			
			array_push($all_dir_entries,  array("name" => $entry, "description" => $description));
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
			
			if($dirDescr != null) {
				if($filterDescription != null && $filterDescription != "") {
					if( ! $dirDescr->containsForImg($entryLC, $filterDescription)) {
						continue;
					}
				}
				
				$description = $description . "\n\n" . $dirDescr->getDescriptionForImg($entry);
			}
		
			if(preg_match("/^.*\\.(jpg|jpeg|png|gif)$/", $entryLC)) {
				$exif = exif_read_data($entryFullName, 0, true);
				if($exif !== false) {
					$description = $description . "\n\n" . computeExifTitle($exif);
				}

				if($filterExif != null  && $filterExif != "") {
					if($exif === false) {
						continue;
					}

					if( ! exifMatch($filterExif, $exif)) {
						continue;
					}
				}
			}

			array_push($all_file_entries, array("name" => $entry, "description" => $description));
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