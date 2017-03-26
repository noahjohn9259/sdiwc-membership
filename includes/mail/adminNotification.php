<?php 
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
