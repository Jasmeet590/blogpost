<?php

class response{

private $_success;
private $_httpstatuscode;
private $_message = array();
private $_data;
private $_tocache = false;
private $_responsedata = array();


public function setsuccess($success){
	$this->_success = $success;
}

public function httpstatuscode($httpstatuscode){
	$this->_httpstatuscode = $httpstatuscode;
}

public function addmessage($message){
	$this->_message[] = $message;
}

public function setdata($data){
	$this->_data = $data;
}

public function tocache($cache){
	$this->_tocache = $cache;
}

public function send(){
	
	
	header('content-type: application/json;charset=utf-8'); 
	
	if($this->_tocache == true){
		header('cache-control: max-age=60');
	}
	else{
		header('cache-control: no-cache, no-store');
	}
	
	if(($this->_success !== true && $this->_success !== false) || !is_numeric($this->_httpstatuscode)){
		
		http_response_code(500);
		$this->_responsedata['statuscode'] = 500;
		$this->_responsedata['Success'] = false;
		$this->addmessage("Response creation error");
		$this->_responsedata = $this->_message;
	}
	else{
		
	  http_response_code($this->_httpstatuscode);
	    $this->_responsedata['statuscode'] = $this->_httpstatuscode;
		$this->_responsedata['Success'] = $this->_success;
		$this->_responsedata['Message'] = $this->_message;
		$this->_responsedata['data'] = $this->_data;
	}
	
	echo json_encode($this->_responsedata);
	
}

}