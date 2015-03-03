<?

$db = null;
$stmtInsert = null;
$stmtUpdate = null;
$stmtDelete = null;
$updateMode = false;

$fileDateCache = array();

function fts_recreate_index() {
global $db, $fts_sqlite_db_filname, $file_lookup_prefix;
	set_time_limit(600) ; // todo trouver une autre solution pour le timeout

	$db = new SQLite3($fts_sqlite_db_filname);

	$db->exec("DROP TABLE IF EXISTS entry");
	$db->exec("VACUUM");
	$db->exec("CREATE VIRTUAL TABLE entry USING fts4(name UNIQUE, type, description, cover, filedate INTEGER)");
	$updateMode = false;
	fts_prepare_statements(true, false, false);

	$db->exec("BEGIN TRANSACTION");
	fts_index_dir($file_lookup_prefix, "", null);
	$db->exec("COMMIT");

}

function fts_update_index() {
global $db, $fts_sqlite_db_filname, $file_lookup_prefix;
	set_time_limit(600) ; // todo trouver une autre solution pour le timeout

	$db = new SQLite3($fts_sqlite_db_filname);
	loadFileDateCache();
	$updateMode = true;
	fts_prepare_statements(true, true, true);

	$db->exec("BEGIN TRANSACTION");
	fts_index_dir($file_lookup_prefix, "");

	fts_delete_remaining_entries();
	
	$db->exec("COMMIT");
}

function fts_query_raw($q) {
global $db, $fts_sqlite_db_filname;
	$db = new SQLite3($fts_sqlite_db_filname);
	$statement = $db->prepare($q);
	$results = $statement->execute();
	return $results;
}

function fts_query_depth_1($dir, $q) {
global $db, $fts_sqlite_db_filname;
	$db = new SQLite3($fts_sqlite_db_filname);
	$statement = $db->prepare("select name2 name, ".
							  " type type, ".
							  "  (select e1.description from entry e1 where e1.name=:dirSlash || name2) description, ".
							  "  (select e2.cover from entry e2 where e2.name=:dirSlash || name2) cover ".
							  "from ( ".
							  "SELECT distinct ".
							  "case instr(n,'/') ".
							  " when 0 then n ".
							  " else substr(n, 1, instr(n,'/')-1) ".
							  " end name2, ".
							  "case instr(n,'/') ".
							  " when 0 then t ".
							  " else 'd' ".
							  " end type ".
							  "from ( ".
							  "SELECT substr(name, :dirlength) n, type t FROM entry ".
							  "WHERE description MATCH :q ".
							  "and name like :dirlike "
							  .") "
							  .") "
							  );

	$statement->bindValue(":q", $q);
	$statement->bindValue(":dirSlash", $dir==""?"":$dir."/");
	$statement->bindValue(":dirlike", $dir.($dir==""?"":"/")."%");
	$statement->bindValue(":dirlength", $dir==""?1:strlen($dir)+2);
	$results = $statement->execute();
	return $results;
}

function fts_query_depth_2($dir, $q) {
global $fts_sqlite_db_filname;
	$db = new SQLite3($fts_sqlite_db_filname);
	$statement = $db->prepare("select name2 name, ".
							  " type type, ".
							  "  (select e1.description from entry e1 where e1.name=:dirSlash || name2) description, ".
							  "  (select e2.cover from entry e2 where e2.name=:dirSlash || name2) cover ".
							  "from ( ".
							  "SELECT distinct ".
							  "case instr(n,'/') ".
							  " when 0 then n ".
							  " else case instr(substr(n,instr(n,'/')+1),'/') ".
							  "      when 0 then case t ".
							  "                  when 'f' then substr(n, 1, instr(n,'/')-1) ".
 							  "                  else n".
							  "                  end ".
							  "      else substr(n,1, instr(n,'/')+instr(substr(n,instr(n,'/')+1),'/')-1) ".
							  "      end ".
							  " end name2 , ".
							  "case instr(n,'/') ".
							  " when 0 then t ".
							  " else 'd' ".
							  " end type ".
							  "from ( ".
							  "SELECT substr(name, :dirlength) n, type t FROM entry ".
							  "WHERE description MATCH :q ".
							  "and name like :dirlike"
							  .") "
							  .") "
							  );
	$statement->bindValue(":q", $q);
	$statement->bindValue(":dirSlash", $dir==""?"":$dir."/");
	$statement->bindValue(":dirlike", $dir.($dir==""?"":"/")."%");
	$statement->bindValue(":dirlength", $dir==""?1:strlen($dir)+2);
	$results = $statement->execute();
	return $results;
}

