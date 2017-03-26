<?php

require_once('mail/adminNotification.php');

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

	$verifyLink = home_url('members/?action=verify&email='.$userData['email'].'&activationCode='.$userData['activationCode']);

	// Message
	$message = '
	<html>
	<head>
	  <title>E-mail Address Verification</title>
	</head>
	<body>
	  <h4>E-mail Address Verification</h4>
	  <p><a class="btn btn-primary" href="'.$verifyLink.'">Verify e-mail address</a></p>
	  <p>If you did not request, please ignore this email. If you feel something went wrong, please contact us at <a href="mailto:sdiwc@sdiwc.net">sdiwc@sdiwc.net</a>.</p>
	</body>
	</html>
	';

	//PHPMailer Object
	$mail = new PHPMailer;

	//From email address and name
	$mail->From = $mailMain['MAIL_FROM'];
	$mail->FromName = $mailMain['MAIL_FROM_NAME'];

	//To address and name
	$mail->addAddress($userData['email']);

	//CC and BCC
	// $mail->addBCC(BCC_EMAIL);

	//Send HTML or Plain Text email
	$mail->isHTML(true);

	$mail->Subject = "E-mail Address Verification";
	$mail->Body = $message;
	$mail->AltBody = br2nl($message);

	$mail->send();
}

function sendMembershipCert($userData) {

	if(empty($userData)) return;
	
	include('certification.php');
	global $mailMain, $dbmain;
	
	if(!empty($userData)) {
		
		$certificationId = $userData['certificationId'];

		if(empty($certificationId)) {

			$certificationId = getLastCertificationId();

			$id = $userData['id'];

			$membershipValidDate = date('Y').'-12-31"';

			$sql = "UPDATE asdf1234_members SET membershipValidDate='".$membershipValidDate."', certificationId=".$certificationId." WHERE id=$id";
			if($dbmain->query($sql) === true) {
				$certificationLink=generateCertificate($userData['firstName'], $userData['lastName'], $userData['email'], $userData['work'],
					$userData['country'], $certificationId);
				
				// Multiple recipients
				$to = $userData['email']; // note the comma
				
				// Subject
				$subject = 'Membership Renewed';
				
				// Message
				$message = '
				<html>
				<head>
				  <title>Membership Renewed</title>
				</head>
				<body>
				  <h4>Membership Renewed!</h4>
				  <p>Your certification is available for download.</p>
				  <p><a href="'.$certificationLink.'">'.$certificationLink.'</a></p>
				</body>
				</html>
				';
				// To send HTML mail, the Content-type header must be set
				$headers[] = 'MIME-Version: 1.0';
				$headers[] = 'Content-type: text/html; charset=iso-8859-1';
				
				// Additional headers
				$headers[] = "From: ".$mailMain['MAIL_FROM_NAME']." <".$mailMain['MAIL_FROM'].">";
				$headers[] = 'Bcc: njucab.sdiwc@yahoo.com';
				// Mail it
				mail($to, $subject, $message, implode("\r\n", $headers));

				$adminMailHead = 'Membership Renewal';
				$adminMailBody = '
				<html>
				<head>
				  <title>Membership Renewed</title>
				</head>
				<body>
				  <h4>Thank you!</h4>
				  <p>Your certification is available for download.</p>
				  <p><a href="'.$certificationLink.'">'.$certificationLink.'</a></p>
				</body>
				</html>
				';

				sendAdminNotif($adminMailHead, $adminMailBody);
			}
		}
	}
}



