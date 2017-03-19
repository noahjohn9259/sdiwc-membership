<?php 

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


function sendAdminNotif($subject = '', $message = '', $attachment = '') {
	global $mailMain;

	$to = $mailMain['ADMIN_EMAIL']; // note the comma

	//PHPMailer Object
	$mail = new PHPMailer;

	//From email address and name
	$mail->From = $mailMain['MAIL_FROM'];
	$mail->FromName = $mailMain['MAIL_FROM_NAME'];

	//To address and name
	$mail->addAddress($mailMain['ADMIN_EMAIL']);

	//CC and BCC
	$mail->addBCC(BCC_EMAIL);

	//Send HTML or Plain Text email
	$mail->isHTML(true);

	$mail->Subject = $subject;
	$mail->Body = $message;

	$message = strip_tags($message, '<br>');

	$mail->AltBody = br2nl($message);

	if(!empty($attachment)) {
		if(file_exists($attachment)) {
			$mail->addAttachment($attachment);
		}
	}

	$mail->send();
}