//////
// other functions
//////

function fts_prepare_statements($insert, $update, $delete) {
global $db, $stmtInsert, $stmtUpdate, $stmtDelete;
	if($insert) {
		$stmtInsert = $db->prepare("INSERT INTO entry(name, type, description, cover, filedate) VALUES(:name, :type, :description, :cover, :filedate)");
	}
	if($update) {
		$stmtUpdate = $db->prepare("UPDATE entry set type=:type, description=:description, cover=:cover, filedate=:filedate WHERE name=:name ");
	}
	if($delete) {
		$stmtDelete = $db->prepare("DELETE FROM entry WHERE name=:name ");
	}
}

function loadFileDateCache() {
global $db, $fileDateCache;

	$fileDateCache = array();
	$results = $db->query("SELECT name n, fileDate fd FROM entry");
	while ($row = $results->fetchArray()) {
		//var_dump($row);
		//echo("load  ".$row["n"]." ".$row["fd"]." <br/>");
		$fileDateCache[$row["n"]] = $row["fd"];
	}

}

function insert($name, $type, $description, $cover, $fileDate) {
global $stmtInsert;

	//error_log("insert $name $type");

	$stmtInsert->bindValue(':name', $name);
	$stmtInsert->bindValue(':type', $type);
	$stmtInsert->bindValue(':description', $description);
	$stmtInsert->bindValue(':cover', $cover);
	$stmtInsert->bindValue(':filedate', $fileDate);
	$stmtInsert->execute();
	commitSometime();
}

function update($name, $type, $description, $fileDate) {
global $stmtUpdate;

	//error_log("update $name");

	$stmtUpdate->bindValue(':name', $name);
	$stmtUpdate->bindValue(':type', "d");
	$stmtUpdate->bindValue(':description', $description);
	$stmtUpdate->bindValue(':filedate', $fileDate);
	$stmtUpdate->execute();
	commitSometime();
}

function delete($name) {
global $stmtDelete;

	//error_log("delete $name");

	$stmtDelete->bindValue(':name', $name);
	$stmtDelete->execute();
	commitSometime();
}

function getDescriptionExif($fullFileName) {

	$exif = exif_read_data($fullFileName, 0, true);
	if($exif === false) {
		return;
	}
	
	$model = "";
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

	return $descriptionExif = $model . "\n" . $userComment . "\n" . $keyWords ;
}

function fts_index_img($fullFileName, $fileName, $description, $hasExif, $forceUpdate) {
global $fileDateCache;

	//set_time_limit(30) ;
	$descriptionExif = "";
	
	$fileDate = filemtime($fullFileName);
	if($fileDate === FALSE) {
		error_log("cannot index '$fullFileName' because cannot get a timestamp");
		return;
	}
	
	
	// quand on recrée l'index pas d'accès à la base ci-dessous car le statement est null
	$fileDateCache = getFileDateFromCacheAndUnset($fileName);
	
		
	if($fileDateCache != null) {
		if($forceUpdate == TRUE) {
		    error_log("update because forceUpdate==TRUE");
			if($hasExif) {
				$description = $description."\n".getDescriptionExif($fullFileName);
			}
			update($fileName, "f", $description, null, $fileDate);
		}
		else if($fileDateCache < $fileDate) {
		    error_log("update because $fileDateCache < $fileDate");
			if($hasExif) {
				$description = $description."\n".getDescriptionExif($fullFileName);
			}
			update($fileName, "f", $description, null, $fileDate);
	    }
	}
	else {
		//error_log("insert because $forceUpdate $fileDateCache $fileDate");
		if($hasExif) {
			$description = $description."\n".getDescriptionExif($fullFileName);
		}
		insert($fileName, "f", $description, null, $fileDate);
	}
}

