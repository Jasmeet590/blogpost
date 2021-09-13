<?php

require_once('db.php');
require_once('Task.php');
require_once('Response.php');

try{
	$writedb = db::connectwritedb();
	$readdb  = db::connectreaddb();
}
catch(PDOException $ex){
	error_log("connection error-".$ex, 0);
	$response = new Response();
	$response->httpstatuscode(500);
	$response->setsuccess(false);
	$response->addmessage("Data base connection error");
	$response->send();
	exit();
}

if(array_key_exists("postid",$_GET)){

  $postid = $_GET['postid'];

    if($postid == '' || !is_numeric($postid)){
	    $response = new Response();
	    $response->httpstatuscode(400);
	    $response->setsuccess(false);
	    $response->addmessage("post id should not be empty and must be numeric");
	    $response->send();
	    exit();
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
		try{
			$query = $readdb->prepare('select tblpost.id, tblpost.title, tblpost.data, tbluser.name from tblpost, tbluser where tblpost.id = :postid and tblpost.userid = tbluser.id');
			$query->bindParam(':postid', $postid, PDO::PARAM_INT);
			$query->execute();

            $rowcount = $query->rowcount();
            
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			 
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['data'], $row['name']);
			 $postarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['Post'] = $postarray;



            $query = $readdb->prepare('select tblcomments.id, tblcomments.comment, tblcomments.userid, tbluser.name from tblcomments, tbluser where postid = :postid and tblcomments.userid = tbluser.id');
			$query->bindParam(':postid', $postid, PDO::PARAM_INT);
			$query->execute();

             $rowcount = $query->rowcount();

			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['comment'], $row['userid'], $row['name']);
			 $commentarray[] = $task->returntaskarray();
			}
			$returndata['comments_returned'] = $rowcount;
			$returndata['Comments'] = $commentarray;




            
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->tocache(true);
			$response->setdata($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("database connection errors");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}

	}
	
	if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
		try{
			$query = $readdb->prepare('delete from tblpost where id = :postid');
			$query->bindParam(':postid', $postid, PDO::PARAM_INT);
			$query->execute();
            
            $rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	

			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->addmessage("Task deleted successfully");
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("database connection errors");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}
		
	}
	
	if($_SERVER['REQUEST_METHOD'] === 'PATCH'){
		
		try{  
			 if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
			   $response = new Response();
	           $response->httpstatuscode(400);
	           $response->setsuccess(false);
	           $response->addmessage("content type header not set to json");
	           $response->send();
	           exit(); 
			 }
			 
			 $rawpatchdata = file_get_contents('php://input');
			 
			 if(!$jsondata = json_decode($rawpatchdata)){
			   $response = new Response();
	           $response->httpstatuscode(400);
	           $response->setsuccess(false);
	           $response->addmessage("Request body is not valid json");
	           $response->send();
	           exit(); 
			 }
			 
			 
			 $title_updated = false;
			 $data_updated = false;
             
			 $queryfield = "";
			 
			 if(isset($jsondata->title)){
				 $title_updated = true;
				 $queryfield .= "title = :title, ";
			 }
			 if(isset($jsondata->data)){
				 $data_updated = true;
				 $queryfield .= "data = :data, ";
			 }
			 if(isset($jsondata->deadline)){
				 $deadline_updated = true;
				 $queryfield .= "deadline = STR_TO_DATE(:deadline, '%d/%m/%Y %H:%i'), ";
			 }
			 if(isset($jsondata->completed)){
				 $completed_updated = true;
				 $queryfield .= "completed = :completed, ";
			 }
			  
			$queryfield = rtrim($queryfield, ", ");
			
			if($title_updated === false && $data_updated === false && $deadline_updated === false && $completed_updated === false){
			   $response = new Response();
	           $response->httpstatuscode(400);
	           $response->setsuccess(false);
	           $response->addmessage("No task feild provided");
	           $response->send();
	           exit();
			}			   
			
			$query = $readdb->prepare('select id, title, data, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where id = :postid');
			$query->bindParam(':postid', $postid, PDO::PARAM_INT);
			$query->execute();
            
            $rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			 
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['data'], $row['deadline'], $row['completed']);
			}
			
			$querystring = "update tbltasks set ".$queryfield." where id = :postid";
			$query = $writedb->prepare($querystring);
			
			
			if($title_updated == true){
				$task->settitle($jsondata->title);
				$up_title = $task->gettitle();
				$query->bindParam(':title', $up_title, PDO::PARAM_STR);
			}
			if($data_updated == true){
				$task->setdata($jsondata->data);
				$up_data = $task->getdata();
				$query->bindParam(':data', $up_data, PDO::PARAM_STR);
			}
			if($deadline_updated == true){
				$task->setdeadline($jsondata->deadline);
				$up_deadline = $task->getdeadline();
				$query->bindParam(':deadline', $up_deadline, PDO::PARAM_STR);
			}
			if($completed_updated == true){
				$task->setcompleted($jsondata->completed);
				$up_completed = $task->getcompleted();
				$query->bindParam(':completed', $up_completed, PDO::PARAM_STR);
			}
			$query->bindParam(':postid', $postid, PDO::PARAM_INT);
			$query->execute();
			
			$rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			$query = $readdb->prepare('select id, title, data, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where id = :postid');
			$query->bindParam(':postid', $postid, PDO::PARAM_INT);
			$query->execute();
			
			 
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['data'], $row['deadline'], $row['completed']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['Task'] = $taskarray;
			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->addmessage("Task updated");
			$response->setdata($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("failed to update task check your data for errors");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(400);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}
		
		
	}
    else{
		$response = new Response();
	    $response->httpstatuscode(405);
	    $response->setsuccess(false);
	    $response->addmessage("Request method not allowed");
	    $response->send();
	    exit(); 
	}

}

