<?php

	session_start();

	//Manual Hermes
	$manual_url = "http://goo.gl/Ab8m0";

	$hostname = gethostname();
	$host  = $_SERVER['HTTP_HOST'];
	$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

	$_SESSION['origin'] = $_SERVER['SCRIPT_NAME']; //hermes/datapump/update_product.php
    if(isset($_GET['dangerous']) && $_GET['dangerous'] == "true"){
        $_SESSION['dangerous'] = true;
    }
    
	if( in_array($hostname, array('etailers1', 'etailers-WWW01')) && basename($_SERVER["SCRIPT_NAME"]) != "export.php") {

        $now = getdate();
        // If Weekends (Sunday, Friday, Saturday) or after 4pm, NOT Possible !!
        if($now['wday'] == 0 || $now['wday'] == 5 || $now['wday'] == 6 || $now['hours'] >= 17){
            if(!isset($_SESSION['dangerous'])) header("Location: http://".$host."/hermes/datapump/toolate.php");
        }

        if(!isset($_SESSION['userhermes'])){
            header("Location: http://".$host."/hermes/datapump/login.php?".$_SERVER['QUERY_STRING']);
            exit();
        }
    } elseif ( in_array($hostname, array('etailers2', 'etailers3', 'etailers-WWW02', 'etailers-WWW03'))) {
        header('HTTP/1.1 503 Too busy, try again later');
        die('Server is too busy at the moment. Please try again later.');
    }
?>