function fts_index_dir($fullDirName, $dir) {
global $dont_show_dir, $fileDateCache;
	//echo ("fts_index_dir $fullDirName <br/>");
	if($dir == $dont_show_dir) {
		return;
	}

	// pour que ça tourne en boucle sans jamais timeouter ...
	$dirUpdated = FALSE;
	
	$dirDescr = new DirDescription($fullDirName);
	if($dirDescr->exists()) {
		$fileDate = $dirDescr->getFileDate();
		if($fileDate === FALSE) {
			$fileDate = null;
		}
		
		$fileDateCache = getFileDateFromCacheAndUnset($dir);

		if($fileDateCache != null) {
			if($fileDateCache < $fileDate) {
				echo("update necessary for $dir (fs:$fileDate db:$fileDateCache<br/>");
				$dirUpdated = TRUE;
				$dirDescr->read();
				$description = $dir . "\n\n" . $dirDescr->getGlobalDescription();
				$coverList = $dirDescr->getCover();
				$cover = ($coverList==null ? null : join(",", $coverList));
				
				update($dir, "d", $description, $cover, $fileDate);
			}
			//else {
			//	echo("update NOT necessary for $dir (fs:$fileDate db:$fileDateCache<br/>");
				// nothing
			//}
		}
		else {
			$dirUpdated = TRUE;
			$dirDescr->read();
			$description = $dir . "\n\n" . $dirDescr->getGlobalDescription();
			$coverList = $dirDescr->getCover();
			$cover = ($coverList==null ? null : join(",", $coverList));
			insert($dir, "d", $description, $cover, $fileDate);
		}
	}
	else {
		$dirDescr = null;
	}
	
	
	if ($handle = opendir($fullDirName)) {
		while (false !== ($entry = readdir($handle))) {
			if($entry == "." ||
				$entry == "..") {
				continue;
			}
		
			$entryFullName = $fullDirName.'/'.$entry;
			$entryName = ($dir==""?"":$dir.'/').$entry;
			$entryLC = strtolower($entry);
			if ( is_dir($entryFullName) ) {
				fts_index_dir($entryFullName, $entryName);
			}
			else {
				if(preg_match("/^.*\\.(jpg|jpeg)$/", $entryLC)) {
					fts_index_img($entryFullName, $entryName, 
						($dirDescr==null?"":$dirDescr->getDescriptionForImg($entry)),
						true, $dirUpdated );
				}
				else if(preg_match("/^.*\\.(png|gif)$/", $entryLC)) {
					fts_index_img($entryFullName, $entryName, 
						($dirDescr==null?"":$dirDescr->getDescriptionForImg($entry)),
						false, $dirUpdated );
				}
			}
		}
	}
	
}

function fts_delete_remaining_entries() {
global $fileDateCache;

	foreach(array_keys($fileDateCache) as $filename) {
		//echo("delete : '$filename' <br/>");
		delete($filename);
	}
}

function getFileDateFromCacheAndUnset($name) {
global $updateMode, $fileDateCache;

	if($updateMode == false) {
		return null;
	}

	$fileDate = null;
	if(isset($fileDateCache[$fileName])) {
		$fileDate = $fileDateCache[$fileName];
		unset($fileDateCache[$fileName]);
	}
	
	return $fileDate;
}

$nbInsert = 0;
function commitSometime() {
	global $db, $nbInsert;
	$nbInsert ++;
	if($nbInsert%1000 == 0) {
		$db->exec("COMMIT");
		$db->exec("BEGIN TRANSACTION");
	}
}


/*function display_results($db, $q) {
	echo("\$q : $q <br/><br/>");
	$statement = $db->prepare("SELECT * FROM entry WHERE description MATCH :q");
	$statement->bindValue(":q", $q);
	$results = $statement->execute();
	while ($row = $results->fetchArray()) {
		var_dump($row);
		echo("<br/>");
	}
}*/