elseif(array_key_exists("completed",$_GET)){
	
	$completed = $_GET['completed'];
	
	if($completed !== 'Y' && $completed !== 'N'){
		$response = new Response();
	    $response->httpstatuscode(400);
	    $response->setsuccess(false);
	    $response->addmessage("completed status must be Y or N");
	    $response->send();
	    exit();
	}
	if($_SERVER['REQUEST_METHOD'] === 'GET'){
		try{
			$query = $readdb->prepare('select id, title, data, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where completed = :completed');
			$query->bindParam(':completed', $completed, PDO::PARAM_STR);
			$query->execute();
            
            $rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['data'], $row['deadline'], $row['completed']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['Task'] = $taskarray;
			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->tocache(true);
			$response->setdata($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("database connection errors");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}

	}

}  

elseif(array_key_exists("page",$_GET)){

	if($_SERVER['REQUEST_METHOD'] == 'GET'){
		
		$page = $_GET['page'];
		
		if($page == '' || !is_numeric($page)){
		    $response = new Response();
	        $response->httpstatuscode(400);
	        $response->setsuccess(false);
	        $response->addmessage("page number cannont be blank and must be numeric");
	        $response->send();
	        exit();
		}
		$limitperpage = 10;
		
		
		try{
			$query = $readdb->prepare('select count(id) as totalnooftask from tbltasks');
			$query->execute();
            
			$row = $query->fetch(PDO::FETCH_ASSOC);
			
			$taskcount = intval($row['totalnooftask']);
			
			$numofpages = ceil($taskcount/$limitperpage);
			
            $rowcount = $query->rowcount();
			
			if($numofpages === 0){
				$numofpages = 1;
			}
			
			if($page > $numofpages || $page == 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("page not found");
	           $response->send();
	           exit(); 
			}

            $offset = ($page == 1 ? 0 : ($limitperpage*($page-1)));
            $query = $readdb->prepare('select id, title, data, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks limit :pglimit offset :offset');
			$query->bindParam(':pglimit', $limitperpage, PDO::PARAM_INT);
			$query->bindParam(':offset', $offset, PDO::PARAM_INT);
			$query->execute();
			
			$rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			 
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['data'], $row['deadline'], $row['completed']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['total_rows'] = $taskcount;
			$returndata['total_page'] = $numofpages;
			$returndata['has_next_page'] = $page < $numofpages;
			$returndata['has_previous_page'] = $page > 1;
			$returndata['Task'] = $taskarray;
			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->tocache(true);
			$response->setdata($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("failed to get page");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}

	}
			
	else{
		$response = new Response();
	    $response->httpstatuscode(500);
	    $response->setsuccess(false);
	    $response->addmessage("Data base connection error");
	    $response->send();
	    exit();
	}

}

elseif(empty($_GET)){
	
	if($_SERVER['REQUEST_METHOD'] === 'GET'){
		try{
			$query = $readdb->prepare('select id, title, data, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks');
			$query->execute();
            
            $rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			 
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['data'], $row['deadline'], $row['completed']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['Task'] = $taskarray;
			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->tocache(true);
			$response->setdata($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("database connection errors");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}

	}
	
	elseif($_SERVER['REQUEST_METHOD'] === 'POST'){
		try{
			if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
			  $response = new Response();
	          $response->httpstatuscode(400);
	          $response->setsuccess(false);
	          $response->addmessage("content type header is not set to json");
	          $response->send();
	          exit(); 
			}
			
			$rawpostdata = file_get_contents('php://input');
			
			if(!$jsondata = json_decode($rawpostdata)){
			  $response = new Response();
	          $response->httpstatuscode(400);
	          $response->setsuccess(false);
	          $response->addmessage("Request body is not valid json");
	          $response->send();
	          exit(); 
			}
			
			if(!isset($jsondata->title) || !isset($jsondata->completed)){
			  $response = new Response();
	          $response->httpstatuscode(400);
	          $response->setsuccess(false);
	          (!isset($jsondata->title) ? $response->addmessage("Title feild is mandatory and must be provided") : false);
			  (!isset($jsondata->completed) ? $response->addmessage("completed feild is mandatory and must be provided") : false);
	          $response->send();
	          exit(); 
			}
			
			$newtask = new Task(null, $jsondata->title, (isset($jsondata->data) ? $jsondata->data : null), (isset($jsondata->deadline) ? $jsondata->deadline : null), $jsondata->completed);
			
			$title = $newtask->gettitle();
			$data = $newtask->getdata();
			$deadline = $newtask->getdeadline();
			$completed = $newtask->getcompleted();
			echo $jsondata->title;
			$query = $writedb->prepare('insert into tbltasks (title, data, deadline, completed) values (:title, :data, STR_TO_DATE(:deadline, \'%d/%m/%Y %H:%i\'), :completed)');
			$query->bindParam(':title', $title, PDO::PARAM_STR);
			$query->bindParam(':data', $data, PDO::PARAM_STR);
			$query->bindParam(':deadline', $deadline, PDO::PARAM_STR);
			$query->bindParam(':completed', $completed, PDO::PARAM_STR);
			$query->execute();
			
			
			$rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}
			
			$lastpostid = $writedb->lastinsertid();
			
			$query = $readdb->prepare('select id, title, data, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where id = :postid');
			$query->bindParam(':postid', $lastpostid, PDO::PARAM_STR);
			$query->execute();
			
			$rowcount = $query->rowcount();
             
            if($rowcount === 0){
			   $response = new Response();
	           $response->httpstatuscode(404);
	           $response->setsuccess(false);
	           $response->addmessage("Task not found");
	           $response->send();
	           exit(); 
			}	
			
			while($row = $query->fetch(PDO::FETCH_ASSOC)){
             $task = new Task($row['id'], $row['title'], $row['data'], $row['deadline'], $row['completed']);
			 $taskarray[] = $task->returntaskarray();
			}
			
			$returndata = array();
			$returndata['rows_returned'] = $rowcount;
			$returndata['Task'] = $taskarray;
			
			$response = new Response();
	        $response->httpstatuscode(200);
	        $response->setsuccess(true);
	        $response->tocache(true);
			$response->setdata($returndata);
	        $response->send();
	        exit(); 
			
		}
		catch(PDOException $ex){
			error_log("database connection error".$ex, 0);
			$response = new Response();
	        $response->httpstatuscode(500);
	        $response->setsuccess(false);
	        $response->addmessage("failed to insert task into database check subbmited data for error");
	        $response->send();
	        exit(); 
			
		}
		catch(TaskException $ex){
			$response = new Response();
	        $response->httpstatuscode(400);
	        $response->setsuccess(false);
	        $response->addmessage($ex->getmessage());
	        $response->send();
	        exit(); 
			
		}
		
	}
	
	else{
		$response = new Response();
	    $response->httpstatuscode(405);
	    $response->setsuccess(false);
	    $response->addmessage("Request method not allowed");
	    $response->send();
	    exit();
	}

}
else{
	$response = new Response();
	$response->httpstatuscode(404);
	$response->setsuccess(false);
	$response->addmessage("end point not found");
	$response->send();
	exit();
}