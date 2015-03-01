<?
include "config.php";
include "common.php";
include "dirDescriptionClass.php";
include "fts.php";

$cmd = $_GET["cmd"];
?>

<!DOCTYPE html>
<html style="height:100%">
<head>
<TITLE>full text search</TITLE>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<script language="javascript">
</script>
</head>
<body>

<?

echo("cmd : $cmd <br/>");

switch($cmd) {
	case "recreate" :
		echo("recreating index ...<br/>");
		fts_recreate_index();
		echo("index recreated<br/>");
		break;
		
	case "update" :
		echo("updating index ...<br/>");
		fts_update_index();
		echo("index updated ...<br/>");
		break;
		
	case "rq": 
		$q = $_GET["q"];
		echo("running raw query (depth 1) : <br/>$q<br/>");
		$results = fts_query_raw($q);
		$count = 0;
		while ($row = $results->fetchArray()) {
			var_dump($row);
			$count++;
			echo("<br>/");
		}
		echo("$count results displayed<br/>");
		break;

	case "query" :
	case "query_1" :
		$dir = isset($_GET["dir"])?$_GET["dir"]:"";
		$q = $_GET["q"];
		echo("running query (depth 1) : dir=$dir q=$q<br/>");
		$results = fts_query_depth_1($dir, $q);
		$count = 0;
		while ($row = $results->fetchArray()) {
			var_dump($row);
			$count++;
			echo("<br>/");
		}
		echo("$count results displayed<br/>");
		break;
		
	case "query_2" :
		$dir = isset($_GET["dir"])?$_GET["dir"]:"";
		$q = $_GET["q"];
		echo("running query (depth 2) : dir=$dir q=$q<br/>");
		$results = fts_query_depth_2($dir, $q);
		$count = 0;
		while ($row = $results->fetchArray()) {
			var_dump($row);
			$count++;
			echo("<br>/");
		}
		echo("$count results displayed<br/>");
		break;

	default :
		echo("unknown command : '$cmd'");
		break;
}

?>

<br/><br/><br/>end<br/><br/>
</body>
</html>