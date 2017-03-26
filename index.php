<?php

include('config.php');
$memberRequiredFields = array('firstName', 'lastName', 'email');
$errors = [];
$showForm = true;
$userExists = false;
$verified = false;
$expired = false;
$resend = false;
$verifyEmailSent = false;

if(!empty($_POST) && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$postFields = $_POST;
	$postData = [];
//    var_dump($postFields);
	if(empty($postFields['firstName'])) {
		$errors['firstName'] = 'This field is required.';
	}
	if(empty($postFields['lastName'])) {
		$errors['lastName'] = 'This field is required.';
	}
	if(empty($postFields['email'])) {
		$errors['email'] = 'This field is required.';
	} else {
		if(!validateEmail($postFields['email'])) {
			$errors['email'] = 'Please enter a valid email address.';
		}
	}
	
	if(empty($errors)) {

		$subscriptions = '';
		if(isset($_POST['subscriptionList'])) {
			$subscriptionList = $_POST['subscriptionList'];

			if(!empty($subscriptionList) && is_array($subscriptionList)) {
				$subscriptions = serialize($subscriptionList);
			}
		}

		// $userInfo = array(
		// 	'firstName' => $postFields['firstName'],
		// 	'lastName' => $postFields['firstName'],
		// 	);


		$memberData = array(
			'firstName' => isset($postFields['firstName']) ? $postFields['firstName'] : '',
			'lastName' => isset($postFields['lastName']) ? $postFields['lastName'] : '',
			'email' => isset($postFields['email']) ? $postFields['email'] : '',
			'work' => isset($postFields['work']) ? $postFields['work'] : '',
			'newsletter' => isset($postFields['newsletter']) ? $postFields['newsletter'] : 0,
			'country' => isset($postFields['country']) ? $postFields['country'] : '',
			'activationCode' => hash('sha256', time() . SECRET_CODE),
			'accountStatus' => 'PENDING',
			'subscriptionList' => $subscriptions,
			'ip' => $_SERVER['REMOTE_ADDR']
		);
		
		if(checkMemberExistsByEmail($memberData['email'])) {
			$errors['emailExists'] = true;
			$showForm = false;

			$userData = getUserByEmail($memberData['email']);

			// _dump_var(VERSION_ID);

			// if($userData['versionId'] == 1) {
			// 	if(!empty($userData['membershipValidDate'])) {
			// 		$yearNow = date('Y');
			// 	} else {
			// 		$expired = true;
			// 	}
			// } else if(VERSION_ID == 2) {

			// }
				$membershipValidDate = $userData['membershipValidDate'];

				if(!empty($membershipValidDate)) {
					$membershipValidDateTimeStamp = strtotime($membershipValidDate);
					$membershipYear = date('Y', $membershipValidDateTimeStamp);
					$yearNow = date('Y');



					if($yearNow == $membershipYear) {
						// _dump_var($membershipYear);

						// _dump_var($yearNow);
						// _dump_var($membershipYear);
						// _dump_var($userData);
						$data = array(
							'userId' => $userData['id'],
							'token' => hash('sha256', $userData['id'] . SECRET_CODE . time())
						);
						updateToken($data, 'asdf1234_members');
						$resend = true;
					} else {
						$expired = true;

					}
				// if(!empty($userData['membershipValidDate'])) {
				// 	$yearNow = date('Y');
				// } else {
				// 	$expired = true;
				// }
				}

		} else {
			// _dump_var($memberData);
			$memberRes = saveData($memberData, 'asdf1234_members');
      // $memberRes = 1;
			if($memberRes > 0) {
				sendVerificationEmail($memberData);
			}
			
			$showForm = false;
		}
	}
}



if(isset($_GET['action']) && $_GET['action'] === 'resendActivation' && isset($_GET['email'])) {
	// _dump_var($_GET);
	$email = htmlspecialchars($_GET['email']);

	if(checkMemberExistsByEmail($email)) {
		$userData = getUserByEmail($email);

		// updateActivationCode

		$userData['activationCode'] = hash('sha256', time() . SECRET_CODE);
		$userData['membershipValidDate'] = date('Y').'-12-31"';

		updateActivationCode($userData);

		sendVerificationEmail($userData);

		header('Location: '.home_url('members/index.php?action=verify&success=true'));

	}
}

if(isset($_GET['action']) && $_GET['action'] === 'verify' && isset($_GET['success']) && $_GET['success'] === 'true') {
	$showForm = false;
}


$countries = getCountries();

// _dump_var($countries);

