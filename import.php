<?php
/**
 * GeoJSON to MySQL importer
 *
 * @todo Don't rely on a database connection to MySQL for escaping
 *
 * @usage php import.php --table district_shapes --keys state_code,district --dir /home/tmp/districts --insert
 */

$config = parse_ini_file(realpath(dirname(__FILE__)) . '/config.ini');
$options = getopt("", array('table:', 'keys:', 'dir:', 'insert'));

define('DB_HOST', $config['host']);
define('DB_USER', $config['username']);
define('DB_PASS', $config['password']);
define('DB_NAME', $config['dbname']);
define('DB_TABLE', $options['table']);
define('DB_COLUMNS', explode(',', $options['keys']));
define('IMPORT_PATH', $options['dir']);
define('DO_INSERT', (isset($options['insert'])));

$columnTemplate = array();

foreach(DB_COLUMNS as $keyName){
	$columnTemplate[$keyName] = '';
}

/**
 * @param $mysqli
 * @param $columnTemplate
 * @param $json
 */
function createInsert($mysqli, $columnTemplate, $json) {
    // Make sure we have a MySQL Connection
    if ($mysqli) {
        $rowValues = $columnTemplate;

        foreach(DB_COLUMNS as $keyName){
            $rowValues[$keyName] = (isset($json['properties'][$keyName])) ? $json['properties'][$keyName] : '';
        }

        $setString = ' ';

        foreach($rowValues as $keyName => $value){
            $setString .= "`{$keyName}` = '".$mysqli->real_escape_string($value)."', ";
        }

        $setString .= " `shape` = ST_GeomFromGeoJSON('".$mysqli->real_escape_string(json_encode($json))."')";

        $query = "INSERT INTO `" . DB_TABLE . "` SET {$setString} ; \r\n";

        if(DO_INSERT){
            if ($mysqli->query($query)) {
                $message = array();
                foreach(DB_COLUMNS as $keyName){
                    $message[] = "'{$json['properties'][$keyName]}' for key '{$keyName}'";
                }

                if (!DO_INSERT) {
                    echo "✓ Added entry " . join(', ', $message) . PHP_EOL;
                }
            } else {
                if (DO_INSERT) {
                    exit(printf("× %s\n", $mysqli->error));
                } else {
                    printf("× %s\n", $mysqli->error);
                }
            }

            //echo $query;
        } else {
            echo $query;
        }
    } else {
        echo "Error: Unable to connect to MySQL." . PHP_EOL;
        echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
        echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
        exit;
    }
}

// Must create a connection to MySQL so real_escape_string works
// @todo don't rely on MySQL connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

foreach (glob(IMPORT_PATH . "/*.geojson") as $filePath) {

    $geojson = file_get_contents($filePath);
    $json = json_decode($geojson, true);

    // Check if GeoJSON is Feature or FeatureCollection
    if ($json['type'] === 'FeatureCollection') {
        foreach($json['features'] as $feature){
            createInsert($mysqli, $columnTemplate, $feature);
        }
    } else if ($json['type'] === 'Feature') {
        createInsert($mysqli, $columnTemplate, $json);
    } else {
        exit('Invalid GeoJSON');
    }
}

/* Close Connection */
$mysqli->close();