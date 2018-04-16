<?php
namespace Core;

if (isset($_GET['session_destroy']) && $_GET['session_destroy'] === 'true') {
    session_destroy();
    header('Location: index.php');
}

$time = explode(' ', microtime());
$start = $time[1] + $time[0];

require_once('php_init.php');
require_once('config.php');
require_once('Route.php');
require_once('database.php');
require_once('cache.php');
require_once('functions.php');
require_once('variables.php');
require_once('tables.php');

$home_dir = rtrim(dirname(__FILE__),"\/Core\/");
$filepath = str_replace('\\', '/', $home_dir);
$docroot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$filedir = str_replace($docroot, '', $filepath);
$protocol_g = "http://"; 
$home_url = $protocol_g . $_SERVER['HTTP_HOST'] . "$filedir";

defined('__HOME_DIR__') ? null : define('__HOME_DIR__', $filepath);
defined('__HOME_URL__') ? null : define('__HOME_URL__', $home_url);	

defined('__MODELS_DIR__') ? null : define('__MODELS_DIR__', __HOME_DIR__ . '/App/Models/');
defined('__VIEWS_DIR__') ? null : define('__VIEWS_DIR__', __HOME_DIR__ . '/App/Views/');
defined('__CORE_DIR__') ? null : define('__CORE_DIR__', __HOME_DIR__ . '/Core/');

// Necesary Array for SQL Bind Params
$p = [];