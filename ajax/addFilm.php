<?php

	define('AJAX_REQUEST', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

	if(!AJAX_REQUEST) {
		
		die();

	}else{

		require_once '../php/dbFunctions.php';
		$db = new DbFunctions();

        $name = $_POST["name"];
		$categoryId = $_POST["category_id"];
        $directorId = $_POST["director_id"];
		// $signupDate = $_POST["signup_date"];

		$success = $db->addFilm($name, $categoryId, $directorId);
		
		echo json_encode($success);
	}

?>