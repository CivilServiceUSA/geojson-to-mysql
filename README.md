# geojson-to-mysql
Takes geojson files and imports them into MySQL

Copy `config.ini.dist` to `config.ini` and add your own values to it.

Usage:

To write insert statements out to a file:
`php import.php --table district_shapes --keys state_code,district --dir /home/tmp/districts > insert.sql`

To execute the insert statements directly:
`php import.php --table district_shapes --keys state_code,district --dir /home/tmp/districts --insert`