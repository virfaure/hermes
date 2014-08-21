<?php

$hostname = gethostname();
if (strpos($hostname, 'pre') !== FALSE) {
    header('Location: /hermes/datapump/index.php?'.$_SERVER['QUERY_STRING']);
}
session_start();

if(!empty($_POST)){
	
	$error = null;
	
	if(!empty($_POST['login']) && !empty($_POST['password'])){
		//Chech if Password Match
		$hostname = gethostname();
		$domain = $_SERVER['SERVER_NAME'];
		$arrDomain = explode(".", $domain);
		$domain = $arrDomain[1];
		$password = substr(md5($domain),0, 6);
		
		if($_POST['password'] != $password) $error = "Login Error, please check your password.";
		if($_POST['login'] != $domain) $error = "Login Error, please check your username.";
	}else{
		$error = "Login Error, please enter your user / password.";
	}
		
	if(empty($error)){
		$_SESSION['userhermes'] = $_POST['login'];
		
        //Action
        if(empty($_POST['action']))  $action ="/hermes/datapump/index.php?".$_SERVER['QUERY_STRING'];
		else  $action = $_POST['action']."?".$_SERVER['QUERY_STRING'];
		
		$host  = $_SERVER['HTTP_HOST'];
		header('Location: http://'.$host.$action);
		exit;
	}
}



?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head>
    <meta charset='utf-8'>
    <title>Login To Hermes</title>
    <link rel="stylesheet" href="styles/styles.css" type="text/css">
</head>
<body>
	<div id="container-form">
			
			<br><br>
			<h1>Login to Hermes</h1>

			<div id="back_login">
				
				<?php
					if(!empty($error)){
						echo '<div class="error">'.$error.'</div><br />';
					}
				?>
				
				<div id="divForm">
					<form id="form_login" name="form_login" action="" method="post">
						<p>
							<label>Login :  </label><br>
							<input type="text" id="login" name="login" class="full" value="">
						</p>
						<div class="clear"></div>
						<p>
							<label>Password : </label> <br>
							<input type="password" id="password" name="password" class="full" value="">
						</p>
						<div class="clear"></div>
						<br><br>
						<p class="right">
							<input type="submit" id="submit" name="submit" value="Login" class="btn btn-blue big">
							<input type="hidden" id="action" name="action" value="<?php echo $_SESSION['origin']  ?>" >
						</p>
						<div class="clear"></div>
					</form>
				</div>
				
			</div>
			
		</div>
</body>
</html>
