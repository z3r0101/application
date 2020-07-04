<?php
/*
 * z3r0101
 *
 * https://github.com/z3r0101
 *
 * An open source application development framework for PHP
 *
 * @package:    z3r0101
 * @author:     ryanzkizen@gmail.com
 * @version:    Beta 1.0
 *
 */

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

define('SITEROOTPATH', rtrim(realpath(''), 'www'));

$application_folder = SITEROOTPATH."application";
$application_folder = realpath($application_folder);
$application_folder = rtrim($application_folder, '/').'/'; #ensure there's a trailing slash
define('APPPATH', $application_folder);

$resources_folder = SITEROOTPATH."application/resources";
$resources_folder = realpath($resources_folder);
$resources_folder = rtrim($resources_folder, '/').'/'; #ensure there's a trailing slash
define('RESPATH_CMS', $resources_folder);

$resources_folder = SITEROOTPATH."www/resources";
$resources_folder = realpath($resources_folder);
$resources_folder = rtrim($resources_folder, '/').'/'; #ensure there's a trailing slash
define('RESPATH_WWW', $resources_folder);

$resources_folder = SITEROOTPATH."application/resources";
$resources_folder = realpath($resources_folder);
$resources_folder = rtrim($resources_folder, '/').'/'; #ensure there's a trailing slash
define('RESPATH', $resources_folder);

$vendors_folder = SITEROOTPATH."vendors";
$vendors_folder = realpath($vendors_folder);
$vendors_folder = rtrim($vendors_folder, '/').'/'; #ensure there's a trailing slash
define('VENDORSPATH', $vendors_folder);

$vendors_folder = SITEROOTPATH."www/vendors";
$vendors_folder = realpath($vendors_folder);
$vendors_folder = rtrim($vendors_folder, '/').'/'; #ensure there's a trailing slash
define('VENDORSPATH_WWW', $vendors_folder);

$vendors_folder = SITEROOTPATH."assets";
$vendors_folder = realpath($vendors_folder);
$vendors_folder = rtrim($vendors_folder, '/').'/'; #ensure there's a trailing slash
define('ASSETSPATH', $vendors_folder);

$vendors_folder = SITEROOTPATH."assets/uploads";
$vendors_folder = realpath($vendors_folder);
$vendors_folder = rtrim($vendors_folder, '/').'/'; #ensure there's a trailing slash
define('UPLOADSPATH', $vendors_folder);

$vendors_folder = SITEROOTPATH."assets/uploads/temp";
$vendors_folder = realpath($vendors_folder);
$vendors_folder = rtrim($vendors_folder, '/').'/'; #ensure there's a trailing slash
define('UPLOADSTEMPPATH', $vendors_folder);

$resources_folder = SITEROOTPATH."www";
$resources_folder = realpath($resources_folder);
$resources_folder = rtrim($resources_folder, '/').'/'; #ensure there's a trailing slash
define('WWWPATH', $resources_folder);

require_once 'system/system.php';