if(isset($_GET['action']) && $_GET['action'] === 'sendCertificate' && !empty($_POST)) {
	$showForm = false;
}
if(isset($_GET['action']) && $_GET['action'] === 'success') {
	$showForm = false;
}

if(isset($_GET['action']) && $_GET['action'] === 'verify') {
	if(isset($_GET['email']) && isset($_GET['activationCode'])) {
		$data = array(
			'email' => $_GET['email'],
			'activationCode' => $_GET['activationCode']
		);
		$userExists = checkVerification($data, 'asdf1234_members');
		if(!empty($userExists)) {
			$data = $userExists;
			$isApproved = verifyMembership($data, 'asdf1234_members');
			if($isApproved) {
				$userId = $data['id'];
				sendCertification($userId);
				$target = $_SERVER['PHP_SELF'];
				header("Location: ".$target."?action=success");
				$verified = true;
				$showForm = false;
			}
		}
	}
}

if(!empty($countries)) {
	$selectedCountry = isset($_POST['country']) && !empty($errors) ? $_POST['country'] : '';
	$formatCountriesField = formatOptionSelectField($countries, $selectedCountry);
}

require_once('../header.php');
?>

<?php if($showForm) : ?>
<h1>SDIWC Membership</h1>
<p id="drop-cap" align="justify">Join SDIWC and get all the benefits of its membership. SDIWC membership is open to all individuals who believe in using technology for human advancement. By joining, you can attend all of the conferences organized by SDIWC at no charge, provided that you do not have a paper to publish. In addition, you will be able to get many discounts on all other activities. The membership fees help  to run SDIWC. Membership for the year <?php echo date('Y'); ?> is free, just fill up the following form:</p>
<br>

<?php endif; ?>
<?php if(isset($_POST) && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors) && false) : ?>
	<div class="alert alert-danger" role="alert">
		<h3>Failed!</h3>
		<?php


		$resendUrl = home_url('/members/resend.php?email=' . $memberData['email']);
		?>
		<p><strong><?php echo $memberData['email'] ?></strong> is already registered with SDIWC. Do you want to resend your certification? <a href="<?php echo $resendUrl; ?>">Click here!</a></p>
	</div>
<?php endif; ?>



