<?php
define('USER_ENV', ($_SERVER['SERVER_ADDR'] === '127.0.0.1' ? 'development' : 'production'));

define('VERSION_ID', 2);

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
		'MAIL_FROM' => 'membership@sdiwc.net',
		'MAIL_FROM_NAME' => 'SDIWC',
	],
	'production' => [
		'ADMIN_EMAIL' => 'sdiwc@sdiwc.net',
		'MAIL_FROM' => 'membership@sdiwc.net',
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
include('includes/members.php');

