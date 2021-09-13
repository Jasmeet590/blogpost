<?php

class Taskexception extends Exception {}

class task{
	
	
	public function __construct($id, $title, $data, $name){
		$this->setid($id);
		$this->settitle($title);
		$this->setdata($data);
		$this->setname($name);
	}
	
	private $_id;
	private $_title;
	private $_data;
	private $_name;
	
	public function getid(){
		return $this->_id;
	}
	public function gettitle(){
		return $this->_title;
	}
	
	public function getdata(){
		return $this->_data;
	}
	public function getname(){
		return $this->_name;
	}
	
	
	
	public function setid($id){
		if(($id !== null) && (!is_numeric($id) || $id <= 0 || $id > 9223372836854775807 || $this->_id !== null)){
			throw new Taskexception("Task id error");
		}
		$this->_id = $id;
	}
	
	public function settitle($title){
		if(strlen($title) < 0 || strlen($title) > 255){
			throw new Taskexception("Task title error");
		}
		$this->_title = $title;
	}
	
	public function setdata($data){
		if((strlen($data) !== null) && (strlen($data) > 16777215)){
			throw new Taskexception("Task data error");
		}
		$this->_data = $data;
	}
	public function setname($name){
		if((strlen($name) !== null) && (strlen($name) > 16777215)){
			throw new Taskexception("Task data error");
		}
		$this->_name = $name;
	}
	
	
	
      public function returntaskarray(){
    	 $task = array();
		 $task['id'] = $this->getid();
		 $task['title'] = $this->gettitle();
		 $task['data'] = $this->getdata();	
		 $task['username'] = $this->getname();	 
		  return $task;
	    }
		 
}
	
	
	
	
	