<?php
	
	/* 
		Clase de conexión con la base de datos
		según la configuración establecida.
	*/
	
	require_once 'dbConfig.php';

	class DbConnection {
	
		protected $_connection;
		
		// Constructor
		function __construct() {
	 
		}
	 
		// Destructor
		function __destruct() {
			// $this->close();
		}

		// Conexión con la BD
		public function openConnection() {

			/* verificar la conexión */
			if (mysqli_connect_errno()) {
				printf("Falló la conexión failed: %s\n", $this->_connection->connect_error);
				exit();
			}
			
			// Conexión con mysql
			$this->_connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
			mysqli_set_charset($this->_connection,'utf8');
			return $this->_connection;
		}
	 
		// Desconexión con la BD
		public function closeConnection() {
			if ($this->openConnection->_connection) {
				mysqli_close($this->_connection);
			}
			return $this->_connection;
		}
			
	}

?>
