<?php

require_once('db.php');
require_once('Response.php');

try{
	$writedb = db::connectwritedb();
}
catch(PDOException $ex){
	error_log("connection error".$ex, 0);
	$response = new Response();
	$response->httpstatuscode(500);
	$response->setsuccess(false);
	$response->addmessage("database connection error");
	$response->send();
	exit;
}

if(array_key_exists("sessionid", $_GET)){
	
	$sessionid = $_GET['sessionid'];
	
	if($sessionid === '' || !is_numeric($sessionid)){
		$response = new Response();
	    $response->httpstatuscode(400);
	    $response->setsuccess(false);
	    ($sessionid == '' ? $response->addmessage("session id cannot be null") : false);
	    (!is_numeric($sessionid) ? $response->addmessage("session id must be numeric") : false);
	    $response->send();
    	exit;
	}
	 
    if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1){
        $response = new Response();
	    $response->httpstatuscode(401);
	    $response->setsuccess(false);
	    (!isset($_SERVER['HTTP_AUTHORIZATION']) ? $response->addmessage("access token is missing from header") : false);
	    (strlen($_SERVER['HTTP_AUTHORIZATION']) < 1 ? $response->addmessage("access token cannot be blank") : false);
	    $response->send();
    	exit;
	}
	
	$accesstoken = $_SERVER['HTTP_AUTHORIZATION'];
	
	if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
		
		try{
			
			$query = $writedb->prepare('delete from tblsessions where id = :sessionid accesstoken = :accesstoken');
			$query->bindparam(':accesstoken', $accesstoken, PDO::PARAM_STR);
			$query->bindparam('sessionid', $sessionid, PDO::PARAM_INT);
			$query->execute();
			
			$rowcount = $query->rowcount();
			
			if($rowcount === 0){
				$response = new Response();
	            $response->httpstatuscode(400);
	            $response->setsuccess(false);
	            $response->addmessage("failed to log out from this session using access token provider");
	            $response->send();
	            exit;
			}
			
			$returndata = array();
			$returndata['session_id'] = $sessionid;
			
			$response = new Response();
			$response->httpstatuscode(200);
			$response->setsuccess(true);
			$response->setdata($returndata);
			$response->send();
			exit;
			
		}
		
		catch(PDOException $ex){
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("error loging out plesase try again");
           	$response->send();
	        exit;	
		}	
	}
	 
	elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){
		
		
			if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
		      $response = new Response();
	          $response->httpstatuscode(400);
	          $response->setsuccess(false);
	          $response->addmessage("content type header not set to json");
	          $response->send();
	          exit;
			}
			
			$rawpostdata = file_get_contents('php://input');
	
	        if(!$jsondata = json_decode($rawpostdata)){
		       $response = new Response();
	           $response->httpstatuscode(400);
	           $response->setsuccess(false);
	           $response->addmessage("request body is not valid json");
	           $response->send();
	           exit;
			}
			
			
			
			if(!isset($jsondata->refresh_token) || strlen($jsondata->refresh_token) < 1){
			   $response = new Response();
	           $response->httpstatuscode(400);
	           $response->setsuccess(false);
			   (!isset($jsondata->refresh_token) ? $response->addmessage("refresh token cannot be empty") : false);
	           (strlen($jsondata->refresh_token) < 1 ? $response->addmessage("refresh token cannot be empty") : false);
	           $response->send();
	           exit;
			}
			try{
				
				$refreshtoken = $jsondata->refresh_token;
				
				
				
				
				
				
				
			}
			catch(PDOException $ex){
			   $response = new Response();
	           $response->httpstatuscode(500);
	           $response->setsuccess(false);
	           $response->addmessage("there was an error refreshing access token please login again");
	           $response->send();
	           exit;	
			}
			
	}
			
	else{
	   $response = new Response();
	   $response->httpstatuscode(405);
	   $response->setsuccess(false);
	   $response->addmessage("request method not allowed");
	   $response->send();
	   exit;			
	}
}
elseif(empty($_GET)){
	
	if($_SERVER['REQUEST_METHOD'] !== 'POST'){
		$response = new Response();
	    $response->httpstatuscode(405);
	    $response->setsuccess(false);
	    $response->addmessage("Request method not allowed");
	    $response->send();
	    exit;
	}
	sleep(1);
	
	if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
		$response = new Response();
	    $response->httpstatuscode(400);
	    $response->setsuccess(false);
	    $response->addmessage("content type header not set to json");
	    $response->send();
	    exit;
	}
	
	$rawpostdata = file_get_contents('php://input');
	
	if(!$jsondata = json_decode($rawpostdata)){
		$response = new Response();
	    $response->httpstatuscode(400);
	    $response->setsuccess(false);
	    $response->addmessage("request body is not valid json");
	    $response->send();
	    exit;
	}
	
	if(!isset($jsondata->email) || !isset($jsondata->password)){
		$response = new Response();
	    $response->httpstatuscode(400);
	    $response->setsuccess(false);
	    (!isset($jsondata->email) ? $response->addmessage("email must be provided") : false);
		(!isset($jsondata->password) ? $response->addmessage("password must be provided") : false);
	    $response->send();
	    exit;
	}
	if(strlen($jsondata->email) < 0 || strlen($jsondata->email) > 255 || strlen($jsondata->password) < 0 || strlen($jsondata->password) > 255){
		$response = new Response();
	    $response->httpstatuscode(400);
	    $response->setsuccess(false);
	    (strlen($jsondata->email) < 0 ? $response->addmessage("email must not be empty") : false);
		(strlen($jsondata->email) > 255 ? $response->addmessage("email cannot exced 255 character") : false);
		(strlen($jsondata->password) < 0 ? $response->addmessage("password must not be empty") : false);
		(strlen($jsondata->password) > 255 ? $response->addmessage("password cannot exced 255 character") : false);
	    $response->send();
	    exit;
	}
	
	try{
		$email = $jsondata->email;
		$password = $jsondata->password;
		
		$query = $writedb->prepare('select id, name, email, password, useractive, loginattempts from tbluser where email = :email');
		$query->bindparam(':email', $email, PDO::PARAM_STR);
		$query->execute();
		
		$rowcount = $query->rowcount();
		
		if($rowcount === 0){
			$response = new Response();
	        $response->httpstatuscode(401);
	        $response->setsuccess(false);
	        $response->addmessage("email or password incorrect");
	        $response->send();
	        exit;
		}
		
		$row = $query->fetch(PDO::FETCH_ASSOC);
		
		$updated_id = $row['id'];
		$updated_fullname = $row['name'];
		$updated_password = $row['password'];
		$updated_useractive = $row['useractive'];
		$updated_username = $row['email'];
		$updated_loginattempts = $row['loginattempts'];
		
		if($updated_useractive !== 'Y'){
			$response = new Response();
	        $response->httpstatuscode(401);
    	    $response->setsuccess(false);
	        $response->addmessage("user account not active");
	        $response->send();
	        exit;
		}
		
		if($updated_loginattempts >= 3){
			$response = new Response();
	        $response->httpstatuscode(401);
    	    $response->setsuccess(false);
	        $response->addmessage("user account is currently locked out");
	        $response->send();
	        exit;
		}
		
		if(!password_verify($password, $updated_password)){
			
			$query = $writedb->prepare('update tbluser set loginattempts = loginattempts+1 where id = :id');
			$query->bindparam(':id', $updated_id, PDO::PARAM_INT);
			$query->execute();
			
			$response = new Response();
	        $response->httpstatuscode(401);
    	    $response->setsuccess(false);
	        $response->addmessage("email or password incorrect");
	        $response->send();
	        exit;
		}
		
		$accesstoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());		
		$refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
		
		$access_token_expiry_seconds = 1200;
		$refresh_token_expiry_seconds = 1209600;
	}
	catch(PDOException $ex){
		$response = new Response();
	    $response->httpstatuscode(500);
	    $response->setsuccess(false);
	    $response->addmessage("there was an issue logging in");
	    $response->send();
	    exit;	
	}
	
	try{
		
		$writedb->begintransaction();
		
		$query = $writedb->prepare('update tbluser set loginattempts = 0 where id = :id');
	    $query->bindparam(':id', $updated_id, PDO::PARAM_INT);
		$query->execute();
		
		$query = $writedb->prepare('insert into tblsessions (userid, accesstoken, accesstokenexpiry, refreshtoken, refreshtokenexpiry) values (:userid, :accesstoken, date_add(NOW(), INTERVAL :accesstokenexpiryseconds SECOND), :refreshtoken, date_add(NOW(), INTERVAL :refreshtokenexpiryseconds SECOND))');
		$query->bindparam(':userid', $updated_id, PDO::PARAM_INT);
		$query->bindparam(':accesstoken', $accesstoken, PDO::PARAM_STR);
		$query->bindparam(':accesstokenexpiryseconds', $access_token_expiry_seconds, PDO::PARAM_INT);
		$query->bindparam(':refreshtoken', $refreshtoken, PDO::PARAM_INT);
		$query->bindparam(':refreshtokenexpiryseconds', $refresh_token_expiry_seconds, PDO::PARAM_INT);
		$query->execute();
		
		$lastsessionid = $writedb->lastinsertid();
		
		$writedb->commit();
		
		$returndata = array();
		$returndata['Session_id'] = intval($lastsessionid);
		$returndata['access_token'] = $accesstoken;
		$returndata['access_token_expiry_in'] = $access_token_expiry_seconds;
		$returndata['refresh_token'] = $refreshtoken;
		$returndata['refresh_token_expiry_in'] = $refresh_token_expiry_seconds;
		
		$response = new Response();
	    $response->httpstatuscode(201);
	    $response->setsuccess(true);
	    $response->setdata($returndata);
	    $response->send();
        exit;
	}
	catch(PDOException $ex){
		$writedb->rollBack();
		$response = new Response();
	    $response->httpstatuscode(500);
	    $response->setsuccess(false);
	    $response->addmessage("there was an issue logging in- plesase try again");
	    $response->send();
	    exit;	
	}

}
else{
	$response = new Response();
	$response->httpstatuscode(404);
	$response->setsuccess(false);
	$response->addmessage("end point not found");
	$response->send();
	exit;
}