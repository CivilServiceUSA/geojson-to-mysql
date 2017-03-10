<?php
/**
 * GeoJSON to MySQL importer
 *
 * @note this is quick and dirty.  It's more of a proof of concept because it's specific to a single table type and connection parameters are hardcoded.
 */

$host = "localhost";
$username = 'replace';
$password = 'replace';
$dbname = 'replace';

$importPath = 'path_to_geo_json';

$mysqli = new mysqli($host, $username, $password, $dbname);
$query = "INSERT INTO district_shapes (`state`, `district_id`, `shape`) VALUES (? , ? , ST_GeomFromGeoJSON(?))";

echo "Importing...", PHP_EOL;


if ($stmt = $mysqli->prepare($query)) {

	foreach (glob("{$importPath}/*.geojson") as $filePath){

		$geojson = file_get_contents($filePath);

		$json = json_decode($geojson, true);

		$stateCode = $json['properties']['state_code'];
		$districtId = $json['properties']['district'];


		echo $filePath, PHP_EOL;

		$stmt->bind_param("sss", $stateCode, $districtId, $geojson);
		$stmt->execute();

	}
}





