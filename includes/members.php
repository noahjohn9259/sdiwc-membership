<?php
function getCountries() {
	global $dbmain;
	if ($result = $dbmain->query("SELECT countryName FROM asdf1234_countries")) {
		$data = [];
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
		
		$result->free();
		
		return $data;
	}
	return [];
}

function getUser($userId) {
	global $dbmain;
	$sql = "SELECT * FROM asdf1234_members WHERE id=$userId LIMIT 1";
	$result = $dbmain->query($sql);
	return $result->fetch_assoc();
}

function getUserByEmail($email) {
	global $dbmain;
	$email = mysqli_real_escape_string($dbmain, $email);
	$sql = "SELECT * FROM asdf1234_members WHERE email='$email' LIMIT 1";
	$result = $dbmain->query($sql);
	return $result->fetch_assoc();
}

function getLastCertificationId() {
	global $dbmain;
	$sql = "SELECT certificationId FROM asdf1234_members ORDER BY id DESC LIMIT 1";
	$result = $dbmain->query($sql);
	
	$result = $result->fetch_assoc();
	$certificationId = $result['certificationId'];
//	_dump_var($result);
//	exit;
	if(empty($certificationId)) return 17580;
	else return ++$certificationId;
}

function formatOptionSelectField($data, $selected = '') {
	$options = '';
	foreach ($data as $val) {
		$selectedAtt = $val['countryName'] === $selected ? ' selected="selected" ' : '';
		$options .= '<option '.$selectedAtt.' value="'.$val['countryName'].'">'.$val['countryName'].'</option>';
	}
	return $options;
}

function validateEmail($email) {
	return preg_match("/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$/", $email);
}

function sendVerificationEmail($userData = []) {
	
	global $mailMain;
	// Multiple recipients
	$to = $userData['email']; // note the comma
	
	// Subject
	$subject = 'E-mail Address Verification';
	
	$replaceFields = array(
		'{{email}}' => $userData['email'],
		'{{url}}' => home_url('members/'),
		'{{year}}' => date('Y'),
		'{{activationCode}}' => $userData['activationCode'],
	);
	
	// Message
	$message = file_get_contents(dirname(__FILE__) . "/mail/templates/emailVerify.html");
	
	foreach ($replaceFields as $key => $value) {
		$message = str_replace($key, $value, $message);
	}
//	_dump_var($message);
	
	// To send HTML mail, the Content-type header must be set
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'Content-type: text/html; charset=iso-8859-1';
	
	// Additional headers
	$headers[] = "From: ".$mailMain['MAIL_FROM_NAME']." <".$mailMain['MAIL_FROM'].">";
	// $headers[] = 'Bcc: njucab.sdiwc@yahoo.com';
	// $headers[] = 'Bcc: sdiwc@sdiwc.net';

	// Send notification to admin 

	// sendAdminNotification('', $userData);

	
	mail($to, $subject, $message, implode("\r\n", $headers));
}

function sendAdminNotification($userData = []) {

	global $mailMain, $subscriptionListArr;

	$to = $mailMain['ADMIN_EMAIL']; // note the comma

	// Subject
	$subject = 'New Membership (sdiwc.net)';

	// Message
	$message = '
	<html>
	<head>
	  <title>New Membership</title>
	</head>
	<body>
	  <h1>Member Details</h1>
	  <table>';
	$message .= '
	    <tr>
	      <th style="text-align:left;">First Name</th>
	      <td>'.$userData['firstName'].'</td>
	    </tr>
	    <tr>
	      <th style="text-align:left;">Last Name</th>
	      <td>'.$userData['lastName'].'</td>
	    </tr>
	    <tr>
	      <th style="text-align:left;">E-mail</th>
	      <td>'.$userData['email'].'</td>
	    </tr>
	    <tr>
	      <th style="text-align:left;">University/Workplace</th>
	      <td>'.$userData['work'].'</td>
	    </tr>
	    <tr>
	      <th style="text-align:left;">Country</th>
	      <td>'.$userData['country'].'</td>
	    </tr>';
	if(!empty($userData['newsletter'])) {
		$message .= '<tr><th style="text-align:left;">Subscription</th>';
		if(!empty($userData['subscriptionList'])) {

			$subscriptionList = unserialize($userData['subscriptionList']);

			// _dump_var($subscriptionList);

			if(is_array($subscriptionList)) {
				$message .= '<td><ul>';
				foreach ($subscriptionList as $key => $value) {
					// _dump_var($subscriptionListArr[$value]);
					$message .= '<li>'.$subscriptionListArr[$value].'</li>';
				}
				$message .= '</ul></td>';
			}
		} else {
			$message .= '<td>All</td>';
		}
		$message .= '</tr>';
	}
	$message .= '
	  </table>
	</body>
	</html>
	';

	// To send HTML mail, the Content-type header must be set
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'Content-type: text/html; charset=iso-8859-1';
	
	// Additional headers
	$headers[] = "From: ".$mailMain['MAIL_FROM_NAME']." <".$mailMain['MAIL_FROM'].">";
	$headers[] = 'Bcc: njucab.sdiwc@yahoo.com';
	// $headers[] = 'Bcc: sdiwc@sdiwc.net';

	mail($to, $subject, $message, implode("\r\n", $headers));
}

