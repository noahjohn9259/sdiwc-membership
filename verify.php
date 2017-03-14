<?php

include('config.php');
require_once('../header.php');


if(!empty($_GET)) {
	$token = isset($_GET['token']) ? $_GET['token'] : '';
	$email = isset($_GET['email']) ? $_GET['email'] : '';
}

?>
<div class="alert alert-success">
	<h3><span class="fa fa-check" style="color: #43AC6A"></span>E-mail address verified.</h3>
	<p>Thank you for verifying your email address.</p>
</div>
<?php
require_once("../footer.php");
?>