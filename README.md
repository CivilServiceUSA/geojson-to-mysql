![Civil Services Logo](https://cdn.civil.services/common/github-logo.png "Civil Services Logo")

Civil Services API
===

[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](https://raw.githubusercontent.com/CivilServiceUSA/geojson-to-mysql/master/LICENSE) [![GitHub contributors](https://img.shields.io/github/contributors/CivilServiceUSA/geojson-to-mysql.svg)](https://github.com/CivilServiceUSA/geojson-to-mysql/graphs/contributors)

__Civil Services__ is a collection of tools that make it possible for citizens to be a part of what is happening in their Local, State & Federal Governments.


GeoJSON to MySQL
---

Takes geojson files and imports them into MySQL

Copy `config.ini.dist` to `config.ini` and add your own values to it.

#### Usage:

To write insert statements out to a file:

```bash
php import.php --table district_shapes --keys state_code,district --dir /home/tmp/districts > insert.sql

```

To execute the insert statements directly:

```bash
php import.php --table district_shapes --keys state_code,district --dir /home/tmp/districts --insert
```