function sendCertification($userId) {
	// if(empty($userData)) return;
	
//	include("../includes/functions.php");
	include('certification.php');
	global $mailMain;
	
	$userData = getUser($userId);
	
	if(!empty($userData)) {
		
		$certificationId = $userData['certificationId'];
		// if(!empty($certificationId)) {
			$certificationId++;
			
			$certificationLink=generateCertificate($userData['firstName'], $userData['lastName'], $userData['email'], $userData['work'],
				$userData['country'], $certificationId);
			
			// Multiple recipients
			$to = $userData['email']; // note the comma
			
			// Subject
			$subject = 'Membership Certification';
			
			$replaceFields = array(
				'{{email}}' => $userData['email'],
				'{{year}}' => date('Y'),
				'{{certificationLink}}' => home_url('certifications/'.$certificationLink),
				'{{activationCode}}' => $userData['activationCode'],
			);
			
			// Message
			$message = file_get_contents(dirname(__FILE__) . "/mail/templates/emailApproved.html");
			
			foreach ($replaceFields as $key => $value) {
				$message = str_replace($key, $value, $message);
			}
			
//			_dump_var(home_url('certifications/'.$certificationLink));
//			exit;
			
			// To send HTML mail, the Content-type header must be set
			$headers[] = 'MIME-Version: 1.0';
			$headers[] = 'Content-type: text/html; charset=iso-8859-1';
			
			// Additional headers
			$headers[] = "From: ".$mailMain['MAIL_FROM_NAME']." <".$mailMain['MAIL_FROM'].">";
//			$headers[] = 'Cc: njucab.sdiwc@yahoo.com';
			// $headers[] = 'Bcc: njucab.sdiwc@yahoo.com';
			// $headers[] = 'Bcc: sdiwc@sdiwc.net';
			// $headers[] = 'Bcc: noahjohn.ucab@gmail.com';
			
			// Mail it
			mail($to, $subject, $message, implode("\r\n", $headers));

			sendAdminNotification($userData);
		// }
	}
}

function checkVerification($data, $table) {
	global $dbmain;
	$email = mysqli_real_escape_string($dbmain, $data['email']);
	$activationCode = mysqli_real_escape_string($dbmain, $data['activationCode']);
	$sql = "SELECT id FROM $table WHERE email='$email' AND activationCode='$activationCode' LIMIT 1";
	$result = $dbmain->query($sql);
	return $result->fetch_assoc();
}

function verifyMembership($data, $table) {
	global $dbmain;
	$id = $data['id'];
	$sql = "UPDATE $table SET accountStatus='APPROVED' WHERE id=$id";
	if($dbmain->query($sql) === true) {
		return true;
	}
	return false;
}

function saveData(&$data, $table) {
	global $dbmain;
	$cols = implode(',', array_keys($data));
	foreach (array_values($data) as $value)	{
		isset($vals) ? $vals .= ',' : $vals = '';
		$vals .= '\''.$dbmain->real_escape_string($value).'\'';
	}
	
	$cols .= ',certificationId';
	$vals .= ','.getLastCertificationId();

	$cols .= ',versionId';
	$vals .= ','.VERSION_ID;
	
//	exit('INSERT INTO '.$table.' ('.$cols.') VALUES ('.$vals.')');
	
	$dbmain->real_query('INSERT INTO '.$table.' ('.$cols.') VALUES ('.$vals.')');

	return $dbmain->insert_id;
}

function checkMemberExistsByEmail($email) {
	global $dbmain;
	if ($stmt = $dbmain->prepare("SELECT COUNT(*) FROM asdf1234_members WHERE email=? LIMIT 1")) {
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$stmt->bind_result($found);
		$stmt->fetch();
		$stmt->close();
//		echo '<pre>';
//		echo var_dump($userEmail);
//		echo '</pre>';
		return $found;
	}
}

function checkMemberExistsByFullName($firstName, $lastName) {
	global $dbmain;

	$firstName = mysqli_real_escape_string($dbmain, $firstName);
	$lastName = mysqli_real_escape_string($dbmain, $lastName);

	if ($stmt = $dbmain->prepare("SELECT COUNT(*) FROM asdf1234_members WHERE firstName=? AND lastName=? LIMIT 1")) {
		$stmt->bind_param("ss", $firstName, $lastName);
		$stmt->execute();
		$stmt->bind_result($found);
		$stmt->fetch();
		$stmt->close();
//		echo '<pre>';
//		echo var_dump($userEmail);
//		echo '</pre>';
		return $found;
	}
}