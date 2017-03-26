<?php

include('config.php');
$memberRequiredFields = array('firstName', 'lastName', 'email');
$errors = [];
$showForm = false;
$subscriptionList = array();

$showUpdateForm = true;

$errorNotFound = false;


// echo ABSPATH;

if($_GET['success'] == 'true' || $_GET['sent'] == 'true') {
	$showUpdateForm = false;
	$showForm = false;
}


if(isset($_GET['email']) && isset($_GET['token'])) {
	$email = $_GET['email'];
	$token = $_GET['token'];

	$showUpdateForm = false;

	$user = getUserByEmail($email);

	if(!empty($user)) {
		if($user['token'] == $token) {
			$userId = (int)$user['id'];

			$showForm = true;

			if(!empty($user['subscriptionList'])) {
				$subsr = $user['subscriptionList'];
				$subs = unserialize($subsr);

				if(is_array($subs)) {
					$subscriptionList = $subs;
				}
			}

			$countries = getCountries();
			if(!empty($countries)) {
				$selectedCountry = isset($_POST['country']) ? $_POST['country'] : $user['country'];
				$formatCountriesField = formatOptionSelectField($countries, $selectedCountry);
			}


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

					$memberData = array(
						'firstName' => isset($postFields['firstName']) ? $postFields['firstName'] : '',
						'lastName' => isset($postFields['lastName']) ? $postFields['lastName'] : '',
						'email' => isset($postFields['email']) ? $postFields['email'] : '',
						'work' => isset($postFields['work']) ? $postFields['work'] : '',
						'newsletter' => isset($postFields['newsletter']) ? $postFields['newsletter'] : 0,
						'country' => isset($postFields['country']) ? $postFields['country'] : '',
						'subscriptionList' => $subscriptions,
						'ip' => $_SERVER['REMOTE_ADDR'],
						'token' => ''
					);

					$memberRes = updateData($memberData, $user['id'], MEMBERS_TABLE);

					if($memberRes > 0) {
						sendCert($memberData);
						header('Location: ' . home_url('members/update.php?success=true'));
					}
					
					$showForm = false;

				} else {

					if(!empty($user['subscriptionList'])) {
						$subsr = $user['subscriptionList'];
						$subs = unserialize($subsr);

						if(is_array($subs)) {
							$subscriptionList = $subs;
						}
					}
				}
			}
		}
	}
}

if(!empty($_POST) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateForm'])) {
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
			$userId = (int)$userData['id'];

			$data = array(
				'userId' => $userId,
				'token' => hash('sha256', $userId . SECRET_CODE . time())
			);
			updateToken($data, 'asdf1234_members');
			resendCertification($userId);

			header('Location: ' . home_url('members/update.php?sent=true'));
		}
		$errorNotFound = true;
		$showUpdateForm = false;
	}
}

require_once('../header.php');

?>

<div class="row">
	<div class="col-md-6 col-md-offset-3">
		<?php if(isset($_GET['success']) && $_GET['success'] == 'true') : ?>
			<div class="alert alert-success">
				<h4>Thank you!</h4>
				<p>Please check your email for your certification.</p>
			</div>
		<?php endif; ?>
		<?php if($_GET['sent'] == 'true') : ?>
			<div class="alert alert-info">
				<h4>Thank you!</h4>
				<p>We have sent an email containing the edit link.</p>
			</div>
		<?php endif; ?>
		<?php if($errorNotFound) : ?>
			<div class="alert alert-danger">
				<h4>Failed!</h4>
				<p>Sorry, this is e-mail is not registered. You can register <a href="<?php echo home_url('members/index.php') ?>">here</a>.</p>
			</div>
		<?php endif; ?>
		<?php if($showUpdateForm) : ?>
		<form id="updateMembershipForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<h4>An email will be sent after you enter your email.</h4>
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
				<input type="hidden" name="updateForm" value="">
				<input type="submit" name="submit" value="Submit" class="btn btn-primary btn-lg">
			</div>
		</form>
		<?php endif; ?>
		<?php if($showForm) : ?>
			<form id="membershipForm" action="<?php echo home_url(untrailingslashit($_SERVER['REQUEST_URI']));?>" method="post">
			<div class="form-group <?php echo isset($errors['firstName']) ? 'has-error' : ''; ?>">
				<label class="control-label" for="firstName">First Name</label>
				<input type="text" name="firstName" value="<?php echo isset($_POST['firstName']) ?
					$_POST['firstName'] : $user['firstName']; ?>" class="form-control">
				<?php if(isset($errors['firstName'])) : ?>
					<span id="firstName-error" class="help-block"><?php echo $errors['firstName']; ?></span>
				<?php endif; ?>
			</div>
			<div class="form-group <?php echo isset($errors['lastName']) ? 'has-error' : ''; ?>">
				<label class="control-label" for="lastName">Last Name</label>
				<input type="text" name="lastName" value="<?php echo isset($_POST['lastName']) ?
					$_POST['lastName'] : $user['lastName']; ?>" class="form-control">
				<?php if(isset($errors['lastName'])) : ?>
					<span id="lastName-error" class="help-block"><?php echo $errors['lastName']; ?></span>
				<?php endif; ?>
			</div>
			<div class="form-group <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
				<label class="control-label" for="email">E-mail</label>
				<input type="text" name="email" value="<?php echo isset($_POST['email']) ?
					$_POST['email'] : $user['email']; ?>" class="form-control">
				<?php if(isset($errors['email'])) : ?>
					<span id="email-error" class="help-block"><?php echo $errors['email']; ?></span>
				<?php endif; ?>
			</div>
			<div class="form-group">
				<label class="control-label" for="work">University/Workplace</label>
				<input type="text" name="work" value="<?php echo isset($_POST['work']) ?
					$_POST['work'] : $user['work']; ?>" class="form-control">
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
						<?php echo isset($_POST['newsletter']) || isset($user['newsletter']) ? ' checked="checked" ' : ''; ?> value="1"> Subscribe to newsletter</label>
				<div class="interestedIn" style="<?php echo isset($_POST['newsletter']) || isset($user['newsletter']) ? 'display: block;' : 'display: none;'; ?>">
					<ul>
						<li><label><input type="checkbox" name="subscriptionList[]" <?php echo in_array('security', $subscriptionList) ? 'checked="checked"' : ''; ?> value="security"> Security Conferences</label></li>
						<li><label><input type="checkbox" name="subscriptionList[]" <?php echo in_array('engineering', $subscriptionList) ? 'checked="checked"' : ''; ?> value="engineering"> Engineering Conferences</label></li>
						<li><label><input type="checkbox" name="subscriptionList[]" <?php echo in_array('elearning', $subscriptionList) ? 'checked="checked"' : ''; ?> value="elearning"> E-learning Conferences</label></li>
						<li><label><input type="checkbox" name="subscriptionList[]" <?php echo in_array('computerScience', $subscriptionList) ? 'checked="checked"' : ''; ?> value="computerScience"> Computer Science Conferences</label></li>
					</ul>
				</div>
			</div>
			<div class="form-footer">
				<input type="submit" name="submitMembership" value="Submit" class="btn btn-primary btn-lg">
			</div>
			</form>
		<?php endif; ?>
	</div>
</div>



<?php
require_once("../footer.php");
?>