<?
include "config.php";

function cache_get_filename($id) {
  global $cache_disk_enabled, $cache_disk_dir, $cache_disk_dir_level, $cache_disk_dir_length;

  if($cache_disk_enabled !== TRUE) {
	return null;
  }
  
  $myhash = sha1($id);
  
  $myhash_filename = $cache_disk_dir;
  for($i=0; $i!=$cache_disk_dir_level; $i++) {
	$myhash_filename = $myhash_filename . "/" . substr($myhash, $i*$cache_disk_dir_length, $cache_disk_dir_length);
  }
  
  $myhash_filename = $myhash_filename . "/" . $myhash;
  
  return $myhash_filename;
}

function cache_check($filename, $last_modification_date) {
  if($filename == null) {
	return FALSE;
  }
  if(file_exists($filename) === FALSE) {
	return FALSE;
  }
  if($last_modification_date !== FALSE) {
	if($last_modification_date > filemtime($filename)) {
		return FALSE;
	}
  }
  return file_get_contents($filename);
}

function cache_save($filename, $im) {
  $i = strrpos($filename, "/");
  if($i === FALSE) {
    return;
  }
  
  $mydir =  substr($filename, 0, $i);
  if(file_exists($filename) === FALSE) {
     mkdir($mydir, null, TRUE);
  }

  imagejpeg($im, $filename);
}

?>