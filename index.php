<?php

include('config.php');
// include(dirname(__FILE__) . '/vendor/phpoffice/phpword/bootstrap.php');
// require_once(dirname(__FILE__) . '/vendor/autoload.php');
// require_once (dirname(__FILE__) . '/vendor/PhpWord/bootstrap.php');
// // \PhpOffice\PhpWord\Autoloader::register();
// echo dirname(__FILE__) . '/vendor/phpoffice/phpword/bootstrap.php';
$memberRequiredFields = array('firstName', 'lastName', 'email');
$errors = [];
$showForm = true;
$userExists = false;
$verified = false;

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
			'activationCode' => hash('sha256', $postFields['firstName'] . $postFields['lastName'] . $postFields['email']
			                                     . 'sdiwc'),
			'accountStatus' => 'PENDING',
			'subscriptionList' => $subscriptions,
			'ip' => $_SERVER['REMOTE_ADDR']
		);

//		if(checkMemberExistsByEmail($memberData['email'])) {
//
//		}
		
//		_dump_var($_POST);
		
		if(checkMemberExistsByEmail($memberData['email'])) {
			$errors['emailExists'] = true;
			$showForm = false;
		// } else if(checkMemberExistsByFullName($memberData['firstName'], $memberData['lastName'])){
		// 	$showForm = false;
		// 	$userExists = true;
		} else {
			// _dump_var($memberData);
			$memberRes = saveData($memberData, 'asdf1234_members');
      $memberRes = 1;
			if($memberRes > 0) {
				sendVerificationEmail($memberData);
			}
			
			$showForm = false;
		}
	}
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
<div id="alert" style="display:none;color:red">Email Already exists. Please refer to this link to renew your membership. <a href="http://sdiwc.net/renewal.php">Renewal</a></div>

<?php if($showForm) : ?>
<h1>SDIWC Membership</h1>
<p id="drop-cap" align="justify">Join SDIWC and get all the benefits of its membership. SDIWC membership is open to all individuals who believe in using technology for human advancement. By joining, you can attend all of the conferences organized by SDIWC at no charge, provided that you do not have a paper to publish. In addition, you will be able to get many discounts on all other activities. The membership fees help  to run SDIWC. Membership for the year <?php echo date('Y'); ?> is free, just fill up the following form:</p>
<br>
<?php endif; ?>
<?php if(isset($_POST) && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) : ?>
	<div class="alert alert-danger" role="alert">
		<h3>Failed!</h3>
		<?php
		$resendUrl = home_url
			('/members/resend.php?email=' . ($memberData['email']));
		?>
		<p><strong><?php echo $memberData['email'] ?></strong> is already registered with SDIWC. Do you want to resend your certification? <a href="<?php echo ($resendUrl); ?>">Click here!</a></p>
	</div>
<?php endif; ?>
<?php if(isset($_POST) && $_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) : ?>
	<div class="alert alert-success" role="alert">
		<h3>Verify Your E-mail Address</h3>
		<p>We now need to verify your email address. We've sent an email to <strong><?php echo $postFields['email']; ?></strong>. Please click the link in that email to continue.</p>
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
		<p>We have sent you your certification to your e-mail address.</p>
	</div>
<?php endif; ?>
<div class="row">
	<div class="col-md-4 col-md-offset-4">
		
		<?php if($showForm) : ?>
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
			</div><div class="form-group">
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
		<?php endif; ?>
	</div>
</div>
<?php
require_once("../footer.php");
?>