/*function display_results_dir_depth_1($db, $dir, $q) {
	echo("\$q : $q <br/>");
	echo("\$dir : $dir <br/>");
	//$statement = $db->prepare("SELECT distinct name FROM entry ".
	//						  "WHERE description MATCH :q ".
	//						  "and (name = :dir or name like :dirlike)");
	$statement = $db->prepare("SELECT distinct ".
							  "case instr(n,'/') ".
							  "when 0 then n ".
							  "else substr(n, 1, instr(n,'/')-1) end r ".
							  "from ( ".
							  "SELECT substr(name, :dirlength) n FROM entry ".
							  "WHERE description MATCH :q ".
							  "and name like :dirlike "
							  .") "
							  //." order by 1"
							  );

	$statement->bindValue(":q", $q);
	$statement->bindValue(":dirlike", $dir.($dir==""?"":"/")."%");
	$statement->bindValue(":dirlength", $dir==""?1:strlen($dir)+2);
	$results = $statement->execute();
	while ($row = $results->fetchArray()) {
		var_dump($row);
		echo("<br/>");
	}
}*/

/*
function display_results_dir_depth_2($db, $dir, $q) {
	echo("\$q : $q <br/>");
	echo("\$dir : $dir <br/>");
	//$statement = $db->prepare("SELECT distinct name FROM entry ".
	//						  "WHERE description MATCH :q ".
	//						  "and (name = :dir or name like :dirlike)");
	$statement = $db->prepare("SELECT distinct ".
							  "case instr(n,'/') ".
							  "when 0 then n ".
							  "else case instr(substr(n,instr(n,'/')+1),'/') ".
							  "     when 0 then case t ".
							  "                 when 'f' then substr(n, 1, instr(n,'/')-1) ".
 							  "                 else n".
							  "                 end ".
							  "     else substr(n,1, instr(n,'/')+instr(substr(n,instr(n,'/')+1),'/')-1) ".
							  "     end ".
							  "end r ".
							  "from ( ".
							  "SELECT substr(name, :dirlength) n, type t FROM entry ".
							  "WHERE description MATCH :q ".
							  "and name like :dirlike"
							  .") "
							  //.order by 1 "
							  );
	$statement->bindValue(":q", $q);
	$statement->bindValue(":dirlike", $dir.($dir==""?"":"/")."%");
	$statement->bindValue(":dirlength", $dir==""?1:strlen($dir)+2);
	$results = $statement->execute();
	while ($row = $results->fetchArray()) {
		var_dump($row);
		echo("<br/>");
	}
}
*/

/*function display_all_results($db) {
	echo("diplay all : <br/>");
	$statement = $db->prepare("SELECT * FROM entry ");
	$results = $statement->execute();
	while ($row = $results->fetchArray()) {
		var_dump($row);
		echo("<br/>");
	}
}
*/



	/*echo("debut : ".date('Y-m-d H:i:s')."<br/>");
	fts_recreate_index($db);
	echo("fin : ".date('Y-m-d H:i:s')."<br/>");
	*/

    //display_results($db, "anniv*");
    //display_results_dir_depth_1($db, "", "ixus*");
    //display_results_dir_depth_2($db, "", "ixus*");
	
    //display_results_dir_depth_2($db, "D:/petit_externe/photos/public_html", "anniv*");
    //display_all_results($db);

	
	
	
	
	//$db->exec("INSERT INTO BookSearch(id, text, exif) VALUES(1, 'coucou mon pote', '600D');");
	//$db->exec("INSERT INTO BookSearch(id, text, exif) VALUES(2, 'coucou bilou', '600D');");
	//$db->exec("INSERT INTO BookSearch(id, text, exif) VALUES(1, 'bla blabla', 'ixus');");
  
	//$results = $db->query("SELECT * FROM entry WHERE description MATCH 'maman'");
	//while ($row = $results->fetchArray()) {
	//	var_dump($row);
	//	echo("<br/>");
	//}

	/*$q = "-pote coucou OR blabla";
	$statement = $db->prepare("SELECT * FROM BookSearch WHERE text MATCH :q");
	$statement->bindValue(":q", $q);
	$results = $statement->execute();
	while ($row = $results->fetchArray()) {
		var_dump($row);
		echo("<br/>");
	}*/

	/*$statement = $db->prepare('SELECT * FROM table WHERE id = :id;');
	$statement->bindValue(':id', $id);

	$result = $statement->execute();*/
?>

