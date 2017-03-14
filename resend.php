<?php

include('config.php');
require_once('../header.php');


if(!empty($_GET)) {
	$token = isset($_GET['token']) ? $_GET['token'] : '';
	$email = isset($_GET['email']) ? $_GET['email'] : '';
}

$email = $_GET['email'];

$user = getUserByEmail($email);
$userExists = false;

if(!empty($user)) {
	// _dump_var($user);
	$userId = (int)$user['id'];
	sendCertification($userId);
	$userExists = true;
}

?>
<?php if($userExists) : ?>
<div class="alert alert-success">
	<h3><span class="fa fa-check" style="color: #43AC6A"></span>E-mail sent!</h3>
	<p>We have sent you your certification. Please check your email.</p>
</div>
<?php else : ?>
<div class="alert alert-danger">
	<h3><span class="fa fa-times" style="color: #B90000"></span>Failed!</h3>
	<p>We cannot process your request.</p>
</div>
<?php endif; ?>
<?php
require_once("../footer.php");
?>