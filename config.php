<?php
define('USER_ENV', ($_SERVER['SERVER_ADDR'] === '127.0.0.1' ? 'development' : 'production'));

define('VERSION_ID', 2);
define('EXPIRE_DURATION', 7200);
define('SECRET_CODE', 'njucab.sdiwc');

defined('ABS_PATH') or define('ABS_PATH', dirname(__FILE__));
defined('SD_DROOT') or define('SD_DROOT', $_SERVER['DOCUMENT_ROOT']);
defined('CERT_PATH') or define('CERT_PATH', SD_DROOT . '/certifications/');

define('BCC_EMAIL', 'njucab.sdiwc@gmail.com');

/* Tables */
define('MEMBERS_TABLE', 'asdf1234_members');

$dbConfig = [
	'development' => [
		'DBHOST' => 'localhost',
		'USER' => 'root',
		'PASSWORD' => '',
		'DBNAME' => 'sdiwcn5_maindb'
	],
	'production' => [
		'DBHOST' => 'localhost',
		'USER' => 'sdiwcn5_admin',
		'PASSWORD' => 'IrbidAhmed1200',
		'DBNAME' => 'sdiwcn5_membership'
	],
];

$mailConfig = [
	'development' => [
		'ADMIN_EMAIL' => 'sdiwc@sdiwc.net',
		'MAIL_FROM' => 'sdiwc@sdiwc.net',
		'MAIL_FROM_NAME' => 'SDIWC',
	],
	'production' => [
		'ADMIN_EMAIL' => 'sdiwc@sdiwc.net',
		'MAIL_FROM' => 'sdiwc@sdiwc.net',
		'MAIL_FROM_NAME' => 'The Society of Digital Information and Wireless Communications (SDIWC)',
	],
];

$subscriptionListArr = array(
	'security' => 'Security Conferences',
	'engineering' => 'Engineering Conferences',
	'elearning' => 'E-learning Conferences',
	'computerScience' => 'Computer Science Conferences'
);

$configMain = $dbConfig[USER_ENV];
$mailMain = $mailConfig[USER_ENV];


$dbmain = new mysqli($configMain['DBHOST'], $configMain['USER'], $configMain['PASSWORD'], $configMain['DBNAME']);
if ($dbmain->connect_errno) {
	echo "Failed to connect to MySQL: (" . $dbmain->connect_errno . ") " . $dbmain->connect_error;
}
global $dbmain, $mailMain, $subscriptionListArr;

include('includes/helpers/functions.php');
include('../includes/functions-include.php');
require_once('libraries/PHPMailer/PHPMailerAutoload.php');
include('includes/members.php');

