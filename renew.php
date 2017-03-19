<?php

include('config.php');

if(isset($_GET['email']) && isset($_GET['token'])) {
	$remoteUserData = array(
		'email' => $_GET['email'],
		'ip' => $_SERVER['REMOTE_ADDR']
	);

	$remoteUserDataSerialize = serialize($remoteUserData);
	$hashCode = hash('sha256', $remoteUserDataSerialize . SECRET_CODE);
	$email = $_GET['email'];
	$user = getUserByEmail($email);
	$userExists = false;

	$token = $_GET['token'];

	if(!empty($user) && $token === $hashCode) {

		// _dump_var($token);
		// _dump_var($_GET);

		sendMembershipCert($user);
		$userExists = true;
		$target = $_SERVER['PHP_SELF'];
		header("Location: ".$target."?action=success");
	}
}
require_once('../header.php');
?>
<?php if(isset($_GET['action']) && $_GET['action'] === 'success') : ?>
<div class="alert alert-success">
	<h3><span class="fa fa-check" style="color: #43AC6A"></span>Thank you!</h3>
	<p>You have renew your membership for year <strong><?php echo date('Y'); ?></strong>. Please check your email for your certification.</p>
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