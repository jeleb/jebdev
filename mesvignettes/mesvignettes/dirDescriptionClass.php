<?


// classe pour gérer les descriptions par image
class DirDescriptionPerImage {
	var $firstImage;
	var $lastImage;
	var $description;
	
	function DirDescriptionPerimage($aFirstImage, $aLastImage) {
		$this->description = "";
		$this->firstImage = $aFirstImage;
		$this->lastImage = $aLastImage;
	}
	
	function addDescription($line) {
		$this->description = $this->description.$line;
	}
	
}

// classe pour gérer les fichiers de description
class DirDescription {
	var $fileName;
	var $globalDescription;
	var $perImageDescriptionArray;
	var $cover;
	
	function DirDescription($dir) {
		global $descriptionFileName;
		$this->fileName = $dir."/".$descriptionFileName;
		$this->perImageDescriptionArray = array();
		$this->globalDescription = "";
		$this->cover = null;
	}
	
	function exists() {
		return file_exists($this->fileName);
	}
	
	function read() {
		// $this->globalDescription = file_get_contents($this->fileName);
		$perImageCurrent = null;
		$h = fopen($this->fileName,"r");

		while(! feof($h))  {
			$line = trim(fgets($h));
			$line = removeUTF8InvalidCaracters($line);
			
			if(substr($line, 0, 1) == "*") {
				$separatorPos = strpos($line, ":", 1);
				$first = null;
				$last  = null;
				if($separatorPos === FALSE) {
					$first = substr($line, 1);
					$last  = $first;
				}
				else {
					$first = substr($line, 1, $separatorPos-1);
					$last  = substr($line, $separatorPos+1);
				}
				$perImageCurrent = new DirDescriptionPerImage($first, $last);
				array_push($this->perImageDescriptionArray, $perImageCurrent);
			}
			else if($perImageCurrent == null &&
					substr($line, 0, 1) == "!") {
				$this->cover = explode(",", substr($line, 1));
			}
			else {
				if($perImageCurrent != null) {
					$perImageCurrent->addDescription($line . "\n");
				}
				else {
						$this->globalDescription = $this->globalDescription . $line . "\n";
				}
			}
		}

		fclose($h);
	}
	
	function contains($text) {
		$text = normalizeString($text);
	
		if(strpos(normalizeString($this->globalDescription), $text) !== FALSE) {
			return TRUE;
		}
		
		foreach($this->perImageDescriptionArray as $descr) {
			if(strpos(normalizeString($descr->description), $text) !== FALSE) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	function containsForImg($img, $text) {
		$text = normalizeString($text);

		if(strpos(normalizeString($this->globalDescription), $text) !== FALSE ) {
			return TRUE;
		}
	
		$imgLC = strtolower($img);
		foreach($this->perImageDescriptionArray as $descr) {
			if( strcmp($imgLC, strtolower($descr->firstImage)) >= 0 && 
				strcmp($imgLC, strtolower($descr->lastImage)) <= 0 &&
				strpos(strtolower($descr->description), $text) !== FALSE ) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	function getGlobalDescription() {
		return $this->globalDescription;
	}
	
	function getDescriptionForImg($img) {
		$str = $this->globalDescription ;
		
		foreach($this->perImageDescriptionArray as $descr) {
			if($descr->lastImage == null && $descr->firstImage==$img) {
				$str = $str . "\n" . $descr->description;
			}
			else if(strcmp($descr->firstImage, $img) <= 0 && strcmp($descr->lastImage, $img)>=0) {
				$str = $str . "\n" . $descr->description;
			}
		}
		
		return $str;
	}
	
	function getDescription() {
		$str = $this->globalDescription /*. "\n"*/;
		
		foreach($this->perImageDescriptionArray as $descr) {
			if($descr->lastImage == null || $descr->lastImage===$descr->firstImage) {
				$str = $str . "*" . $descr->firstImage . "\n";
			}
			else {
				$str = $str . "*" . $descr->firstImage . ":" . $descr->lastImage . ":\n";
			}
			$str = $str . $descr->description /*. "\n"*/;
		}
		
		return $str;
	}
	
	function getCover() {
		return $this->cover;
	}
}

?>