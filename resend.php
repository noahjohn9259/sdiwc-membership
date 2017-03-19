<?php

include('config.php');
require_once('../header.php');

$isResend = false;


if(isset($_GET['email']) && isset($_GET['token'])) {
	$email = $_GET['email'];
	$token = $_GET['token'];

	$user = getUserByEmail($email);

	if(!empty($user)) {
		if($user['token'] == $token) {
			$userId = (int)$user['id'];
			$isResend = true;
			sendVerifyUser($userId);
		}
	}
}

?>
<?php if($isResend) : ?>
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