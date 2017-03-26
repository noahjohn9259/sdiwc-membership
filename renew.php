<?php

include('config.php');

$isRenew = false;
$showRenewForm = true;
$isActivated = true;
$accountRenewed = false;
$isAccountValid = false;
$sendRenewEmail = false;
$renewEmailSent = false;
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
		sendMembershipCert($user);
		$userExists = true;
		$target = $_SERVER['PHP_SELF'];
		// header("Location: ".$target."?action=success");
	}
}



if(!empty($_POST) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renewForm'])) {
	$postFields = $_POST;
	if(empty($postFields['email'])) {
		$errors['email'] = 'This field is required.';
	} else {
		if(!validateEmail($postFields['email'])) {
			$errors['email'] = 'Please enter a valid email address.';
		}
	}

	if(empty($errors)) {

		$email = $postFields['email'];
		$userData = getUserByEmail($email);

		if(!empty($userData)) {

			// _dump_var($userData);

			$membershipValidDate = $userData['membershipValidDate'];

			if($userData['accountStatus'] != 'PENDING') {
				$membershipValidDateTimeStamp = strtotime($membershipValidDate);
				$membershipYear = date('Y', $membershipValidDateTimeStamp);
				$yearNow = date('Y');

				if($yearNow == $membershipYear) {
					$isAccountValid = true;
					// sendCertificationForRenewal($userData);
					// header('Location: '.home_url('members/renew.php?valid=true'));
				} else {

					$renewEmailSent = true;

					$userId = (int)$userData['id'];
					$token = hash('sha256', $userId . SECRET_CODE . time());
					$userData['token'] = $token;
					$data = array(
						'userId' => $userId,
						'token' => $token
					);
					updateToken($data);
					sendVerificationEmailForRenewal($userData);

				}
				// updateToken($data, 'asdf1234_members');
				// resendCertification($userId);

				// header('Location: ' . home_url('members/renew.php?sent=true'));
			} else if(!empty($membershipValidDate)){
				$isActivated = false;
			}
		}
		$errorNotFound = true;
		$showRenewForm = false;
	}
}


if(isset($_GET['action']) && $_GET['action'] === 'renew' && isset($_GET['email']) && isset($_GET['token'])) {
	$email = htmlspecialchars($_GET['email']);
	$token = htmlspecialchars($_GET['token']);
	$userData = getUserByEmail($email);

	if(!empty($userData)) {
		if($userData['token'] === $token) {
			$userData['membershipValidDate'] = date('Y').'-12-31"';
			if(updateMembership($userData)) {
				sendCertificationForRenewal($userData);

				header('Location: '.home_url('members/renew.php?success=true'));
			}
		}
	}
} else if(isset($_GET['action']) && $_GET['action'] === 'resendCertification') {


	if(isset($_GET['email']) && !empty($_GET['email'])) {
		
		$email = $_GET['email'];
		$userData = getUserByEmail($email);

		if(!empty($userData)) {

			$membershipValidDate = $userData['membershipValidDate'];

			// _dump_var($userData);

			if($userData['accountStatus'] != 'PENDING') {
				$membershipValidDateTimeStamp = strtotime($membershipValidDate);
				$membershipYear = date('Y', $membershipValidDateTimeStamp);
				$yearNow = date('Y');

				if($yearNow == $membershipYear) {

					// _dump_var($userData);
					$sendRenewEmail = true;
					$showRenewForm = false;

					$userId = (int)$userData['id'];
					$token = hash('sha256', $userId . SECRET_CODE . time());
					$userData['token'] = $token;
					$data = array(
						'userId' => $userId,
						'token' => $token
					);
					updateToken($data);
					sendCertificationForRenewal($userData);
				}
			}
		}
	}
}

if(isset($_GET['success']) && $_GET['success'] == 'true') {
	$showRenewForm = false;
} else if(isset($_GET['valid']) && $_GET['valid'] == 'true'){
	$showRenewForm = false;
	$isAccountValid = true;
}

require_once('../header.php');
?>
<div class="row">
	<div class="col-md-6 col-md-offset-3">
		<?php if(isset($_GET['success']) && $_GET['success'] == 'true') : ?>
		<div class="alert alert-success">
			<h3><span class="fa fa-check" style="color: #43AC6A"></span>Congratulations!</h3>
			<p>You have renew your membership for year <strong><?php echo date('Y'); ?></strong>. Please check your email for your certification.</p>
		</div>
		<?php endif; ?>

		<?php if(!$isActivated) : ?>
		<div class="alert alert-danger">
			<h3><span class="fa fa-times" style="color: #C62828"></span>Failed!</h3>
			<p>This account is not yet activated. Do you want to resend your activation e-mail?<br></p>
			<br>
			<p><a class="btn btn-primary btn-md" href="<?php echo home_url('members/index.php?action=resendActivation&email=' . $_POST['email']); ?>">Resend Activation</a></p>
		</div>
		<?php endif; ?>

		<?php if($renewEmailSent) : ?>
		<div class="alert alert-info">
			<h3 style="color: #0D47A1;"><span class="fa fa-check" style="color: #1565C0"></span>Sent</h3>
			<p>Please check your email to verify your renewal.<br></p>
		</div>
		<?php endif; ?>

		<?php if($sendRenewEmail) : ?>
		<div class="alert alert-info">
			<h3 style="color: #0D47A1;"><span class="fa fa-check" style="color: #1565C0"></span>Email Sent!</h3>
			<p>We have sent you an email. Check your email to proceed.<br></p>
		</div>
		<?php endif; ?>

		<?php if($isAccountValid) : ?>
		<div class="alert alert-info">
			<h3 style="color: #0D47A1;"><span class="fa fa-check" style="color: #1565C0"></span>Thank you!</h3>
			<p>Your account is still valid until December 31, <?php echo $membershipYear;?>.<br></p>
			<p>Do you want to us resend your certication?<br></p>
			<p><a class="btn btn-primary btn-md" href="<?php echo home_url('members/renew.php?action=resendCertification&email=' . $_POST['email']); ?>">Resend Certification</a></p>
		</div>
		<?php endif; ?>

		<?php if($showRenewForm) : ?>
		<form id="renewMembershipForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<h4>Please enter your registered e-mail. An e-mail will be sent to you.</h4>
			<div class="form-group <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
				<label class="control-label" for="email">E-mail</label>
				<input type="text" name="email" value="<?php echo isset($_POST['email']) && !empty($errors) ? $_POST['email'] :
					'';
				?>" class="form-control">
				<?php if(isset($errors['email'])) : ?>
					<span id="email-error" class="help-block"><?php echo $errors['email']; ?></span>
				<?php endif; ?>
			</div>
			<div class="form-footer">
				<input type="hidden" name="renewForm" value="">
				<input type="submit" name="submit" value="Submit" class="btn btn-primary btn-lg">
			</div>
		</form>
		<?php endif; ?>
	</div>
</div>
<?php
require_once("../footer.php");
?>