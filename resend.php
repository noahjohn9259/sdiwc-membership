<?php

include('config.php');

$isResend = false;


if(isset($_GET['success']) && $_GET['success'] == 'true') {
	$isResend = true;
}

if(isset($_GET['email']) && isset($_GET['token'])) {
	$email = $_GET['email'];
	$token = $_GET['token'];

	$user = getUserByEmail($email);

	if(!empty($user)) {
		if($user['token'] == $token) {
			$userId = (int)$user['id'];
			$isResend = true;
			resendCertification($userId);

			header('Location: '.home_url('members/resend.php?success=true'));
		}
	}
}
require_once('../header.php');

?>
<div class="row">
	<div class="col-md-6 col-md-offset-3">
		<?php if($isResend) : ?>
		<div class="alert alert-success">
			<h3><span class="fa fa-check" style="color: #43AC6A"></span>Thank you!</h3>
			<p>We have resend your certification. Please check your email.</p>
		</div>
		<?php else : ?>
		<div class="alert alert-danger">
			<h3><span class="fa fa-times" style="color: #B90000"></span>Failed!</h3>
			<p>We couldn't process your request.</p>
		</div>
		<?php endif; ?>
	</div>
</div>
<?php
require_once("../footer.php");
?>