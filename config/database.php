<?php // config/database.php

# NOTE: This is required, because this file might be included from within the __autoload() function. Without this, these would not be global.
global $DATABASE;

$DATABASE = array();
$DATABASE['adapter']   = 'mysql';
$DATABASE['host']      = 'localhost';
$DATABASE['database']  = 'quickquote_development';
$DATABASE['user_name'] = 'root';
$DATABASE['password']  = '';
$DATABASE['connection']  = null;

