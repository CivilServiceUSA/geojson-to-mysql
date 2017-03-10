<?php
/**
 * GeoJSON to MySQL importer
 *
 * @note this is quick and dirty.  It's more of a proof of concept because it's specific to a single table type and connection parameters are hardcoded.
 *
 * @usage php import.php --table district_shapes --keys state_code,district --dir /home/tmp/districts --insert
 */

$config = parse_ini_file('./config.ini');
$options = getopt("", array('table:', 'keys:', 'dir:', 'insert'));

$table   = $options['table'];
$keys    = $options['keys'];
$dir    = $options['dir'];
$insertIntoDb = (isset($options['insert'])) ? true : false;

$importPath = $dir;

$keysToImport = explode(',', $keys);

$columnTemplate = array();

foreach($keysToImport as $keyName){
	$columnTemplate[$keyName] = '';
}


// Must create a connection to MySQL so real_escape_string works
// @todo don't rely on MySQL connection
$mysqli = new mysqli($config['host'], $config['username'], $config['password'], $config['dbname']);


$query = "INSERT INTO district_shapes (`state`, `district_id`, `shape`) VALUES (? , ? , ST_GeomFromGeoJSON(?))";


foreach (glob("{$importPath}/*.geojson") as $filePath){

	$geojson = file_get_contents($filePath);
	$json = json_decode($geojson, true);

	$rowValues = $columnTemplate;

	foreach($keysToImport as $keyName){
		$rowValues[$keyName] = (isset($json['properties'][$keyName])) ? $json['properties'][$keyName] : '';
	}

	$setString = ' ';

	foreach($rowValues as $keyName => $value){
		$setString .= "`{$keyName}` = '".$mysqli->real_escape_string($value)."', ";
	}

	$setString .= " `shape` = ST_GeomFromGeoJSON('".$mysqli->real_escape_string($geojson)."')";

	$query = "INSERT INTO `{$table}` SET {$setString} ; \r\n\r\n";

	if($insertIntoDb){
		$mysqli->query($query);
	} else {
		echo $query;
	}
}
