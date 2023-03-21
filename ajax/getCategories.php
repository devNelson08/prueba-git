<?php

	define('AJAX_REQUEST', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

	if(!AJAX_REQUEST) {
		
		die();

	}else{

		require_once '../php/dbFunctions.php';
		$db = new DbFunctions();

		$categories = $db->getCategories();
		
		echo json_encode($categories);
	}

?>