function sendCertification($userId) {
	// if(empty($userData)) return;
	
//	include("../includes/functions.php");
	global $mailMain, $subscriptionListArr;
	
	$userData = getUser($userId);
	
	if(!empty($userData)) {
		
		include('certification.php');
		$certificationId = $userData['certificationId'];
		$validDateTimestamp = strtotime(trim($userData['membershipValidDate'], '"'));
		// if(!empty($certificationId)) {
			$certificationId++;
			
			$file=generateCertificate($userData['firstName'], $userData['lastName'], $userData['email'], $userData['work'], $userData['country'], $certificationId);
			$certificationPath = CERT_PATH . $file;

			// Message
			$message = '
			<html>
			<head>
			  <title>New Membership</title>
			</head>
			<body>
				<h4>Congratulations!</h4>
				<p>This membership allows you to take advantages from the SDIWC.<br></p>
				<p>Your membership is valid until December 31, '.date('Y').'</p>
			</body>
			</html>
			';

			//PHPMailer Object
			$mail = new PHPMailer;

			//From email address and name
			$mail->From = $mailMain['MAIL_FROM'];
			$mail->FromName = $mailMain['MAIL_FROM_NAME'];

			//To address and name
			$mail->addAddress($userData['email']);

			//Send HTML or Plain Text email
			$mail->isHTML(true);

			$mail->Subject = "Membership Certification";
			$mail->Body = $message;
			$mail->AltBody = br2nl($message);

			$mail->addAttachment($certificationPath, 'Certification.pdf');

			$mail->send();

			// Send admin notification for new membership
			$adminMailSubject = "New Membership";
			// Message
			$adminMailMessage = '
			<html>
			<head>
			  <title>New Membership</title>
			</head>
			<body>
			  <h4>Member Details (Renewed)</h4>
			  <table>';
			$adminMailMessage .= '
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
			    </tr>
			    <tr>
			      <th style="text-align:left; padding-right: 20px">Membership valid until:</th>
			      <td>December 31, '.date('Y', $validDateTimestamp).'</td>
			    </tr>';
			if(!empty($userData['newsletter'])) {
				$adminMailMessage .= '<tr><th style="text-align:left;">Subscription</th>';
				if(!empty($userData['subscriptionList'])) {

					$subscriptionList = unserialize($userData['subscriptionList']);

					// _dump_var($subscriptionList);

					if(is_array($subscriptionList)) {
						$adminMailMessage .= '<td><ul>';
						foreach ($subscriptionList as $key => $value) {
							$adminMailMessage .= '<li>'.$subscriptionListArr[$value].'</li>';
						}
						$adminMailMessage .= '</ul></td>';
					}
				} else {
					$adminMailMessage .= '<td>All</td>';
				}
				$adminMailMessage .= '</tr>';
			}
			$adminMailMessage .= '
			  </table>
			</body>
			</html>
			';

			sendAdminNotif($adminMailSubject, $adminMailMessage);
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

function renewMembership($data, $table) {
	global $dbmain;
	$id = $data['id'];
	$sql = "UPDATE $table SET accountStatus='APPROVED' WHERE id=$id";
	if($dbmain->query($sql) === true) {
		return true;
	}
	return false;
}

function updateMembership($data, $table = MEMBERS_TABLE) {
	global $dbmain;
	$id = $data['id'];
	$membershipValidDate = $data['membershipValidDate'];
	$sql = "UPDATE $table SET token='', activationCode='', membershipValidDate='$membershipValidDate' WHERE id=$id";
	if($dbmain->query($sql) === true) {
		return true;
	}
	return false;
}

function updateActivationCode($data, $table = MEMBERS_TABLE) {
	global $dbmain;
	$id = $data['id'];
	$activationCode = $data['activationCode'];
	$membershipValidDate = $data['membershipValidDate'];
	$sql = "UPDATE $table SET activationCode='$activationCode', membershipValidDate='$membershipValidDate' WHERE id=$id";
	if($dbmain->query($sql) === true) {
		return true;
	}
	return false;
}

function updateToken($data, $table = MEMBERS_TABLE) {
	global $dbmain;
	$id = $data['userId'];
	$token = $data['token'];
	$sql = "UPDATE $table SET token='$token' WHERE id=$id";
	if($dbmain->query($sql) === true) {
		return true;
	}
	return false;
}

function updateData($data, $id, $table) {
	global $dbmain;
	$cols = implode(',', array_keys($data));
	foreach (array_values($data) as $value)	{
		isset($vals) ? $vals .= ',' : $vals = '';
		$vals .= '\''.$dbmain->real_escape_string($value).'\'';
	}

	$values  = '';
	foreach ($data as $key => $value) {
		!empty($values) ? $values .= ', ' : $values = '';
		$values .= $key . '=' . '\''.$dbmain->real_escape_string($value).'\'';
	}
	
	return $dbmain->real_query('UPDATE '.$table.' SET ' . $values . ' WHERE id='.$id);
	// _dump_var('UPDATE '.$table.' SET ' . $values . ' WHERE id='.$id);


	// return $dbmain->insert_id;
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

	$cols .= ',membershipValidDate';
	$vals .= ',"'.date('Y').'-12-31"';


	$cols .= ',versionId';
	$vals .= ','.VERSION_ID;

	$cols .= ',createdAt';
	$vals .= ',"'.date('Y-m-d').'"';

	
//	exit('INSERT INTO '.$table.' ('.$cols.') VALUES ('.$vals.')');
	
	//exit('INSERT INTO '.$table.' ('.$cols.') VALUES ('.$vals.')');
	$dbmain->real_query('INSERT INTO '.$table.' ('.$cols.') VALUES ('.$vals.')');


	return $dbmain->insert_id;
}

function checkMembershipDate($email) {
	global $dbmain;
	if ($stmt = $dbmain->prepare("SELECT COUNT(*) FROM asdf1234_members WHERE email=? LIMIT 1")) {
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$stmt->bind_result($found);
		$stmt->fetch();
		$stmt->close();
		return $found;
	}
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

function testEmail() {
	global $mailMain;

	//PHPMailer Object
	$mail = new PHPMailer;

	//From email address and name
	$mail->From = $mailMain['MAIL_FROM'];
	$mail->FromName = $mailMain['MAIL_FROM_NAME'];

	//To address and name
	$mail->addAddress("njucab.sdiwc@gmail.com", "Noah Ucab");

	//CC and BCC
	$mail->addBCC("njucab.sdiwc@yahoo.com");

	//Send HTML or Plain Text email
	$mail->isHTML(true);

	$mail->Subject = "Subject Text";
	$mail->Body = "<i>Mail body in HTML</i>";
	$mail->AltBody = "This is the plain text version of the email content";

	$mail->addAttachment(ABS_PATH . '/data/test.txt');

	// if(!$mail->send()) {
	// 	echo "Mailer Error: " . $mail->ErrorInfo;
	// } else {
	// 	echo "Message has been sent successfully";
	// }
}


function sendVerificationEmailForRenewal($userData = []) {


	global $mailMain;

	$renewalLink = home_url('members/renew.php?action=renew&email='.$userData['email'].'&token='.$userData['token']);

	// Message
	$message = '
	<html>
	<head>
	  <title>Verify your Membership Renewal</title>
	</head>
	<body>
	  <h4>Verify your Membership Renewal</h4>
	  <p><a class="btn btn-primary" href="'.$renewalLink.'">Verify Renewal</a></p>
	  <p>If you did not request, please ignore this email. If you feel something went wrong, please contact us at <a href="mailto:sdiwc@sdiwc.net">sdiwc@sdiwc.net</a>.</p>
	</body>
	</html>
	';

	//PHPMailer Object
	$mail = new PHPMailer;

	//From email address and name
	$mail->From = $mailMain['MAIL_FROM'];
	$mail->FromName = $mailMain['MAIL_FROM_NAME'];

	//To address and name
	$mail->addAddress($userData['email']);

	//CC and BCC
	// $mail->addBCC(BCC_EMAIL);

	//Send HTML or Plain Text email
	$mail->isHTML(true);

	$mail->Subject = "Verify your Membership Renewal";
	$mail->Body = $message;
	$mail->AltBody = br2nl($message);

	$mail->send();
}


function sendCertificationForRenewal($userData) {
	global $mailMain, $subscriptionListArr;

	if(!empty($userData)) {

		include('certification.php');

		$certificationId = $userData['certificationId'];


		$validDateTimestamp = strtotime(trim($userData['membershipValidDate'], '"'));
		// echo $validDateTimestamp;
		// exit('asd');


		$token = $userData['token']; // note the comma
		$updateLink = home_url('members/update.php?email='.$userData['email'].'&token='.$token);
		
		$file=generateCertificate($userData['firstName'], $userData['lastName'], $userData['email'], $userData['work'], $userData['country'], $certificationId);
		$certificationPath = CERT_PATH . $file;

		// Message
		$message = '
		<html>
		<head>
		  <title>Membership Certification</title>
		</head>
		<body>
		  <h4>Membership Information</h4>
		  <table style="margin-bottom: 20px;">';
		$message .= '
		    <tr>
		      <th style="text-align:left; padding-right: 20px">First Name</th>
		      <td>'.$userData['firstName'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">Last Name</th>
		      <td>'.$userData['lastName'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">E-mail</th>
		      <td>'.$userData['email'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">University/Workplace</th>
		      <td>'.$userData['work'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">Country</th>
		      <td>'.$userData['country'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">Membership valid until:</th>
		      <td>December 31, '.date('Y', $validDateTimestamp).'</td>
		    </tr>';
		if(!empty($userData['newsletter'])) {
			$message .= '<tr><th style="text-align:left; padding-right: 20px">Subscription</th>';
			if(!empty($userData['subscriptionList'])) {

				$subscriptionList = unserialize($userData['subscriptionList']);

				// _dump_var($subscriptionList);

				if(is_array($subscriptionList)) {
					$message .= '<td><div>';
					foreach ($subscriptionList as $key => $value) {
						$message .= '<div>'.$subscriptionListArr[$value].'</div>';
					}
					$message .= '</div></td>';
				}
			} else {
				$message .= '<td>All</td>';
			}
			$message .= '</tr>';
		}
		$message .= '
		  </table>
		  <p>If you want to update your membership information, visit link below.</p>
			<p><a href="'.$updateLink.'">'.$updateLink.'</a></p>
		</body>
		</html>
		';

		//PHPMailer Object
		$mail = new PHPMailer;

		//From email address and name
		$mail->From = $mailMain['MAIL_FROM'];
		$mail->FromName = $mailMain['MAIL_FROM_NAME'];

		//To address and name
		$mail->addAddress($userData['email']);

		//CC and BCC
		// $mail->addBCC(BCC_EMAIL);

		//Send HTML or Plain Text email
		$mail->isHTML(true);

		$mail->Subject = "Membership Certification";
		$mail->Body = $message;
		$mail->AltBody = "If you want to update your membership, you can go to this link below.\r\n\r\n".$updateLink;

		$mail->addAttachment($certificationPath, 'Certification.pdf');

		$mail->send();

		$adminMailSubject = "Membership Certification (Renewed)";
		// Message
		$adminMessage = '
		<html>
		<head>
		  <title>Membership Certification (Renewed)</title>
		</head>
		<body>
		  <h4>Member Information</h4>
		  <table>';
		$adminMessage .= '
		    <tr>
		      <th style="text-align:left; margin-right: 20px">First Name</th>
		      <td>'.$userData['firstName'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; margin-right: 20px">Last Name</th>
		      <td>'.$userData['lastName'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; margin-right: 20px">E-mail</th>
		      <td>'.$userData['email'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; margin-right: 20px">University/Workplace</th>
		      <td>'.$userData['work'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; margin-right: 20px">Country</th>
		      <td>'.$userData['country'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">Membership valid until:</th>
		      <td>December 31, '.date('Y', $validDateTimestamp).'</td>
		    </tr>';
		if(!empty($userData['newsletter'])) {
			$adminMessage .= '<tr><th style="text-align:left; margin-right: 20px">Subscription</th>';
			if(!empty($userData['subscriptionList'])) {

				$subscriptionList = unserialize($userData['subscriptionList']);

				if(is_array($subscriptionList)) {
					$adminMessage .= '<td><div>';
					foreach ($subscriptionList as $key => $value) {
						$adminMessage .= '<p style="margin-bottom: 5px;">'.$subscriptionListArr[$value].'</p>';
					}
					$adminMessage .= '</div></td>';
				}
			} else {
				$adminMessage .= '<td>All</td>';
			}
			$adminMessage .= '</tr>';
		}
		$adminMessage .= '
		  </table>
		</body>
		</html>
		';

		sendAdminNotif($adminMailSubject, $adminMessage);
	}
}



function resendCertification($userId) {
	global $mailMain, $subscriptionListArr;

	$userData = getUser($userId);
	
	if(!empty($userData)) {

		include('certification.php');

		$certificationId = $userData['certificationId'];

		$validDateTimestamp = strtotime(trim($userData['membershipValidDate'], '"'));

		$token = $userData['token']; // note the comma
		$updateLink = home_url('members/update.php?email='.$userData['email'].'&token='.$token);
		
		$file=generateCertificate($userData['firstName'], $userData['lastName'], $userData['email'], $userData['work'], $userData['country'], $certificationId);
		$certificationPath = CERT_PATH . $file;

		// Message
		$message = '
		<html>
		<head>
		  <title>Membership Certification</title>
		</head>
		<body>
		  <h4>Membership Information</h4>
		  <table style="margin-bottom: 20px;">';
		$message .= '
		    <tr>
		      <th style="text-align:left; padding-right: 20px">First Name</th>
		      <td>'.$userData['firstName'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">Last Name</th>
		      <td>'.$userData['lastName'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">E-mail</th>
		      <td>'.$userData['email'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">University/Workplace</th>
		      <td>'.$userData['work'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">Country</th>
		      <td>'.$userData['country'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">Membership valid until:</th>
		      <td>December 31, '.date('Y', $validDateTimestamp).'</td>
		    </tr>';
		if(!empty($userData['newsletter'])) {
			$message .= '<tr><th style="text-align:left; padding-right: 20px">Subscription</th>';
			if(!empty($userData['subscriptionList'])) {

				$subscriptionList = unserialize($userData['subscriptionList']);

				// _dump_var($subscriptionList);

				if(is_array($subscriptionList)) {
					$message .= '<td><div>';
					foreach ($subscriptionList as $key => $value) {
						$message .= '<p>'.$subscriptionListArr[$value].'</p>';
					}
					$message .= '</div></td>';
				}
			} else {
				$message .= '<td>All</td>';
			}
			$message .= '</tr>';
		}
		$message .= '
		  </table>
		  <p>If you want to update your membership information, visit link below.</p>
			<p><a href="'.$updateLink.'">'.$updateLink.'</a></p>
		</body>
		</html>
		';

		//PHPMailer Object
		$mail = new PHPMailer;

		//From email address and name
		$mail->From = $mailMain['MAIL_FROM'];
		$mail->FromName = $mailMain['MAIL_FROM_NAME'];

		//To address and name
		$mail->addAddress($userData['email']);

		//CC and BCC
		// $mail->addBCC(BCC_EMAIL);

		//Send HTML or Plain Text email
		$mail->isHTML(true);

		$mail->Subject = "Membership Certification";
		$mail->Body = $message;
		$mail->AltBody = "If you want to update your membership, you can go to this link below.\r\n\r\n".$updateLink;

		$mail->addAttachment($certificationPath, 'Certification.pdf');

		$mail->send();

		$adminMailSubject = "Membership Certification (Resent)";
		// Message
		$adminMessage = '
		<html>
		<head>
		  <title>Membership Certification (Resent)</title>
		</head>
		<body>
		  <h4>Member Information</h4>
		  <table>';
		$adminMessage .= '
		    <tr>
		      <th style="text-align:left; margin-right: 20px">First Name</th>
		      <td>'.$userData['firstName'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; margin-right: 20px">Last Name</th>
		      <td>'.$userData['lastName'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; margin-right: 20px">E-mail</th>
		      <td>'.$userData['email'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; margin-right: 20px">University/Workplace</th>
		      <td>'.$userData['work'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; margin-right: 20px">Country</th>
		      <td>'.$userData['country'].'</td>
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">Membership valid until:</th>
		      <td>December 31, '.date('Y', $validDateTimestamp).'</td>
		    </tr>';
		if(!empty($userData['newsletter'])) {
			$adminMessage .= '<tr><th style="text-align:left; margin-right: 20px">Subscription</th>';
			if(!empty($userData['subscriptionList'])) {

				$subscriptionList = unserialize($userData['subscriptionList']);

				if(is_array($subscriptionList)) {
					$adminMessage .= '<td><div>';
					foreach ($subscriptionList as $key => $value) {
						$adminMessage .= '<p style="margin-bottom: 5px;">'.$subscriptionListArr[$value].'</p>';
					}
					$adminMessage .= '</div></td>';
				}
			} else {
				$adminMessage .= '<td>All</td>';
			}
			$adminMessage .= '</tr>';
		}
		$adminMessage .= '
		  </table>
		</body>
		</html>
		';

		sendAdminNotif($adminMailSubject, $adminMessage);
	}
}

function sendCert($userData = []) {
	global $mailMain, $subscriptionListArr;

	if(!empty($userData)) {

		include('certification.php');

		$certificationId = $userData['certificationId'];

		$validDateTimestamp = strtotime(trim($userData['membershipValidDate'], '"'));

		$file=generateCertificate($userData['firstName'], $userData['lastName'], $userData['email'], $userData['work'], $userData['country'], $certificationId);
		$certificationPath = CERT_PATH . $file;

		$message = '
				<html>
				<head>
				  <title>Membership Verification</title>
				</head>
				<body>
				  <p>Thank you for updating your membership details.</p>
				  <p>Please see attached file for your certification.</p>
				</body>
				</html>
				';

		//PHPMailer Object
		$mail = new PHPMailer;

		//From email address and name
		$mail->From = $mailMain['MAIL_FROM'];
		$mail->FromName = $mailMain['MAIL_FROM_NAME'];

		//To address and name
		$mail->addAddress($userData['email']);

		//CC and BCC
		// $mail->addBCC(BCC_EMAIL);

		//Send HTML or Plain Text email
		$mail->isHTML(true);

		$mail->Subject = "Membership Certification";
		$mail->Body = $message;
		$mail->AltBody = "Thank you for updating your membership details.\r\n\r\nPlease see attached file for your certification.";

		$mail->addAttachment($certificationPath, 'Certification.pdf');

		$mail->send();

		$adminMailSubject = "Membership Certification (Renewed)";
		// Message
		$adminMessage = '
		<html>
		<head>
		  <title>Membership Certification (Renewed)</title>
		</head>
		<body>
		  <h4>Member Details (Renewed)</h4>
		  <table>';
		$adminMessage .= '
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
		    </tr>
		    <tr>
		      <th style="text-align:left; padding-right: 20px">Membership valid until:</th>
		      <td>December 31, '.date('Y', $validDateTimestamp).'</td>
		    </tr>';
		if(!empty($userData['newsletter'])) {
			$adminMessage .= '<tr><th style="text-align:left;">Subscription</th>';
			if(!empty($userData['subscriptionList'])) {

				$subscriptionList = unserialize($userData['subscriptionList']);

				// _dump_var($subscriptionList);

				if(is_array($subscriptionList)) {
					$adminMessage .= '<td><ul>';
					foreach ($subscriptionList as $key => $value) {
						$adminMessage .= '<li>'.$subscriptionListArr[$value].'</li>';
					}
					$adminMessage .= '</ul></td>';
				}
			} else {
				$adminMessage .= '<td>All</td>';
			}
			$adminMessage .= '</tr>';
		}
		$adminMessage .= '
		  </table>
		</body>
		</html>
		';

		sendAdminNotif($adminMailSubject, $adminMessage);
	}
}