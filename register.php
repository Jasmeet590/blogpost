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

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
	$response = new Response();
	$response->httpstatuscode(405);
	$response->setsuccess(false);
	$response->addmessage("Request method not allowed");
	$response->send();
	exit;
}

if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
	$response = new Response();
	$response->httpstatuscode(400);
	$response->setsuccess(false);
	$response->addmessage("Content type header not set to json");
	$response->send();
	exit;
}

$rawpostdata = file_get_contents('php://input');

if(!$jsondata = json_decode($rawpostdata)){
    $response = new Response();
	$response->httpstatuscode(500);
	$response->setsuccess(false);
	$response->addmessage("Request body is not valid json");
	$response->send();
	exit;
}

if(!isset($jsondata->email) || !isset($jsondata->name) || !isset($jsondata->password)){
	$response = new Response();
	$response->httpstatuscode(400);
	$response->setsuccess(false);
	(!isset($jsondata->email) ? $response->addmessage("email must be entered") : false);
	(!isset($jsondata->name) ? $response->addmessage("name must be entered") : false);
	(!isset($jsondata->password) ? $response->addmessage("password must be entered") : false);
	$response->send();
	exit;
}

if(strlen($jsondata->email) < 0 || strlen($jsondata->email) > 255 || strlen($jsondata->name) < 0 || strlen($jsondata->name) > 255 || strlen($jsondata->password) < 0 || strlen($jsondata->password) > 255){	
	$response = new Response();
	$response->httpstatuscode(400);
	$response->setsuccess(false);
	(strlen($jsondata->email) < 0 ? $response->addmessage("email must not be empty") : false);
	(strlen($jsondata->email) > 255 ? $response->addmessage("email must not more than") : false);
	(strlen($jsondata->name) < 0 ? $response->addmessage("name must not be empty") : false);
	(strlen($jsondata->name) > 255 ? $response->addmessage("name must not more than") : false);
	(strlen($jsondata->password) < 0 ? $response->addmessage("password must not be empty") : false);
	(strlen($jsondata->password) > 255 ? $response->addmessage("password must not more than") : false);
	$response->send();
	exit;
}
 $email = trim($jsondata->email);
 $name = trim($jsondata->name);
 $password = $jsondata->password;
 
 
 try{
	 
	 $query = $writedb->prepare('select id from tbluser where email = :email');
	 $query->bindparam(':email', $email, PDO::PARAM_STR);
	 $query->execute();
	 
	 $rowcount = $query->rowcount();
	 
	 if($rowcount !== 0){
		 $response = new Response();
	     $response->httpstatuscode(409);
	     $response->setsuccess(false);
	     $response->addmessage("email already exsist");
	     $response->send();
	     exit;
	    }
		
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);
		
		$query = $writedb->prepare('insert into tbluser (email, name, password) values (:email, :name, :password)');
		$query->bindparam(':email', $email, PDO::PARAM_STR);
		$query->bindparam(':name', $name, PDO::PARAM_STR);
		$query->bindparam(':password', $hashed_password, PDO::PARAM_STR);
		$query->execute();
		
		$rowcount = $query->rowcount();
	 
	 if($rowcount === 0){
		 $response = new Response();
	     $response->httpstatuscode(500);
	     $response->setsuccess(false);
	     $response->addmessage("there was problem creating user");
	     $response->send();
	     exit;
	    }
		
		$lastuserid = $writedb->lastInsertId();
		
		$returndata = array();
		$returndata['user_id'] = $lastuserid;
		$returndata['username'] = $email;
		$returndata['fullname'] = $name;
		
		$response = new Response();
	    $response->httpstatuscode(200);
	    $response->setsuccess(true);
	    $response->addmessage("user successfully creadted");
		$response->setdata($returndata);
	    $response->send();
        exit;
    }
catch(PDOException $ex){
	error_log("database query error-".$ex, 0);
	$response = new Response();
	$response->httpstatuscode(500);
	$response->setsuccess(false);
	$response->addmessage("there was an issue creating a user account");
	$response->send();
	exit;
}