<div class="row">
	<div class="col-md-6 col-md-offset-3">
		<?php // ---------------------- EXPIRED ----------------------- ?>
		<?php if($expired) : ?>
			<div class="alert alert-warning" role="alert">
				<h3>Failed!</h3>
				<?php

				$remoteUserData = array(
					'email' => $memberData['email'],
					'ip' => $_SERVER['REMOTE_ADDR']
				);

				$remoteUserDataSerialize = serialize($remoteUserData);
				$token = hash('sha256', $remoteUserDataSerialize . SECRET_CODE);
				$renewLink = home_url
					('members/renew.php?email=' . $memberData['email'] .'&token=' . $token);
				?>
				<p>Your membership is already expired. Do you want to renew your membership?</p>
				<br/>
				<p><a href="<?php echo $renewLink; ?>" class="btn btn-primary">Renew my membership</a></p>
			</div>
		<?php endif; ?>
		<?php // ---------------------- EXPIRED ----------------------- ?>



		<?php // ---------------------- RESEND ----------------------- ?>
		<?php if($resend) : ?>
			<div class="alert alert-warning" role="alert">
				<h3>Sorry!</h3>
				<?php

				$token = hash('sha256', $userData['id'] . SECRET_CODE . time());
				$renewLink = home_url
					('members/resend.php?email=' . $memberData['email'] .'&token=' . $token);
				?>
				<p>Your membership for this year is valid. Do you want to resend your certification?</p>
				<br/>
				<p><a href="<?php echo $renewLink; ?>" class="btn btn-primary">Resend my membership</a></p>
				<br>
				<p><a href="<?php echo home_url('members/'); ?>"><strong><span class="fa fa-arrow-left"></span> Register with different email</strong></a></p>
			</div>
		<?php endif; ?>
		<?php // ---------------------- RESEND ----------------------- ?>







		<?php if(isset($_POST) && $_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) : ?>
			<div class="alert alert-success" role="alert">
				<h3>Verify Your E-mail Address</h3>
				<p>We now need to verify your email address. We've sent an email to <strong><?php echo $postFields['email']; ?></strong>.</p>
			</div>
		<?php endif; ?>

		<?php if(isset($_GET['action']) && $_GET['action'] === 'verify' && isset($_GET['success']) && $_GET['success'] === 'true') : ?>
			<div class="alert alert-success" role="alert">
				<h3>Verify Your E-mail Address</h3>
				<p>We now need to verify your email address. We've sent an email to <strong><?php echo $postFields['email']; ?></strong>.</p>
			</div>
		<?php endif; ?>

		<?php if(isset($_GET['action']) && $_GET['action'] === 'sendCertificate' && !empty($_POST)) : ?>
			<div class="alert alert-success" role="alert">
				<p>We have sent you your certification. <br> If you have concerns email us at <a href="mailto:sdiwc@sdiwc.net">sdiwc@sdiwc.net</a>.</p>
			</div>
		<?php endif; ?>
		<?php if(isset($_GET['action']) && $_GET['action'] === 'success') : ?>
			<div class="alert alert-success" role="alert">
				<h3>Thank you for joining SDIWC.</h3>
				<p>We have sent your certification to your e-mail address.</p>
			</div>
		<?php endif; ?>
		
		<?php if($showForm) : ?>
			<div class="alert alert-info">
				<p>Already a member? </p>
				<p><strong><a class="btn btn-primary btn-md btn-block" href="<?php echo home_url('members/update.php'); ?>">Update your membership</a></strong></p>
				<p> <strong><a class="btn btn-primary btn-md btn-block" href="<?php echo home_url('members/renew.php'); ?>">Re-new your membership</a></strong></p>
			</div>
			<form id="membershipForm" action="<?php echo $_SERVER['PHP_SELF'];?>" method="post"">
			<div class="form-group <?php echo isset($errors['firstName']) ? 'has-error' : ''; ?>">
				<label class="control-label" for="firstName">First Name</label>
				<input type="text" name="firstName" value="<?php echo isset($_POST['firstName']) && !empty($errors) ?
					$_POST['firstName'] : '';
				?>" class="form-control">
				<?php if(isset($errors['firstName'])) : ?>
					<span id="firstName-error" class="help-block"><?php echo $errors['firstName']; ?></span>
				<?php endif; ?>
			</div>
			<div class="form-group <?php echo isset($errors['lastName']) ? 'has-error' : ''; ?>">
				<label class="control-label" for="lastName">Last Name</label>
				<input type="text" name="lastName" value="<?php echo isset($_POST['lastName']) && !empty($errors) ?
					$_POST['lastName'] : '';
				?>" class="form-control">
				<?php if(isset($errors['lastName'])) : ?>
					<span id="lastName-error" class="help-block"><?php echo $errors['lastName']; ?></span>
				<?php endif; ?>
			</div>
			<div class="form-group <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
				<label class="control-label" for="email">E-mail</label>
				<input type="text" name="email" value="<?php echo isset($_POST['email']) && !empty($errors) ? $_POST['email'] :
					'';
				?>" class="form-control">
				<?php if(isset($errors['email'])) : ?>
					<span id="email-error" class="help-block"><?php echo $errors['email']; ?></span>
				<?php endif; ?>
			</div>
			<div class="form-group">
				<label class="control-label" for="work">University/Workplace</label>
				<input type="text" name="work" value="<?php echo isset($_POST['work']) && !empty($errors) ?
					$_POST['work'] : '';
				?>" class="form-control">
			</div>
			<div class="form-group">
				<label class="control-label" for="country">Country</label>
				<select name="country" id="country" class="form-control">
					<option value=""></option>
					<?php echo $formatCountriesField; ?>
				</select>
			</div>
			<div class="form-group">
				<label class="control-label"><input type="checkbox" name="newsletter" id="newsletter"
						<?php echo isset($_POST['newsletter']) && !empty($errors) ? ' checked="checked" ' : ''; ?> value="1"> Subscribe to newsletter</label>
				<div class="interestedIn" style="display: none">
					<ul>
						<li><label><input type="checkbox" name="subscriptionList[]" value="security"> Security Conferences</label></li>
						<li><label><input type="checkbox" name="subscriptionList[]" value="engineering"> Engineering Conferences</label></li>
						<li><label><input type="checkbox" name="subscriptionList[]" value="elearning"> E-learning Conferences</label></li>
						<li><label><input type="checkbox" name="subscriptionList[]" value="computerScience""> Computer Science Conferences</label></li>
					</ul>
				</div>
			</div>
			<div class="form-footer">
				<input type="submit" name="submitMembership" value="Submit" class="btn btn-primary btn-lg">
			</div>
			</form>
			<br />
			
		<?php endif; ?>
	</div>
</div>
<?php
require_once("../footer.php");
?>

