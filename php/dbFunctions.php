<?php
	
	/* 
		Funciones de cifrado y consultas directas a la base de datos
	*/

	require_once 'dbConnection.php';

	
	class DbFunctions {
		
		private $connection;
		// private $userConnection;
		// private $dbUser;
		
		
		// Constructor
		function __construct() {
			
			// Conexión con la base de datos
			$dbConnection = new DbConnection();
			$this->connection= $dbConnection->openConnection();
			
			
		}
		

		/////////////////////////////////////////////////////////////////////////////////////////////////////
		/* DATABASE CRUDS */
		
		//CRUD Films

		/* Get all Films*/
		function getFilms(){
			//List order by name 
			$result = mysqli_query($this->connection,"SELECT films.id, films.name, films.image, categories.name AS categories_name, directors.name AS directors_name ,films.signup_date
			FROM films INNER JOIN categories  ON categories.id = films.category_id INNER JOIN directors  ON directors.id = films.director_id 
			 WHERE films.active=1");
			$films = array();
			while($film =  mysqli_fetch_assoc($result)){
				$films[] = $film;
			}
			return $films;
		}



		
	}

?>