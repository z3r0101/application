<?php
/*
 * z3r0101
 *
 * An open source application development framework for PHP
 *
 * @package:    z3r0101
 * @author:     ryanzkizen@gmail.com
 * @version:    Beta 1.0
 *
 *
 *
 */

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

define('WWWPATH', realpath('').'/');

$application_folder = "application";
$application_folder = realpath($application_folder);
$application_folder = rtrim($application_folder, '/').'/'; #ensure there's a trailing slash
define('APPPATH', $application_folder);

$resources_folder = "application/resources";
$resources_folder = realpath($resources_folder);
$resources_folder = rtrim($resources_folder, '/').'/'; #ensure there's a trailing slash
define('RESPATH_CMS', $resources_folder);

$resources_folder = "www/resources";
$resources_folder = realpath($resources_folder);
$resources_folder = rtrim($resources_folder, '/').'/'; #ensure there's a trailing slash
define('RESPATH_WWW', $resources_folder);

$resources_folder = "resources";
$resources_folder = realpath($resources_folder);
$resources_folder = rtrim($resources_folder, '/').'/'; #ensure there's a trailing slash
define('RESPATH', $resources_folder);

$vendors_folder = "vendors";
$vendors_folder = realpath($vendors_folder);
$vendors_folder = rtrim($vendors_folder, '/').'/'; #ensure there's a trailing slash
define('VENDORSPATH', $vendors_folder);

require_once 'system/system.php';