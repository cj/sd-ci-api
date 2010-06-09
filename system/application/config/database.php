<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the "Database Connection"
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the "default" group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = DB_DEFAULT;
$active_record = TRUE;

$db[DB_DEFAULT]['hostname'] = DB_HOST;
$db[DB_DEFAULT]['username'] = DB_USER;
$db[DB_DEFAULT]['password'] = DB_PASS;
$db[DB_DEFAULT]['database'] = DB_DEFAULT;
$db[DB_DEFAULT]['dbdriver'] = "mysql";
$db[DB_DEFAULT]['dbprefix'] = "";
$db[DB_DEFAULT]['pconnect'] = TRUE;
$db[DB_DEFAULT]['db_debug'] = TRUE;
$db[DB_DEFAULT]['cache_on'] = FALSE;
$db[DB_DEFAULT]['cachedir'] = "";
$db[DB_DEFAULT]['char_set'] = "utf8";
$db[DB_DEFAULT]['dbcollat'] = "utf8_general_ci";

$db[DB_SD_API]['hostname'] = DB_HOST;
$db[DB_SD_API]['username'] = DB_USER;
$db[DB_SD_API]['password'] = DB_PASS;
$db[DB_SD_API]['database'] = DB_SD_API;
$db[DB_SD_API]['dbdriver'] = "mysql";
$db[DB_SD_API]['dbprefix'] = "";
$db[DB_SD_API]['pconnect'] = TRUE;
$db[DB_SD_API]['db_debug'] = TRUE;
$db[DB_SD_API]['cache_on'] = FALSE;
$db[DB_SD_API]['cachedir'] = "";
$db[DB_SD_API]['char_set'] = "utf8";
$db[DB_SD_API]['dbcollat'] = "utf8_general_ci";


/* End of file database.php */
/* Location: ./system/application/config/database.php */