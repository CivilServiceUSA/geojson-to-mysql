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

/**
 * @param $mysqli
 * @param $json
 */
function createInsert($mysqli, $json) {
    // Make sure we have a MySQL Connection
    if ($mysqli) {
        $data = array();

        foreach(DB_COLUMNS as $keyName){
            $value = (isset($json['properties'][$keyName])) ? $json['properties'][$keyName] : '';
            $data[] = $mysqli->real_escape_string($value);
        }

        $columns = '`' . implode('`,`', DB_COLUMNS) . '`, `shape`';
        $values = '"' . implode('","', $data) . '"';
        $values .= ", ST_GeomFromGeoJSON('".$mysqli->real_escape_string(json_encode($json))."')";

        $query = "INSERT INTO `" . DB_TABLE . "` ({$columns}) VALUES ({$values}); \r\n";

        // Fix some Data Type Issues
        $query = str_replace('""', 'null', $query);
        $query = preg_replace('/"(\d+)"/i', '$1', $query);

        if(DO_INSERT){
            if ($mysqli->query($query)) {
                $message = array();
                foreach(DB_COLUMNS as $keyName){
                    $message[] = "'{$json['properties'][$keyName]}' for key '{$keyName}'";
                }

                echo "✓ Added entry " . join(', ', $message) . PHP_EOL;
            } else {
                printf("× %s\n", $mysqli->error);
            }
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
            createInsert($mysqli, $feature);
        }
    } else if ($json['type'] === 'Feature') {
        createInsert($mysqli, $json);
    } else {
        exit('Invalid GeoJSON');
    }
}

/* Close Connection */
$mysqli->close();