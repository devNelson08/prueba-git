<?php
	
	/* 
		Funciones de cifrado y consultas directas a la base de datos
	*/

	require_once 'dbConnection.php';
	//require_once 'dbUserConnection.php';
	//require_once 'dbUserFunctions.php';
	
	class DbFunctions {
		
		private $connection;
		// private $userConnection;
		// private $dbUser;
		
		
		// Constructor
		function __construct() {
			
			// Conexión con la base de datos
			$dbConnection = new DbConnection();
			$this->connection= $dbConnection->openConnection();
			
			// Conexión con la base de datos de usuarios
			// $dbUserConnection = new DbUserConnection();
			// $this->userConnection= $dbUserConnection->openConnection();
			
			// $this->dbUser = new DbUserFunctions();
		}
		
		/* Valida si las variables de sesión corresponden a un usuario válido */
		function validateUserBySession($sessionEmail, $sessionPassword){
			$sessionEmail = mysqli_real_escape_string($this->connection, $sessionEmail);
			$result = mysqli_query($this->connection,"SELECT * FROM users WHERE email='".$sessionEmail."' ");
			$user = mysqli_fetch_assoc($result);
			if(password_verify($user["password"], $sessionPassword)){
				return true;
			}else{
				return false;
			}
		}

		/* Cambia la contraseña de un usuario */
		function resetPassword($email, $password){
			$email = mysqli_real_escape_string($this->connection, $email);
			// $passwordHashed = password_hash($password, PASSWORD_DEFAULT);
			$result = mysqli_query($this->connection, "UPDATE users SET `password` = '".$password."' WHERE email = '".$email."'");
			if($result){
				return true;
			}else{
				return false;
			}
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

		//CRUD User

		/* Get all users without password*/
		function getUsers(){
			// $result = mysqli_query($this->connection,"SELECT `id`, `name`, `email`, `avatar`, role_id,  employee_id, `active` FROM `users`");
			$result = mysqli_query($this->connection,"SELECT users.id, users.name, users.email, roles.id AS role_id, roles.name AS role_name, (SELECT employees.name FROM employees WHERE employees.id= users.employee_id) AS employee_name, users.employee_id FROM users, roles WHERE users.role_id = roles.id ");
			// $result = mysqli_query($this->connection,"SELECT	 * FROM users WHERE active=1 ORDER BY `name` asc");
			// $user = mysqli_fetch_assoc($result);
			$users = array();
			while($user =  mysqli_fetch_assoc($result)){
				$users[] = $user;
			}
			return $users;
		}

		// function getUsersById($id){
		// 	// $result = mysqli_query($this->connection,"SELECT * FROM clients WHERE id='".$id."' ");
		// 	$result = mysqli_query($this->connection,"SELECT ca.name, ca.last_name, ca.email, c.name AS client_name, st.name AS sending_type_name
		// 		FROM `client-addresses` ca, clients c, `sending-types` st 
		// 		WHERE ca.client_id=c.id
		// 		AND ca.sending_type_id=st.id
		// 		AND client_id='".$id."'");
		// 	$client = mysqli_fetch_assoc($result);
		// 	if($result){
		// 		return $client;
		// 	}else{
		// 		return false;
		// 	}
		// }

		/* Valida si las variables de sesión corresponden a un usuario válido */
		function validateUserRole($sessionEmail, $sessionPassword, $roles){
			$sessionEmail = mysqli_real_escape_string($this->connection, $sessionEmail);
			$result = mysqli_query($this->connection,"SELECT * FROM users WHERE email='".$sessionEmail."' ");
			$user = mysqli_fetch_assoc($result);
			if(password_verify($user["password"], $sessionPassword) && in_array($user["role"], $roles)){
				return true;
			}else{
				return false;
			}
		}

		/* Valida si las variables de sesión corresponden a un usuario válido */

		function validateUserPermission($sessionEmail, $sessionPassword, $permissions){

			$sessionEmail = mysqli_real_escape_string($this->connection, $sessionEmail);
			$result = mysqli_query($this->connection,"SELECT * FROM users WHERE email='".$sessionEmail."'");
			$user = mysqli_fetch_assoc($result);
			if(password_verify($user["password"], $sessionPassword)){
				// $result = mysqli_query($this->connection,"SELECT * FROM users WHERE email='".$sessionEmail."'");
				// $user = mysqli_fetch_assoc($result);
				$permissionsArrayString = "";
				foreach ($permissions as $permission) {
					$permissionsArrayString.= $permission.",";
				}
				$permissionsArrayString = rtrim($permissionsArrayString, ",");
				$result = mysqli_query($this->connection,"SELECT(SELECT COUNT(*) FROM `user_permission` WHERE permission_id IN (".$permissionsArrayString.") AND `specific_id` IS null AND `user_id`=".$user["id"].") = '".count($permissions)."' AS validation");
				$validation = mysqli_fetch_assoc($result)["validation"];
				if($validation){
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}

		//Select employees who are not assigned to a user
		function getEmployeesNotAssignedToUser(){
			// $result = mysqli_query($this->connection,"SELECT  * FROM  employees  WHERE id NOT IN (SELECT users.employee_id FROM users)");
			$result = mysqli_query($this->connection,"SELECT  * FROM  employees  WHERE id NOT IN (SELECT users.employee_id  FROM users WHERE users.employee_id IS NOT NULL )");
			// $result = mysqli_query($this->connection,"SELECT c.id, c.name, c.image, c.cif, c.address, c.web, c.postal_code, c.constitution_date, c.phone, c.capital, c.city, c.country, c.commercial_register, c.social_object, c.observations, c.signup_date, c.active FROM companies c INNER JOIN service_company sc ON NOT sc.company_id=c.id AND sc.service_id='".$serviceId."'");
			$employees = array();
			while($employee =  mysqli_fetch_assoc($result)){
				$employees[] = $employee;
			}
			return $employees;
		}

		/* add a user */
		function addUser($name, $email, $password, $avatar, $roleId, $employeeId){
			$password = password_hash($password, PASSWORD_DEFAULT);
			$signupDate = date('Y-m-d H:i:s');
			if($employeeId == null){
				$employeeId="NULL";
			} else {
				$employeeId="'".$employeeId."'";
			}
			
			$currentUser = $this->dbUser->getUserBySession($_SESSION['email'], $_SESSION['password']);
			$userResult = mysqli_query($this->userConnection, "INSERT INTO `users` (`name`, `email`, `password`, avatar, `code`, `verification`, `attempts`, `last_try`, `signup_date`,  `active`, `db_id`)  VALUES ('".$name."','".$email."','".$password."',NULL ,NULL ,0 ,0 ,NULL,'".$signupDate."','1', '".$currentUser['db_id']."')");
			// $userResult = mysqli_query($this->userConnection, "INSERT INTO `users` (`name`, `email`, `password`, avatar, `code`, `verification`, `attempts`, `last_try`, `signup_date`,  `active`, `db_id`)  VALUES ('prueba','pruebaemail','123','NULL' ,'NULL' ,0 ,0 ,'NULL',CURDATE(),'1', '1')");
			

			$result = mysqli_multi_query($this->connection, "START TRANSACTION;
				INSERT INTO `users` (`name`, `email`, `password`, `avatar`, `role_id`, `employee_id`, `active`) VALUES ('".$name."','".$email."','".$password."', NULL, ".$roleId.", ".$employeeId.", 1);
				INSERT INTO user_permission (`permission_id`, `specific_id`, `user_id`) SELECT role_permission.permission_id , null, users.id FROM `role_permission`, users  WHERE role_permission.role_id=users.role_id AND users.id=LAST_INSERT_ID();
				COMMIT;"
			);
			if($result && $userResult){
				return true;
			}else{
				return false;
			}

		}

		function userEmailExists($email){
			$result = mysqli_query($this->userConnection, "SELECT email FROM users WHERE email='".$email."'");
			if(mysqli_num_rows($result)>0){
				return true;
			}else{
				return false;
			}
		}

		//CRUD Roles

		/* Get all roles*/
		function getRoles(){
			//List order by name 
			$result = mysqli_query($this->connection, "SELECT * FROM roles ORDER BY `id` asc");

			$roles = array();
			while($role =  mysqli_fetch_assoc($result)){
				$roles[] = $role;
			}
			return $roles;
		}

		//CRUD Clients

		/* Get all clients*/
		function getClients(){
			//List order by name 
			$result = mysqli_query($this->connection,"SELECT * FROM clients WHERE active=1 ORDER BY `name` asc");

			//LIST the last rol inserted
		
			$clients = array();
			while($client =  mysqli_fetch_assoc($result)){
				$clients[] = $client;
			}
			return $clients;
		}

		/* Get an clients by id */
		function getClientById($id){
			$result = mysqli_query($this->connection,"SELECT * FROM clients WHERE id='".$id."' ");
			$client = mysqli_fetch_assoc($result);
			if($result){
				return $client;
			}else{
				return false;
			}
		}

		/* Update a client */
		function setClient($id, $name, $cnmv, $cif, $phone, $image, $address, $postalCode, $web, $city, $country, $accountingAccount, $iban, $paymentMethod, $commercialRegister, $socialObject, $observations, $drive){
			$result = mysqli_query($this->connection, "UPDATE `clients` SET `name`='".$name."', `cnmv`='".$cnmv."',`cif`='".$cif."', `phone`='".$phone."', `image`='".$image."', `address`='".$address."', `postal_code`='".$postalCode."',`web`='".$web."', `city`='".$city."', `country`='".$country."', `accounting_account`='".$accountingAccount."', `iban`='".$iban."', `payment_method`='".$paymentMethod."', `commercial_register`='".$commercialRegister."',`social_object`='".$socialObject."',`observations`='".$observations."', `drive`='".$drive."' WHERE `id`='".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		/*Add an client*/
		function addClient($name, $cnmv, $cif, $phone, $image, $address, $postalCode, $web, $city, $country, $accountingAccount, $iban, $paymentMethod, $commercialRegister, $socialObject, $observations, $drive){
			$result = mysqli_query($this->connection, "INSERT INTO `clients` (`name`, `cnmv`, `cif`, `phone`, `image`, `address`, `postal_code`, `web`, `city`, `country`, `accounting_account`,`iban`, `payment_method`, `commercial_register`,`social_object`, `observations`, `drive`, `active`)  VALUES ('".$name."', '".$cnmv."', '".$cif."','".$phone."','".$image."', '".$address."',  '".$postalCode."', '".$web."', '".$city."', '".$country."', '".$accountingAccount."','".$iban."', '".$paymentMethod."', '".$commercialRegister."','".$socialObject."','".$observations."', '".$drive."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function clientCifExists($cif){
			$result = mysqli_query($this->connection, "SELECT cif FROM clients WHERE cif='".$cif."'");
			if(mysqli_num_rows($result)>0){
				return true;
			}else{
				return false;
			}
		}

		function disableClient($clientId){
			$result = mysqli_query($this->connection, "UPDATE `clients` SET `active`=0 WHERE `id` = '".$clientId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}
		
		/* Get an clients-adresses by id */
		function getClientAdressesById($id){
			// $result = mysqli_query($this->connection,"SELECT * FROM clients WHERE id='".$id."' ");
			$result = mysqli_query($this->connection,"SELECT (SELECT GROUP_CONCAT(email) FROM `client-addresses` WHERE `client_id`='".$id."' AND `sending_type_id`=1) as recipients, (SELECT GROUP_CONCAT(email) FROM `client-addresses` WHERE `client_id`='".$id."' AND `sending_type_id`=2) as carbonCopyRecipients, (SELECT GROUP_CONCAT(email) FROM `client-addresses` WHERE `client_id`='".$id."' AND `sending_type_id`=3) as blindCarbonCopyRecipients;");
			$client = mysqli_fetch_assoc($result);
			if($result){
				return $client;
			}else{
				return false;
			}
		}


		function getClientShippingAddressesOfClient($clientId){
		// $result = mysqli_query($this->connection,"SELECT * FROM clients WHERE id='".$id."' ");
			$result = mysqli_query($this->connection,"SELECT ca.id AS id , ca.name AS name , ca.last_name AS last_name, ca.email AS email, c.name AS client_name, st.name AS sending_type_name FROM `client-addresses` ca, clients c, `sending-types` st WHERE ca.client_id=c.id AND ca.sending_type_id=st.id AND ca.active=1 AND client_id='".$clientId."'" );
			$clientContacts = array();
			while($clientContact =  mysqli_fetch_assoc($result)){
				$clientContacts[] = $clientContact;
			}
			return $clientContacts;
		}


		//CRUD Suppliers

		/* Get all suppliers*/
		function getSuppliers(){
			//List order by name 
			$result = mysqli_query($this->connection,"SELECT * FROM suppliers WHERE active=1 ORDER BY `name` asc");
			$suppliers = array();
			while($supplier =  mysqli_fetch_assoc($result)){
				$suppliers[] = $supplier;
			}
			return $suppliers;
		}

		/* Get an suppliers by id */
		function getSupplierById($id){
			$result = mysqli_query($this->connection,"SELECT suppliers.`id`, suppliers.`name`, suppliers.`cif`, suppliers.`contact`, suppliers.`address`, suppliers.`accounting_account`, suppliers.`spending_account`, suppliers.`iva-type_id`, suppliers.`retention-type_id`, suppliers.`active`, `iva-types`.`percentage` AS `iva-type_percentage`, `iva-types`.`recharge` AS `iva-type_recharge`, `retention-types`.`percentage` AS `retention-type_percentage` FROM suppliers, `iva-types`, `retention-types` WHERE suppliers.`iva-type_id`=`iva-types`.id AND suppliers.`retention-type_id`=`retention-types`.id AND suppliers.id='".$id."'");
			$supplier = mysqli_fetch_assoc($result);
			if($result){
				return $supplier;
			}else{
				return false;
			}
		}

		/* Update a supplier */
		function setSupplier($id, $name, $cif, $contact, $address, $accountingAccount, $spendingAccount, $ivaTypeId, $retentionTypeId){
			$result = mysqli_query($this->connection, "UPDATE `suppliers` SET `name`='".$name."', `cif`='".$cif."', `contact`='".$contact."', `address`='".$address."', `accounting_account`='".$accountingAccount."', `spending_account`='".$spendingAccount."', `iva-type_id`='".$ivaTypeId."', `retention-type_id`='".$retentionTypeId."' WHERE `id`='".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		/*Add a supplier*/
		function addSupplier($name, $cif, $contact, $address, $accountingAccount, $spendingAccount, $ivaTypeId, $retentionTypeId){
			$result = mysqli_query($this->connection, "INSERT INTO `suppliers` (`name`, `cif`, `contact`, `address`, `accounting_account`, `spending_account`, `iva-type_id`, `retention-type_id`, `active`)  VALUES ('".$name."', '".$cif."', '".$contact."', '".$address."', '".$accountingAccount."', '".$spendingAccount."', '".$ivaTypeId."', '".$retentionTypeId."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function disableSupplier($id){
			$result = mysqli_query($this->connection, "UPDATE `suppliers` SET `active`=0 WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function getSupplierBills($supplierId){
			$result = mysqli_query($this->connection,"SELECT * FROM `supplier-bills` WHERE `supplier_id`='".$supplierId."'");
			$suppliers = array();
			while($supplier =  mysqli_fetch_assoc($result)){
				$suppliers[] = $supplier;
			}
			return $suppliers;
		}

		/*Add a supplier bill*/
		function addSupplierBill($name, $fileExtension, $amount, $billNumber, $registrationDate, $referenceDate, $operationDate, $preparatedDate, $issueDate, $expirationDate, $amendingDate, $sendingDate, $recordingDate, $collectingDate, $accountingDate, $observations, $supplierName, $supplierCif, $supplierAccountingAccount, $supplierSpendingAccount, $supplierIvaTypePercentage, $supplierRetentionTypePercentage, $companyId, $supplierId){
			if($registrationDate == null){
				$registrationDate="NULL";
			} else {
				$registrationDate="'".$registrationDate."'";
			}
			if($referenceDate == null){
				$referenceDate="NULL";
			} else {
				$referenceDate="'".$referenceDate."'";
			}
			if($issueDate == null){
				$issueDate="NULL";
			} else {
				$issueDate="'".$issueDate."'";
			}
			if($operationDate == null){
				$operationDate="NULL";
			} else {
				$operationDate="'".$operationDate."'";
			}
			if($preparatedDate == null){
				$preparatedDate="NULL";
			} else {
				$preparatedDate="'".$preparatedDate."'";
			}
			if($issueDate == null){
				$issueDate="NULL";
			} else {
				$issueDate="'".$issueDate."'";
			}
			if($expirationDate == null){
				$expirationDate="NULL";
			} else {
				$expirationDate="'".$expirationDate."'";
			}
			if($amendingDate == null){
				$amendingDate="NULL";
			} else {
				$amendingDate="'".$amendingDate."'";
			}
			if($sendingDate == null){
				$sendingDate="NULL";
			} else {
				$sendingDate="'".$sendingDate."'";
			}
			if($recordingDate == null){
				$recordingDate="NULL";
			} else {
				$recordingDate="'".$recordingDate."'";
			}
			if($collectingDate == null){
				$collectingDate="NULL";
			} else {
				$collectingDate="'".$collectingDate."'";
			}
			if($accountingDate == null){
				$accountingDate="NULL";
			} else {
				$accountingDate="'".$accountingDate."'";
			}
			$registrationDate = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "INSERT INTO `supplier-bills` (`name`, `file_extension`, `amount`, `bill_number`, `registration_date`, `reference_date`, `operation_date`, `preparated_date`, `issue_date`, `expiration_date`, `amending_date`, `sending_date`, `recording_date`, `collecting_date`, `accounting_date`, `observations`, `supplier_name`, `supplier_cif`, `supplier_accounting_account`, `supplier_spending_account`, `supplier_iva_type_percentage`, `supplier_retention_type_percentage`, `company_id`, `supplier_id`) VALUES ('".$name."','".$fileExtension."','".$amount."','".$billNumber."','".$registrationDate."',".$referenceDate.",".$operationDate.",".$preparatedDate.",".$issueDate.",".$expirationDate.",".$amendingDate.",".$sendingDate.",".$recordingDate.",".$collectingDate.",".$accountingDate.",'".$observations."','".$supplierName."','".$supplierCif."','".$supplierAccountingAccount."','".$supplierSpendingAccount."','".$supplierIvaTypePercentage."','".$supplierRetentionTypePercentage."','".$companyId."','".$supplierId."')");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function supplierCifExists($cif){
			$result = mysqli_query($this->connection, "SELECT * FROM suppliers WHERE cif='".$cif."'");
			if(mysqli_num_rows($result)>0){
				return true;
			}else{
				return false;
			}
		}

		// CRUD Employees
		
		/* Get all employees */
		function getEmployees(){
			$result = mysqli_query($this->connection,"SELECT e.id, e.name, e.image, e.company_id, e.department_id, e.employment_id, e.contract, e.email, e.phone, e.personal_email, e.personal_phone, e.observations, e.postal_code, e.province, e.city, e.country, e.facebook, e.instagram, e.twitter, e.linkedin, e.active, e.signup_date, c.name AS company_name, d.name AS department_name, em.name AS employment_name FROM employees e INNER JOIN companies c ON c.id = e.company_id INNER JOIN departments d ON d.id = e.department_id INNER JOIN employments em ON em.id = e.employment_id WHERE e.active=1");
			$employees = array();
			while($employee =  mysqli_fetch_assoc($result)){
				$employees[] = $employee;
			}
			return $employees;
		}

		function getClientsNotAssignedToEmployee($employeeId){
			// $result = mysqli_query($this->connection,"SELECT c.id, c.name, c.image, c.cif, c.address, c.web, c.postal_code, c.constitution_date, c.phone, c.capital, c.city, c.country, c.commercial_register, c.social_object, c.observations, c.signup_date, c.active FROM companies c INNER JOIN service_company sc ON NOT sc.company_id=c.id AND sc.service_id='".$serviceId."'");
			$result = mysqli_query($this->connection,"SELECT c.id, c.name, c.cnmv, c.image,  c.address, c.web, c.city, c.country, c.observations, c.drive, c.active FROM clients c WHERE c.id NOT IN (SELECT sc.client_id FROM employee_client sc WHERE sc.client_id=c.id AND sc.employee_id='".$employeeId."')");
			$clients = array();
			while($client =  mysqli_fetch_assoc($result)){
				$clients[] = $client;
			}
			return $clients;
		}


		/* Get an employee by id */
		function getEmployeeById($id){
			$result = mysqli_query($this->connection,"SELECT * FROM employees WHERE id='".$id."' ");
			$employee = mysqli_fetch_assoc($result);
			if($result){
				return $employee;
			}else{
				return false;
			}
		}
		
		/* Get the last employee */
		function getLastEmployee(){
			$result=mysqli_query($this->connection,"SELECT * FROM `employees` ORDER BY `signup_date` DESC LIMIT 1"); 
			$employee = mysqli_fetch_assoc($result);
			if($result){
				return $employee;
			}else{
				return false;
			}
		}

		/* Get the employees of a company */
		function getEmployeesOfCompany($companyId){
			$result = mysqli_query($this->connection,"SELECT e.id, e.name, e.image AS image, e.company_id, e.department_id, e.employment_id, e.contract, e.email, e.phone, e.personal_email, e.personal_phone, e.observations, e.postal_code, e.province, e.city, e.country, e.facebook, e.instagram, e.twitter, e.linkedin, e.active, e.signup_date, c.name AS company_name, d.name AS department_name, em.name AS employment_name FROM employees e INNER JOIN companies c ON c.id = e.company_id INNER JOIN departments d ON d.id = e.department_id INNER JOIN employments em ON em.id = e.employment_id WHERE e.company_id='".$companyId."' AND e.active=1");
			$employees = array();
			while($employee =  mysqli_fetch_assoc($result)){
				$employees[] = $employee;
			}
			return $employees;
		}

		/* Get the employees of a department */
		function getEmployeesOfDepartment($departmentId){
			$result = mysqli_query($this->connection,"SELECT e.id, e.name, e.image, e.company_id, e.department_id, e.employment_id, e.contract, e.email, e.phone, e.personal_email, e.personal_phone, e.observations, e.postal_code, e.province, e.city, e.country, e.facebook, e.instagram, e.twitter, e.linkedin, e.active, e.signup_date, c.name AS company_name, d.name AS department_name, em.name AS employment_name FROM employees e INNER JOIN companies c ON c.id = e.company_id INNER JOIN departments d ON d.id = e.department_id INNER JOIN employments em ON em.id = e.employment_id WHERE e.department_id='".$departmentId."' AND e.active=1");
			$employees = array();
			while($employee =  mysqli_fetch_assoc($result)){
				$employees[] = $employee;
			}
			return $employees;
		}

		/* Get the employees of a employment */
		function getEmployeesOfEmployment($employmentId){
			$result = mysqli_query($this->connection,"SELECT e.id, e.name, e.image, e.company_id, e.department_id, e.employment_id, e.contract, e.email, e.phone, e.personal_email, e.personal_phone, e.observations, e.postal_code, e.province, e.city, e.country, e.facebook, e.instagram, e.twitter, e.linkedin, e.active, e.signup_date, c.name AS company_name, d.name AS department_name, em.name AS employment_name FROM employees e INNER JOIN companies c ON c.id = e.company_id INNER JOIN departments d ON d.id = e.department_id INNER JOIN employments em ON em.id = e.employment_id WHERE e.employment_id='".$employmentId."' AND e.active=1");
			$employees = array();
			while($employee =  mysqli_fetch_assoc($result)){
				$employees[] = $employee;
			}
			return $employees;
		}

		
		/*Add an employee*/
		function addEmployee($name, $image, $companyId, $departmentId, $employmentId, $contract, $email, $phone, $personalEmail, $personalPhone, $observations, $postalCode, $province, $city, $country, $facebook, $instagram, $twitter, $linkedin){
			$signupDate = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "INSERT INTO `employees`(`name`, `image`, `company_id`, `department_id`, `employment_id`, `contract`, `email`, `phone`, `personal_email`, `personal_phone`, `observations`, `postal_code`, `province`, `city`, `country`, `facebook`, `instagram`, `twitter`, `linkedin`, `active`, `signup_date`) VALUES ('".$name."','".$image."','".$companyId."','".$departmentId."','".$employmentId."','".$contract."','".$email."','".$phone."','".$personalEmail."','".$personalPhone."','".$observations."','".$postalCode."','".$province."','".$city."','".$country."','".$facebook."','".$instagram."','".$twitter."','".$linkedin."', 1,'".$signupDate."')");
			// $result = mysqli_query($this->connection, "INSERT INTO `employees`(`name`, `image`, `company_id`, `department_id`, `employment_id`, `contract`, `email`, `phone`, `personal_email`, `personal_phone`, `observations`, `postal_code`, `province`, `city`, `country`, `facebook`, `instagram`, `twitter`, `linkedin`, `active`, `signup_date`) VALUES ('".$name."','".$image."','".$companyId."','".$departmentId."',".$employmentId.",'c','e','p','pe','pp','o','pc','p','c','c','f','i','t','l',1, $signupDate)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		/* Update an employee */
		function setEmployee($id, $name, $image, $companyId, $departmentId, $employmentId, $contract, $email, $phone, $personalEmail, $personalPhone, $observations, $postalCode, $province, $city, $country, $facebook, $instagram, $twitter, $linkedin){
			$result = mysqli_query($this->connection, "UPDATE `employees` SET `name`='".$name."',`image`='".$image."',`company_id`='".$companyId."',`department_id`='".$departmentId."',`employment_id`='".$employmentId."',`contract`='".$contract."',`email`='".$email."',`phone`='".$phone."',`personal_email`='".$personalEmail."',`personal_phone`='".$personalPhone."',`observations`='".$observations."',`postal_code`='".$postalCode."',`province`='".$province."',`city`='".$city."',`country`='".$country."',`facebook`='".$facebook."',`instagram`='".$instagram."',`twitter`='".$twitter."',`linkedin`='".$linkedin."' WHERE `id`='".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function disableEmployee($employeeId){
			$result = mysqli_query($this->connection, "UPDATE `employees` SET `active`=0 WHERE `id` = '".$employeeId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD Company

		function getCompanies(){
			$result = mysqli_query($this->connection,"SELECT * FROM companies WHERE active=1");
			$companies = array();
			while($company =  mysqli_fetch_assoc($result)){
				$companies[] = $company;
			}
			return $companies;
		}

		function getCompaniesNotAssignedToService($serviceId){
			// $result = mysqli_query($this->connection,"SELECT c.id, c.name, c.image, c.cif, c.address, c.web, c.postal_code, c.constitution_date, c.phone, c.capital, c.city, c.country, c.commercial_register, c.social_object, c.observations, c.signup_date, c.active FROM companies c INNER JOIN service_company sc ON NOT sc.company_id=c.id AND sc.service_id='".$serviceId."'");
			$result = mysqli_query($this->connection,"SELECT c.id, c.name, c.image, c.cif, c.address, c.web, c.postal_code, c.constitution_date, c.phone, c.capital, c.city, c.country, c.commercial_register, c.social_object, c.observations, c.signup_date, c.active FROM companies c WHERE c.id NOT IN (SELECT sc.company_id FROM service_company sc WHERE sc.company_id=c.id AND sc.service_id='".$serviceId."')");
			$companies = array();
			while($company =  mysqli_fetch_assoc($result)){
				$companies[] = $company;
			}
			return $companies;
		}
		
		function getCompaniesWithBillingToRecord(){
			// $result = mysqli_query($this->connection,"SELECT c.id, c.name, c.image, c.cif, c.address, c.web, c.postal_code, c.constitution_date, c.phone, c.capital, c.city, c.country, c.commercial_register, c.social_object, c.observations, c.signup_date, c.active FROM companies c INNER JOIN service_company sc ON NOT sc.company_id=c.id AND sc.service_id='".$serviceId."'");
			$result = mysqli_query($this->connection,"SELECT companies.id, companies.name FROM `bills`, service_company_client, service_company, companies WHERE bills.service_company_client_id=service_company_client.id AND service_company_client.service_company_id=service_company.id AND service_company.company_id=companies.id AND bills.issue_date IS NOT NULL AND bills.recording_date IS NULL GROUP BY companies.id");
			$companies = array();
			while($company =  mysqli_fetch_assoc($result)){
				$companies[] = $company;
			}
			return $companies;
		}
		
		function getCompanyById($id){
			$result = mysqli_query($this->connection,"SELECT companies.`id`, companies.`name`, companies.`image`, companies.`cif`, companies.`address`, companies.`web`, companies.`postal_code`, companies.`constitution_date`, companies.`phone`, companies.`capital`, companies.`city`, companies.`country`, companies.`corporative_color`, companies.`font_type_id`, companies.`email`, companies.`billing_email`, companies.`iban`, companies.`acronym`, companies.`commercial_register`, companies.`social_object`, companies.`observations`, companies.`signup_date`, companies.`active`, `font-types`.name as `font_type_name` FROM companies, `font-types` WHERE companies.`font_type_id`=`font-types`.id AND companies.id='".$id."' ");
			$company = mysqli_fetch_assoc($result);
			if($result){
				return $company;
			}else{
				return false;
			}
		}

		/* add a company */
		function addCompany($name, $image, $cif, $address, $web, $postalCode, $constitutionDate, $phone, $capital, $city, $country, $colorCorporative, $fontTypeId, $email, $iban, $billingEmail, $acronym, $commercialRegister, $socialObject, $observations){
			$signupDate = date('Y-m-d H:i:s');
			if($constitutionDate == null){
				$constitutionDate="NULL";
			} else {
				$constitutionDate="'".$constitutionDate."'";
			}
			$result = mysqli_query($this->connection, "INSERT INTO `companies` (`name`, `image`, `cif`, `address`, `web`, `postal_code`, `constitution_date`, `phone`, `capital`, `city`, `country`, `corporative_color`, `font_type_id`, `email`, `iban`, `billing_email`, `acronym`, `commercial_register`, `social_object`, `observations`, `signup_date`, `active`)  VALUES ('".$name."','".$image."','".$cif."','".$address."','".$web."','".$postalCode."',".$constitutionDate.",'".$phone."','".$capital."','".$city."','".$country."', '".$colorCorporative."', '".$fontTypeId."', '".$email."', '".$iban."', '".$billingEmail."', '".$acronym."', '".$commercialRegister."', '".$socialObject."','".$observations."','".$signupDate."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function companyCifExists($cif){
			$result = mysqli_query($this->connection, "SELECT cif FROM companies WHERE cif='".$cif."'");
			
			if(mysqli_num_rows($result)>0){
				return true;
			}else{
				return false;
			}
		}

		/* set company */
		function setCompany($id, $name, $image, $cif, $address, $web, $postalCode, $constitutionDate, $phone, $capital, $city, $country, $colorCorporative, $fontTypeId, $email, $iban,$billingEmail, $acronym, $commercialRegister, $socialObject, $observations){
			if($constitutionDate == null){
				$constitutionDate="NULL";
			} else {
				$constitutionDate="'".$constitutionDate."'";
			}
			$result = mysqli_query($this->connection, "UPDATE `companies` SET `name`='".$name."',`image`='".$image."',`cif`='".$cif."',`address`='".$address."',`web`='".$web."',`postal_code`='".$postalCode."',`constitution_date`=".$constitutionDate.",`phone`='".$phone."',`capital`='".$capital."',`city`='".$city."',`country`='".$country."', `corporative_color`='".$colorCorporative."', `font_type_id`='".$fontTypeId."', `email`='".$email."', `iban`='".$iban."', `billing_email`='".$billingEmail."', `acronym`='".$acronym."',`commercial_register`='".$commercialRegister."',`social_object`='".$socialObject."',`observations`='".$observations."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function disableCompany($companyId){
			$result = mysqli_query($this->connection, "UPDATE `companies` SET `active`=0 WHERE `id` = '".$companyId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}


		// CRUD Department

		// Get departments of a company
		function getDepartmentsOfCompany($id){
			$result = mysqli_query($this->connection,"SELECT * FROM departments WHERE company_id='".$id."' AND active=1");
			$departments = array();
			while($department =  mysqli_fetch_assoc($result)){
				$departments[] = $department;
			}
			return $departments;
		}

		/* Get a department by id */
		function getDepartmentById($id){
			$result = mysqli_query($this->connection,"SELECT * FROM departments WHERE id='".$id."' ");
			$department = mysqli_fetch_assoc($result);
			if($result){
				return $department;
			}else{
				return false;
			}
		}

		/* Add a department */
		function addDepartment($name, $description, $companyId){
			$result = mysqli_query($this->connection, "INSERT INTO `departments`(`name`, `description`, `company_id`, `active`) VALUES ('".$name."', '".$description."', '".$companyId."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// function addDepartments($departmentNames, $companyId){
		// 	if($departmentNames){
		// 		$values ="";
		// 		foreach ($departmentNames as $departmentName){
		// 			$values.= "('".$departmentName."','".$companyId."'),";
		// 		}
		// 		$values = substr($values, 0, -1);
		// 		$result = mysqli_query($this->connection, "INSERT INTO `departments`(`name`, `company_id`) VALUES ".$values);
		// 		if($result){
		// 			return true;
		// 		}else{
		// 			return false;
		// 		}
		// 	} else {
		// 		return true;
		// 	}
		// }

		/* Update a service */
		function setDepartment($id, $name, $description){
			$result = mysqli_query($this->connection, "UPDATE `departments` SET `name`='".$name."',`description`='".$description."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// function setDepartments($departments) {
		// 	if($departments){
		// 		$changes = "";
		// 		$ids = "";
		// 		foreach ($departments as $id => $data){
		// 			$changes.= "WHEN '".$id."' THEN '".$data["name"]."'";
		// 			$ids.= "'".$id."', ";
		// 		}
		// 		$ids = substr($ids, 0, -2);
		// 		$result = mysqli_query($this->connection, "UPDATE departments SET name = CASE id ".$changes." ELSE name END WHERE id IN(".$ids.")");
		// 		if($result){
		// 			return true;
		// 		}else{
		// 			return false;
		// 		}
		// 	} else {
		// 		return true;
		// 	}
		// }

		function disableDepartment($departmentId){
			$result = mysqli_query($this->connection, "UPDATE `departments` SET `active`=0 WHERE `id` = '".$departmentId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD Employment
		/* Get the employments of a company */
		function getEmploymentsOfCompany($companyId){
			$result = mysqli_query($this->connection,"SELECT * FROM employments WHERE company_id='".$companyId."' AND active=1");
			$employments = array();
			while($employment =  mysqli_fetch_assoc($result)){
				$employments[] = $employment;
			}
			return $employments;
		}

		function getEmploymentById($id){
			$result = mysqli_query($this->connection,"SELECT * FROM employments WHERE id='".$id."' ");
			$employment = mysqli_fetch_assoc($result);
			if($result){
				return $employment;
			}else{
				return false;
			}
		}

		function disableEmployment($employmentId){
			$result = mysqli_query($this->connection, "UPDATE `employments` SET `active`=0 WHERE `id` = '".$employmentId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		/* Add a employment */
		function addEmployment($name, $description, $companyId){
			$result = mysqli_query($this->connection, "INSERT INTO `employments`(`name`, `description`, `company_id`, `active`) VALUES ('".$name."', '".$description."', '".$companyId."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		/* Update an employment */
		function setEmployment($id, $name, $description){
			$result = mysqli_query($this->connection, "UPDATE `employments` SET `name`='".$name."',`description`='".$description."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD Service
		
		/* Add an service */

		function addService($name, $acronym, $description){
			$signupDate = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "INSERT INTO `services`(`name`, `acronym`, `description`, `contract_template`, `budget_template`, `signup_date`, `active`) VALUES ('".$name."','".$acronym."','".$description."', '<p>&nbsp;</p>', '<p>&nbsp;</p>','".$signupDate."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		/* Update a service */
		function setService($id, $name, $acronym, $description){
			$result = mysqli_query($this->connection, "UPDATE `services` SET `name`='".$name."',`acronym`='".$acronym."',`description`='".$description."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		/* Update a serviceTemplate */
		function setServiceBudgetTemplate($id, $budgetTemplate){
			$result = mysqli_query($this->connection, "UPDATE `services` SET `budget_template`='".$budgetTemplate."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function setServiceContractTemplate($id, $contractTemplate){
			$result = mysqli_query($this->connection, "UPDATE `services` SET `contract_template`='".$contractTemplate."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		/* Get all services*/
		function getServices(){
			$result = mysqli_query($this->connection,"SELECT * FROM services WHERE active=1");
			$services = array();
			while($service =  mysqli_fetch_assoc($result)){
				$services[] = $service;
			}
			return $services;
		}

		

		/* Get a service by id */
		function getServiceById($id){
			$result = mysqli_query($this->connection,"SELECT * FROM services WHERE id='".$id."' ");
			$service = mysqli_fetch_assoc($result);
			if($result){
				return $service;
			}else{
				return false;
			}
		}

		/* Get the last service */
		function getLastService(){
			$result=mysqli_query($this->connection,"SELECT * FROM `services` ORDER BY `signup_date` DESC LIMIT 1"); 
			$service = mysqli_fetch_assoc($result);
			if($result){
				return $service;
			}else{
				return false;
			}
		}

		function disableService($serviceId){
			$result = mysqli_query($this->connection, "UPDATE `services` SET `active`=0 WHERE `id` = '".$serviceId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD Subservices
		function addSubservice($name, $description, $baseRate, $serviceId){
			$active = 1;
			$result = mysqli_query($this->connection, "INSERT INTO `subservices`(`name`, `description`, `base_rate`, `service_id`, `active`) VALUES ('".$name."','".$description."','".$baseRate."','".$serviceId."','".$active."')");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function getSubserviceById($subserviceId){
			$result = mysqli_query($this->connection,"SELECT * FROM subservices WHERE id='".$subserviceId."' ");
			$subservice = mysqli_fetch_assoc($result);
			if($result){
				return $subservice;
			}else{
				return false;
			}
		}

		function getSubservicesOfService($serviceId){
			$result = mysqli_query($this->connection,"SELECT * FROM  subservices WHERE service_id='".$serviceId."' AND active=1 ORDER BY id DESC");
			$subservices = array();
			while($subservice =  mysqli_fetch_assoc($result)){
				$subservices[] = $subservice;
			}
			return $subservices;
		}

		function getSubservicesOfServiceNotAssignedToServiceCompanyClient($serviceCompanyClientId){
			$result = mysqli_query($this->connection,"SELECT subservices.id AS id, subservices.name AS name, subservices.description AS description, subservices.base_rate AS base_rate, subservices.service_id AS service_id FROM subservices, services, service_company, service_company_client WHERE service_company_client.service_company_id=service_company.id AND service_company.service_id=services.id AND subservices.service_id=services.id AND service_company_client.id='".$serviceCompanyClientId."' AND subservices.id NOT IN (SELECT subservices.id  FROM subservices, services, service_company, service_company_client, service_company_client_subservice WHERE service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client.service_company_id=service_company.id AND service_company.service_id=services.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client.id='".$serviceCompanyClientId."')");
			$subservices = array();
			while($subservice =  mysqli_fetch_assoc($result)){
				$subservices[] = $subservice;
			}
			return $subservices;
		}

		function setSubservice($id, $name, $baseRate, $description, $serviceId){
			$result = mysqli_query($this->connection, "UPDATE `subservices` SET `name`='".$name."',`base_rate`='".$baseRate."',`description`='".$description."',`service_id`='".$serviceId."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}


		function deleteSubserviceSnapById($subServiceSnapId){
			$result = mysqli_query($this->connection, "DELETE FROM `service_company_client_subservice` WHERE id='".$subServiceSnapId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// SELECT * FROM subservices WHERE subservices.id NOT IN (SELECT subservices.id  FROM subservices, services, service_company, service_company_client, service_company_client_subservice WHERE service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client.service_company_id=service_company.id AND service_company.service_id=services.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client.id=55)
		
		// SELECT subservices.id AS id, subservices.name AS name, subservices.base_rate AS base_rate FROM subservices, services WHERE services.id = subservices.service_id NOT IN (SELECT subservices.id  FROM subservices, services, service_company, service_company_client, service_company_client_subservice WHERE service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client.service_company_id=service_company.id AND service_company.service_id=services.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client.id=55)

		// select de los asignados actualmente: 
		// SELECT subservices.id  FROM subservices, services, service_company, service_company_client, service_company_client_subservice WHERE service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client.service_company_id=service_company.id AND service_company.service_id=services.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client.id=55
		
		// select de 
		// SELECT subservices.id AS id, subservices.name AS name, subservices.base_rate AS base_rate FROM subservices WHERE subservices.service_id = (SELECT subservices.service_id FROM subservices, services, service_company, service_company_client WHERE service_company_client.service_company_id=service_company.id AND service_company.service_id=services.id AND subservices.service_id=services.id AND service_company_client.id=55)
		
		
		function getContratos(){
			$result = mysqli_query($this->connection,"SELECT * FROM contratos");
			$clients = array();
			while($client =  mysqli_fetch_assoc($result)){
				$clients[] = $client;
			}
			return $clients;

		}
		// CRUD Font types
		function getFontTypes(){
			$result = mysqli_query($this->connection,"SELECT * FROM `font-types`");
			$fontTypes = array();
			while($fontType =  mysqli_fetch_assoc($result)){
				$fontTypes[] = $fontType;
			}
			return $fontTypes;
		}
		function getFontTypeById($id){
			$result = mysqli_query($this->connection,"SELECT * FROM `font-types` WHERE id='".$id."' ");
			$fontType = mysqli_fetch_assoc($result);
			if($result){
				return $fontType;
			}else{
				return false;
			}
		}

		// function setSubservices() {
		// 	$changes = "";
		// 	$ids = "";
		// 	foreach ($departments as $id => $data){
		// 		$changes.= "WHEN '".$id."' THEN '".$data["name"]."'";
		// 		$ids.= "'".$id."', ";
		// 	}
		// 	$ids = substr($ids, 0, -2);
		// 	$result = mysqli_query($this->connection, "UPDATE departments SET name = CASE id ".$changes." ELSE name END WHERE id IN(".$ids.")");
		// 	if($result){
		// 		return true;
		// 	}else{
		// 		return false;
		// 	}
		// }

		// CRUD ServiceCompany

		function getServiceCompaniesByServiceId($serviceId){
			$result = mysqli_query($this->connection,"SELECT service_company.id AS id, services.id AS service_id, services.name AS service_name, companies.id AS company_id, companies.name AS company_name, companies.phone AS company_phone, companies.web AS company_web, companies.image AS company_image, service_company.description, service_company.price FROM service_company, services, companies WHERE service_company.service_id='".$serviceId."' AND service_company.service_id = services.id AND service_company.company_id = companies.id");
			$serviceCompanies = array();
			while($serviceCompany =  mysqli_fetch_assoc($result)){
				$serviceCompanies[] = $serviceCompany;
			}
			return $serviceCompanies;
		}

		function getServiceCompaniesByCompanyId($companyId){
			$result = mysqli_query($this->connection,"SELECT service_company.id AS id, services.id AS service_id, services.name AS service_name, services.description AS service_description, companies.id AS company_id, companies.name AS company_name, companies.phone AS company_phone, companies.web AS company_web, companies.image AS company_image, service_company.description, service_company.price FROM service_company, services, companies WHERE service_company.company_id='".$companyId."' AND service_company.service_id = services.id AND service_company.company_id = companies.id");
			$serviceCompanies = array();
			while($serviceCompany =  mysqli_fetch_assoc($result)){
				$serviceCompanies[] = $serviceCompany;
			}
			return $serviceCompanies;
		}

		function getServiceCompaniesByServiceIdNotAssignedToClient($serviceId, $clientId){
			// $result = mysqli_query($this->connection,"SELECT c.id, c.name, c.image, c.cif, c.address, c.web, c.postal_code, c.constitution_date, c.phone, c.capital, c.city, c.country, c.commercial_register, c.social_object, c.observations, c.signup_date, c.active FROM companies c INNER JOIN service_company sc ON NOT sc.company_id=c.id AND sc.service_id='".$serviceId."'");
			$result = mysqli_query($this->connection,"SELECT sc.id, sc.description, sc.price, c.name AS company_name FROM service_company sc, services s, companies c WHERE sc.service_id=s.id AND sc.company_id=c.id AND sc.id NOT IN (SELECT scc.service_company_id FROM service_company_client scc WHERE scc.service_company_id=sc.id AND scc.client_id='".$clientId."') AND sc.service_id='".$serviceId."'");
			$companies = array();
			while($company =  mysqli_fetch_assoc($result)){
				$companies[] = $company;
			}
			return $companies;
		}

		function addServiceCompany($serviceId, $companyId){
			$result = mysqli_query($this->connection, "INSERT INTO `service_company`(`service_id`, `company_id`) VALUES ('".$serviceId."','".$companyId."')");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD EmployeeClient

		function getEmployeeClientsByEmployeeId($employeeId){
			$result = mysqli_query($this->connection,"SELECT employee_client.id AS id, employees.name AS employee_name, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, clients.image AS client_image ,employee_client.description FROM employee_client , employees, clients WHERE employee_client .employee_id='".$employeeId."' AND employee_client .employee_id = employees.id AND employee_client .client_id = clients.id");
			//   SELECT service_company.id AS id, services.name AS service_name, companies.id AS company_id, companies.name AS company_name, companies.web AS company_web, companies.image AS company_image, service_company.description, service_company.price FROM service_company, services, companies WHERE service_company.service_id='".$serviceId."' AND service_company.service_id = services.id AND service_company.company_id = companies.id
			$employeeClients = array();
			while($employeeClient =  mysqli_fetch_assoc($result)){
				$employeeClients[] = $employeeClient;
			}
			return $employeeClients;
		}

		function addEmployeeClient($employeeId, $clientId){
			$result = mysqli_query($this->connection, "INSERT INTO `employee_client`(`employee_id`, `client_id`) VALUES ('".$employeeId."','".$clientId."')");
			if($result){
				return true;
			}else{
				return false;
			}
		}


		// CRUD ServiceCompanyClient

		function getServiceCompanyClients(){
			// $result = mysqli_query($this->connection,"SELECT * FROM services WHERE active=1");
			// $result = mysqli_query($this->connection,"SELECT services.name AS service_name, companies.name AS company_name, clients.id AS client_id, clients.name AS client_name, clients.image AS client_image, clients.web AS client_web FROM `services`, companies, clients, service_company, service_company_client WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id ");
			$result = mysqli_query($this->connection,"SELECT service_company_client.id AS id, services.id AS service_id, services.name AS service_name, services.contract_template AS service_contract_template, services.budget_template AS service_budget_template, companies.id AS company_id, companies.name AS company_name, companies.cif AS company_cif, companies.image AS company_image, companies.address AS company_address, companies.commercial_register AS company_commercial_register, clients.id AS client_id, clients.name AS client_name, clients.cif AS client_cif, clients.address AS client_address, clients.web AS client_web, clients.image AS client_image FROM `services`, companies, clients, service_company, service_company_client WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id ");
			$serviceCompanyClients = array();
			while($serviceCompanyClient =  mysqli_fetch_assoc($result)){
				$serviceCompanyClients[] = $serviceCompanyClient;
			}
			return $serviceCompanyClients;
		}
		

		function getServiceCompanyClientsByServiceId($serviceId){
			$result = mysqli_query($this->connection,"SELECT service_company_client.id AS id, services.id AS service_id, services.name AS service_name, services.contract_template AS service_contract_template, services.budget_template AS service_budget_template, companies.name AS company_name, companies.image AS company_image, companies.address AS company_address, companies.commercial_register AS company_commercial_register, clients.id AS client_id, clients.name AS client_name, clients.address AS client_address, clients.web AS client_web, clients.image AS client_image FROM `services`, companies, clients, service_company, service_company_client WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company.service_id= '".$serviceId."'");
			$serviceCompanyClients = array();
			while($serviceCompanyClient =  mysqli_fetch_assoc($result)){
				$serviceCompanyClients[] = $serviceCompanyClient;
			}
			return $serviceCompanyClients;
		}

		function getServiceCompanyClientsByClientId($clientId){
			$result = mysqli_query($this->connection,"SELECT service_company_client.id, services.id AS service_id, services.name AS service_name, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web FROM `services`, companies, clients, service_company, service_company_client WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client.client_id= '".$clientId."'");
			$serviceCompanyClients = array();
			while($serviceCompanyClient =  mysqli_fetch_assoc($result)){
				$serviceCompanyClients[] = $serviceCompanyClient;
			}
			return $serviceCompanyClients;
		}

		function getServiceCompanyClientsByCompanyId($companyId){
			$result = mysqli_query($this->connection,"SELECT service_company_client.id, services.id AS service_id, services.name AS service_name, companies.name AS company_name, clients.id AS client_id, clients.name AS client_name, clients.image AS client_image, clients.web AS client_web, subservices.name AS subservice_name FROM services, subservices, companies, clients, service_company, service_company_client, service_company_client_subservice WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client.id=service_company_client_subservice.service_company_client_id AND service_company_client_subservice.subservice_id=subservices.id AND service_company.company_id= '".$companyId."' ORDER BY client_name");
			$serviceCompanyClients = array();
			while($serviceCompanyClient =  mysqli_fetch_assoc($result)){
				$serviceCompanyClients[] = $serviceCompanyClient;
			}
			return $serviceCompanyClients;

		}
		
		/*function getServiceCompanyClientsByCompanyId($companyId){
			$result = mysqli_query($this->connection,"SELECT service_company_client.id, services.id AS service_id, services.name AS service_name, companies.name AS company_name, clients.id AS client_id, clients.name AS client_name, clients.image AS client_image, clients.web AS client_web, subservices.name AS subservice_name FROM services, subservices, companies, clients, service_company, service_company_client, service_company_client_subservice WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client.id=service_company_client_subservice.service_company_client_id AND service_company_client_subservice.subservice_id=subservices.id AND service_company.company_id= '".$companyId."' ORDER BY client_name");
			$serviceCompanyClients = array();
			while($serviceCompanyClient =  mysqli_fetch_assoc($result)){
				$serviceCompanyClients[] = $serviceCompanyClient;
			}
			return $serviceCompanyClients;

		}*/

		//Clients by subservices
		function getServiceCompanyClientsBySubservicesId($subserviceId){
			$result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id , service_company_client.client_id, clients.name AS client_name , clients.image AS client_image,  companies.name AS company_name , companies.acronym AS company_acronym
			FROM `service_company_client_subservice` , service_company_client ,clients, companies, service_company
			WHERE  service_company_client_subservice.service_company_client_id=service_company_client.id AND 
			service_company_client_subservice.subservice_id='".$subserviceId."' AND service_company_client.client_id=clients.id AND service_company_client.service_company_id=service_company.id AND service_company.company_id=companies.id");
			$serviceCompanyClients = array();
			while($serviceCompanyClient =  mysqli_fetch_assoc($result)){
				$serviceCompanyClients[] = $serviceCompanyClient;
			}
			return $serviceCompanyClients;
		}

		function getServiceCompanyClientById($id){
			$result = mysqli_query($this->connection,"SELECT service_company_client.id AS id, services.id AS service_id, services.name AS service_name, companies.id AS company_id, companies.image AS company_image, companies.name AS company_name, companies.cif AS company_cif, clients.id AS client_id, clients.name AS client_name, clients.image AS client_image, clients.cif AS client_cif, clients.web AS client_web FROM `services`, companies, clients, service_company, service_company_client WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client.id= '".$id."'");
			$serviceCompanyClient = mysqli_fetch_assoc($result);
			if($result){
				return $serviceCompanyClient;
			}else{
				return false;
			}

		}

		function addServiceCompanyClient($serviceCompanyId, $clientId){
			$result = mysqli_query($this->connection, "INSERT INTO `service_company_client`(`service_company_id`, `client_id`) VALUES ('".$serviceCompanyId."','".$clientId."')");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD ServiceCompanyClientSubservices

		function getServiceCompanyClientSubservices(){
			// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id,  services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.description AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id");
			$result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id, services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.description  AS observations, service_company_client_subservice.ending_date AS ending_date, service_company_client_subservice.billing_date AS billing_date, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, service_company_client_subservice.snap_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id AND snap_date IS NULL");
			$serviceCompanyClientSubservices = array();
			while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
				$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			}
			return $serviceCompanyClientSubservices;
		}

		function getServiceCompanyClientSubservicesOfCurrentYearByCompanyId($companyId){
		// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id,  services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.description AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id");
		$result = mysqli_query($this->connection, "SELECT service_company_client_subservice.id, services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price*service_company_client_subservice.units AS price, service_company_client_subservice.units, service_company_client_subservice.description  AS observations, service_company_client_subservice.ending_date AS ending_date, service_company_client_subservice.starting_date AS starting_date, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, service_company_client_subservice.snap_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id AND ending_date IS NULL AND NOT (periodicity_id=1 AND (snap_date IS NULL OR NOT( YEAR(billing_date) = YEAR(CURDATE()) ) ) ) AND service_company.company_id='" . $companyId . "'");
			// $serviceCompanyClientSubservices = array();
			// while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
			// 	$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			// }
			// return $serviceCompanyClientSubservices;
			$serviceCompanyClientSubservices = array();
			while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
				$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			}
			return $serviceCompanyClientSubservices;
		}

		function getServiceCompanyClientSubservicesByCompanyId($companyId){
			// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id,  services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.description AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id");
			$result = mysqli_query($this->connection, "SELECT service_company_client_subservice.id, services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price*service_company_client_subservice.units AS price, service_company_client_subservice.units, service_company_client_subservice.description  AS observations, service_company_client_subservice.ending_date AS ending_date, service_company_client_subservice.starting_date AS starting_date, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, service_company_client_subservice.snap_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id AND ending_date IS NULL AND NOT (periodicity_id=1 AND snap_date IS NULL) AND service_company.company_id='" . $companyId . "'");
				// $serviceCompanyClientSubservices = array();
				// while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
				// 	$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
				// }
				// return $serviceCompanyClientSubservices;
				$serviceCompanyClientSubservices = array();
				while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
					$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
				}
				return $serviceCompanyClientSubservices;
			}

		function getServiceCompanyClientSubservicesByServiceId($serviceId){
			// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id,  services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.description AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id");
			$result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id, services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.description  AS observations, service_company_client_subservice.ending_date AS ending_date, service_company_client_subservice.billing_date AS billing_date, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, service_company_client_subservice.snap_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id AND snap_date IS NULL AND service_company.service_id='".$serviceId."'");
			// $serviceCompanyClientSubservices = array();
			// while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
			// 	$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			// }
			// return $serviceCompanyClientSubservices;
			$serviceCompanyClientSubservices = array();
			while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
				$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			}
			return $serviceCompanyClientSubservices;
		}

		function getServiceCompanyClientSubservicesByClientId($clientId){
			// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id,  services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.description AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id");
			$result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id, services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.description  AS observations, service_company_client_subservice.ending_date AS ending_date, service_company_client_subservice.billing_date AS billing_date, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, service_company_client_subservice.snap_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id AND snap_date IS NULL AND service_company_client.client_id='".$clientId."'");
			// $serviceCompanyClientSubservices = array();
			// while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
			// 	$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			// }
			// return $serviceCompanyClientSubservices;
			$serviceCompanyClientSubservices = array();
			while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
				$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			}
			return $serviceCompanyClientSubservices;
		}

		function deleteServiceCompanyClientSubservice($id){
			$result = mysqli_query($this->connection, "DELETE FROM `service_company_client_subservice` WHERE id='".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}


		//FACTURADO
		function getBillingCurrentYearTotalAmountByCompanyId($companyId){
			// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id,  services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.observations AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id");
			// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id,  services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.observations AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, service_company_client_subservice.snap_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id AND snap_date IS NULL");
			// $result = mysqli_query($this->connection,"SELECT  SUM(`service_company_client_subservice`.price * `service_company_client_subservice`.units * (1+`service_company_client_subservice`.bonus/100)) AS amount  FROM `service_company_client_subservice` , `service_company_client`, service_company WHERE  service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client.service_company_id = service_company.id  ");
		
			$result = mysqli_query($this->connection,"SELECT 
			IFNULL(
			 (
				SUM(
					ROUND(
						(
							SELECT  (
								CASE WHEN SUM(ROUND( price,2)) THEN SUM(ROUND( price,2))  ELSE 0 END
							)
							FROM billing, bills
							WHERE billing.bill_id=bills.id 
							AND service_company_client_subservice_id=service_company_client_subservice.id 
							AND  bills.operation_date >=concat(year(now()),'-01-01') AND  bills.operation_date <=concat(year(now()),'-12-31')    
						),2
					)
				)
			) ,0 )AS amount_billed
			FROM service_company, service_company_client, service_company_client_subservice WHERE service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client.service_company_id=service_company.id AND service_company.company_id='".$companyId."'");
			$serviceCompanyClientSubserviceTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $serviceCompanyClientSubserviceTotalAmount;
			}else{
				return false;
			}
		}

		// // Query de prueba pendiente de facturar
		// function queryTry($companyId){
		// 	$result = mysqli_query($this->connection,"SELECT (SUM(
		// 		IF(
		// 			service_company_client_subservice.periodicity_id = 1 
		// 			AND service_company_client_subservice.snap_date IS NOT NULL
		// 			AND YEAR(service_company_client_subservice.billing_date) = YEAR(CURDATE())
		// 			AND service_company_client_subservice.id NOT IN(
		// 				SELECT  billing.service_company_client_subservice_id 
		// 				FROM billing
		// 			), ROUND(service_company_client_subservice.price , 2 ), 0
		// 		)
		// 	) 
		// 	+ SUM(
		// 		IF(
		// 			service_company_client_subservice.periodicity_id = 2
		// 			, ROUND(
		// 				service_company_client_subservice.price / 12 * (
		// 					SELECT 
		// 						IF(
		// 							select
		// 							IF(year("2021-01-12") = year(curdate())
		// 							and month("2021-01-12") = month(curdate())
		// 							and "2022-01-12" <= curdate() + interval 30 day
		// 							, "MISMO MES"
		// 							, "NO")
		// 						)
		// 					FROM billing, bills WHERE billing.bill_id=bills.id AND billing.service_company_client_subservice_id=service_company_client_subservice.id
		// 					AND YEAR(bills.reference_date)=YEAR(CURDATE())

						
		// 	FROM service_company, service_company_client, service_company_client_subservice
		// 	WHERE service_company_client_subservice.service_company_client_id = service_company_client.id
		// 	AND service_company_client.service_company_id = service_company.id
		// 	AND service_company_client_subservice.ending_date IS NULL
		// 	AND service_company_client_subservice.withdrawal_date IS NULL
		// 	AND service_company.company_id = '".$companyId."'");
		// 	$BillingCurrentYearTotalAmount = mysqli_fetch_assoc($result);
		// 	if($result){
		// 		return $BillingCurrentYearTotalAmount;
		// 	}else{
		// 		return false;
		// 	}
		// }
		
		//PENDIENTE DE FACTURAR
		function getPendingBillingCurrentYearTotalAmountByCompanyId($companyId){
			$result = mysqli_query($this->connection,"SELECT 
			IFNULL(
			(SUM(
				IF(
					service_company_client_subservice.periodicity_id = 1 
					AND service_company_client_subservice.snap_date IS NOT NULL
					AND YEAR(service_company_client_subservice.billing_date) = YEAR(CURDATE())
					AND service_company_client_subservice.id NOT IN(
						SELECT  billing.service_company_client_subservice_id 
						FROM billing
					), ROUND(service_company_client_subservice.price*service_company_client_subservice.units , 2 ), 0
				)
			) 
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 2
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 12 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)
								)
						
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 3
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 4 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                       IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) )/3
								
							
						
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 4
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 2 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                          IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        )+ 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                      IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) )/6
							
						
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 5
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 1 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                       IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)) / 12
							
						
					, 2 )
					, 0
				)
			)),0)
			AS amount_to_bill
			FROM service_company, service_company_client, service_company_client_subservice
			WHERE service_company_client_subservice.service_company_client_id = service_company_client.id
			AND service_company_client.service_company_id = service_company.id		
			AND (service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE()))
			AND service_company.company_id = '".$companyId."'");
			
			
			$PendingBillingCurrentYearTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $PendingBillingCurrentYearTotalAmount;
			}else{
				return false;
			}
		}
		
		//CONTRATADO MENOS PUNTUAL NO EJECUTADO
		function getServiceCompanyClientSubservicesCurrentYearTotalAmountByCompanyId($companyId){	
			$result = mysqli_query($this->connection,"SELECT 
			IFNULL(
				SUM(
					ROUND(
						
						IF(
							service_company_client_subservice.periodicity_id=1
							,
							IF(
								(service_company_client_subservice.snap_date IS NULL)
								, service_company_client_subservice.price*service_company_client_subservice.units
								, 0
							)
							, service_company_client_subservice.price
						)
					, 2)
				)
			, 0)
			AS amount 
			FROM service_company, service_company_client, service_company_client_subservice
			WHERE service_company_client_subservice.service_company_client_id = service_company_client.id
			AND service_company_client.service_company_id = service_company.id
			AND service_company_client_subservice.ending_date IS NULL
			AND service_company_client_subservice.withdrawal_date IS NULL
			AND service_company.company_id = '".$companyId."'");
			$BillingCurrentYearTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $BillingCurrentYearTotalAmount;
			}else{
				return false;
			}
		}
		//RECURRENTE + EJECUTADO
		function getServiceCompanyClientSubservicesCurrentYearExecutedAmountByCompanyId($companyId){	
			$result = mysqli_query($this->connection,"SELECT
		IFNULL(
			 SUM(
				IF(
					service_company_client_subservice.periodicity_id = 1 
					AND service_company_client_subservice.snap_date IS NOT NULL
					AND YEAR(service_company_client_subservice.billing_date) = YEAR(CURDATE())
					, ROUND(service_company_client_subservice.price*service_company_client_subservice.units , 2), 0
				)
			)  
			+ (-- sumo lo facturado en el año hasta la fecha
					SELECT  (
						SUM(
							ROUND(
								(
									SELECT  (
										CASE WHEN SUM(price) THEN SUM(price)  ELSE 0 END
									)
									FROM billing, bills
									WHERE billing.bill_id=bills.id 
									AND billing.service_company_client_subservice_id = service_company_client_subservice.id
									AND  bills.issue_date >=concat(year(now()),'-01-01') AND  bills.issue_date <=concat(year(now()),'-12-31')    
								)
							,2)
						)
					)
				)
				+
				SUM(
				IF(
					service_company_client_subservice.periodicity_id = 2
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 12 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                      IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                           IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)
						)
					, 2)
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 3
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 4 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                           IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)
						)/3
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 4
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 2 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                         IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        )+ 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                       IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) 
						)/6
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 5
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 1 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) 
						)/12
					, 2 )
					, 0
				)
			)
			,0 )
			AS amount
			FROM service_company, service_company_client, service_company_client_subservice
			WHERE service_company_client_subservice.service_company_client_id = service_company_client.id
			AND service_company_client.service_company_id = service_company.id
			AND (service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE()))
			AND service_company.company_id = '".$companyId."'");
			

			
			$PendingBillingCurrentYearTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $PendingBillingCurrentYearTotalAmount;
			}else{
				return false;
			}
			
		}
	/////////////////////CLIENTE///////////////////
		//FACTURADO
		function getBillingCurrentYearTotalAmountByClientId($clientId){
		
			$result = mysqli_query($this->connection,"SELECT 
			IFNULL( (
				SUM(
					ROUND(
						(
							SELECT  (
								CASE WHEN SUM(ROUND( price,2)) THEN SUM(ROUND( price,2))  ELSE 0 END
							)
							FROM billing, bills
							WHERE billing.bill_id=bills.id 
							AND service_company_client_subservice_id=service_company_client_subservice.id 
							AND  bills.operation_date >=concat(year(now()),'-01-01') AND  bills.operation_date <=concat(year(now()),'-12-31')    
						),2
					)
				)
			),0 ) AS amount_billed
			FROM service_company, service_company_client, service_company_client_subservice WHERE service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client.service_company_id=service_company.id AND service_company_client.client_id='".$clientId."'");
			$serviceCompanyClientSubserviceTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $serviceCompanyClientSubserviceTotalAmount;
			}else{
				return false;
			}
		}

		
		//PENDIENTE DE FACTURAR
		function getPendingBillingCurrentYearTotalAmountByClientId($clientId){
			$result = mysqli_query($this->connection,"SELECT 
			IFNULL(
				(SUM(
				IF(
					service_company_client_subservice.periodicity_id = 1 
					AND service_company_client_subservice.snap_date IS NOT NULL
					AND YEAR(service_company_client_subservice.billing_date) = YEAR(CURDATE())
					AND service_company_client_subservice.id NOT IN(
						SELECT  billing.service_company_client_subservice_id 
						FROM billing
					), ROUND(service_company_client_subservice.price*service_company_client_subservice.units , 2 ), 0
				)
			) 
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 2
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 12 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)
								)
						
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 3
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 4 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                       IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) )/3
								
							
						
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 4
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 2 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                          IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        )+ 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                      IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) )/6
							
						
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 5
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 1 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                       IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)) / 12
							
						
					, 2 )
					, 0
				)
			)),0)
			AS amount_to_bill
			FROM service_company, service_company_client, service_company_client_subservice
			WHERE service_company_client_subservice.service_company_client_id = service_company_client.id
			AND service_company_client.service_company_id = service_company.id		
			AND (service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE()))
			AND service_company_client.client_id='".$clientId."'");
			
			
			$PendingBillingCurrentYearTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $PendingBillingCurrentYearTotalAmount;
			}else{
				return false;
			}
		}
		
		//CONTRATADO MENOS PUNTUAL NO EJECUTADO
		function getServiceCompanyClientSubservicesCurrentYearTotalAmountByClientId($clientId){	
			$result = mysqli_query($this->connection,"SELECT 
			IFNULL(
				SUM(
				ROUND(
					 
					IF(
						service_company_client_subservice.periodicity_id=1
						,
						IF(
							(service_company_client_subservice.snap_date IS NULL)
							, service_company_client_subservice.price*service_company_client_subservice.units
							, 0
						)
						, service_company_client_subservice.price
					)
				, 2)
			),0)
			AS amount 
			FROM service_company, service_company_client, service_company_client_subservice
			WHERE service_company_client_subservice.service_company_client_id = service_company_client.id
			AND service_company_client.service_company_id = service_company.id
			AND service_company_client_subservice.ending_date IS NULL
			AND service_company_client_subservice.withdrawal_date IS NULL
			AND service_company_client.client_id='".$clientId."'");
			$BillingCurrentYearTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $BillingCurrentYearTotalAmount;
			}else{
				return false;
			}
		}
		//RECURRENTE + EJECUTADO
		function getServiceCompanyClientSubservicesCurrentYearExecutedAmountByClientId($clientId){	
			$result = mysqli_query($this->connection,"SELECT 
			IFNULL(
			SUM(
				IF(
					service_company_client_subservice.periodicity_id = 1 
					AND service_company_client_subservice.snap_date IS NOT NULL
					AND YEAR(service_company_client_subservice.billing_date) = YEAR(CURDATE())
					, ROUND(service_company_client_subservice.price*service_company_client_subservice.units , 2), 0
				)
			)  
			+ (-- sumo lo facturado en el año hasta la fecha
					SELECT  (
						SUM(
							ROUND(
								(
									SELECT  (
										CASE WHEN SUM(price) THEN SUM(price)  ELSE 0 END
									)
									FROM billing, bills
									WHERE billing.bill_id=bills.id 
									AND billing.service_company_client_subservice_id = service_company_client_subservice.id
									AND  bills.issue_date >=concat(year(now()),'-01-01') AND  bills.issue_date <=concat(year(now()),'-12-31')    
								)
							,2)
						)
					)
				)
				+
				SUM(
				IF(
					service_company_client_subservice.periodicity_id = 2
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 12 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                      IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                           IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)
						)
					, 2)
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 3
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 4 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                           IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)
						)/3
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 4
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 2 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                         IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        )+ 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                       IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) 
						)/6
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 5
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 1 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) 
						)/12
					, 2 )
					, 0
				)
			),0 )
			AS amount
			FROM service_company, service_company_client, service_company_client_subservice
			WHERE service_company_client_subservice.service_company_client_id = service_company_client.id
			AND service_company_client.service_company_id = service_company.id
			AND (service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE()))
			AND service_company_client.client_id='".$clientId."'");
			

			
			$PendingBillingCurrentYearTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $PendingBillingCurrentYearTotalAmount;
			}else{
				return false;
			}
			
		}

	////////////////////FIN CLIENTE/////////////////
		

		//FACTURADO
		function getBillingCurrentYearTotalAmount(){
			// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id,  services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.observations AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id");
			// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id,  services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.observations AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, service_company_client_subservice.snap_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id AND snap_date IS NULL");
			// $result = mysqli_query($this->connection,"SELECT  SUM(`service_company_client_subservice`.price * `service_company_client_subservice`.units * (1+`service_company_client_subservice`.bonus/100)) AS amount  FROM `service_company_client_subservice` , `service_company_client`, service_company WHERE  service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client.service_company_id = service_company.id  ");
		
			$result = mysqli_query($this->connection,"SELECT  (
				SUM(
					ROUND(
						(
							SELECT  (
								CASE WHEN SUM(ROUND( price,2)) THEN SUM(ROUND( price,2))  ELSE 0 END
							)
							FROM billing, bills
							WHERE billing.bill_id=bills.id 
							AND service_company_client_subservice_id=service_company_client_subservice.id 
							AND  bills.operation_date >=concat(year(now()),'-01-01') AND  bills.operation_date <=concat(year(now()),'-12-31')    
						),2
					)
				)
			) AS amount_billed
			FROM service_company, service_company_client, service_company_client_subservice WHERE service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client.service_company_id=service_company.id");
			$serviceCompanyClientSubserviceTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $serviceCompanyClientSubserviceTotalAmount;
			}else{
				return false;
			}
		}

		// // Query de prueba pendiente de facturar
		// function queryTry($companyId){
		// 	$result = mysqli_query($this->connection,"SELECT (SUM(
		// 		IF(
		// 			service_company_client_subservice.periodicity_id = 1 
		// 			AND service_company_client_subservice.snap_date IS NOT NULL
		// 			AND YEAR(service_company_client_subservice.billing_date) = YEAR(CURDATE())
		// 			AND service_company_client_subservice.id NOT IN(
		// 				SELECT  billing.service_company_client_subservice_id 
		// 				FROM billing
		// 			), ROUND(service_company_client_subservice.price , 2 ), 0
		// 		)
		// 	) 
		// 	+ SUM(
		// 		IF(
		// 			service_company_client_subservice.periodicity_id = 2
		// 			, ROUND(
		// 				service_company_client_subservice.price / 12 * (
		// 					SELECT 
		// 						IF(
		// 							select
		// 							IF(year("2021-01-12") = year(curdate())
		// 							and month("2021-01-12") = month(curdate())
		// 							and "2022-01-12" <= curdate() + interval 30 day
		// 							, "MISMO MES"
		// 							, "NO")
		// 						)
		// 					FROM billing, bills WHERE billing.bill_id=bills.id AND billing.service_company_client_subservice_id=service_company_client_subservice.id
		// 					AND YEAR(bills.reference_date)=YEAR(CURDATE())

						
		// 	FROM service_company, service_company_client, service_company_client_subservice
		// 	WHERE service_company_client_subservice.service_company_client_id = service_company_client.id
		// 	AND service_company_client.service_company_id = service_company.id
		// 	AND service_company_client_subservice.ending_date IS NULL
		// 	AND service_company_client_subservice.withdrawal_date IS NULL
		// 	AND service_company.company_id = '".$companyId."'");
		// 	$BillingCurrentYearTotalAmount = mysqli_fetch_assoc($result);
		// 	if($result){
		// 		return $BillingCurrentYearTotalAmount;
		// 	}else{
		// 		return false;
		// 	}
		// }
		
		//PENDIENTE DE FACTURAR
		function getPendingBillingCurrentYearTotalAmount(){
			$result = mysqli_query($this->connection,"SELECT (SUM(
				IF(
					service_company_client_subservice.periodicity_id = 1 
					AND service_company_client_subservice.snap_date IS NOT NULL
					AND YEAR(service_company_client_subservice.billing_date) = YEAR(CURDATE())
					AND service_company_client_subservice.id NOT IN(
						SELECT  billing.service_company_client_subservice_id 
						FROM billing
					), ROUND(service_company_client_subservice.price*service_company_client_subservice.units , 2 ), 0
				)
			) 
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 2
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 12 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)
								)
						
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 3
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 4 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                       IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) )/3
								
							
						
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 4
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 2 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                          IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        )+ 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                      IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) )/6
							
						
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 5
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 1 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                       IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)) / 12
							
						
					, 2 )
					, 0
				)
			))
			AS amount_to_bill
			FROM service_company, service_company_client, service_company_client_subservice
			WHERE service_company_client_subservice.service_company_client_id = service_company_client.id
			AND service_company_client.service_company_id = service_company.id		
			AND (service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE()))
			");
			
			
			$PendingBillingCurrentYearTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $PendingBillingCurrentYearTotalAmount;
			}else{
				return false;
			}
		}
		
		//CONTRATADO MENOS PUNTUAL NO EJECUTADO
		function getServiceCompanyClientSubservicesCurrentYearTotalAmount(){	
			$result = mysqli_query($this->connection,"SELECT 
			
				SUM(
					ROUND(
						
						IF(
							service_company_client_subservice.periodicity_id=1
							,
							IF(
								(service_company_client_subservice.snap_date IS NULL)
								, service_company_client_subservice.price*service_company_client_subservice.units
								, 0
							)
							, service_company_client_subservice.price
						)
					, 2)
				)
			AS amount 
			FROM service_company, service_company_client, service_company_client_subservice
			WHERE service_company_client_subservice.service_company_client_id = service_company_client.id
			AND service_company_client.service_company_id = service_company.id
			AND service_company_client_subservice.ending_date IS NULL
			AND service_company_client_subservice.withdrawal_date IS NULL");
			$BillingCurrentYearTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $BillingCurrentYearTotalAmount;
			}else{
				return false;
			}
		}
		//RECURRENTE + EJECUTADO
		function getServiceCompanyClientSubservicesCurrentYearExecutedAmount(){	
			$result = mysqli_query($this->connection,"SELECT SUM(
				IF(
					service_company_client_subservice.periodicity_id = 1 
					AND service_company_client_subservice.snap_date IS NOT NULL
					AND YEAR(service_company_client_subservice.billing_date) = YEAR(CURDATE())
					, ROUND(service_company_client_subservice.price*service_company_client_subservice.units , 2), 0
				)
			)  
			+ (-- sumo lo facturado en el año hasta la fecha
					SELECT  (
						SUM(
							ROUND(
								(
									SELECT  (
										CASE WHEN SUM(price) THEN SUM(price)  ELSE 0 END
									)
									FROM billing, bills
									WHERE billing.bill_id=bills.id 
									AND billing.service_company_client_subservice_id = service_company_client_subservice.id
									AND  bills.issue_date >=concat(year(now()),'-01-01') AND  bills.issue_date <=concat(year(now()),'-12-31')    
								)
							,2)
						)
					)
				)
				+
				SUM(
				IF(
					service_company_client_subservice.periodicity_id = 2
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 12 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                      IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                           IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)
						)
					, 2)
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 3
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 4 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                           IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								)
						)/3
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 4
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 2 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                         IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        )+ 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                       IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) 
						)/6
					, 2 )
					, 0
				)
			)
			+ SUM(
				IF(
					service_company_client_subservice.periodicity_id = 5
					, ROUND(
						service_company_client_subservice.price * service_company_client_subservice.units / 1 * (
							SELECT 
								TIMESTAMPDIFF(MONTH, 
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
									, IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date
                                    )
								)
								+ DATEDIFF(
									IF(
                                        service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                        , CONCAT(YEAR(CURDATE()),'-12-31')
                                        , service_company_client_subservice.ending_date),
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
									MONTH
								) /
								DATEDIFF(
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(
                                        MONTH, 
                                            IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                            , IF(service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                                , CONCAT(YEAR(CURDATE()),'-12-31')
                                                , service_company_client_subservice.ending_date)
                                        ) + 1
									MONTH,
									IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                    + INTERVAL
									TIMESTAMPDIFF(MONTH, 
                                        IF(
									YEAR(IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id  AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE()) AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date))<YEAR(CURDATE())
									,CONCAT(YEAR(CURDATE())-1,'-12-31'), 
									IF((SELECT  COUNT( * )FROM billing, bills WHERE billing.bill_id = bills.id AND bills.issue_date IS NOT NULL AND YEAR(bills.issue_date)=YEAR(CURDATE())  AND billing.service_company_client_subservice_id = service_company_client_subservice.id)>0, CONCAT(CONCAT(YEAR(NOW()),'-'),MONTH((SELECT bills.reference_date FROM billing, bills WHERE billing.bill_id=bills.id  AND  bills.reference_date >=concat(year(now()),'-01-01') AND  bills.reference_date <=concat(year(now()),'-12-31') AND service_company_client_id = service_company_client_subservice.service_company_client_id and billing.periodicity_id =  service_company_client_subservice.periodicity_id ORDER BY  bills.id DESC LIMIT 1)) + 1,'-01'),service_company_client_subservice.starting_date) - INTERVAL 1 DAY)
                                        , IF(
                                            service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE())
                                            , CONCAT(YEAR(CURDATE()),'-12-31')
                                            , service_company_client_subservice.ending_date)
                                        )
                                    MONTH
								) 
						)/12
					, 2 )
					, 0
				)
			)
			AS amount
			FROM service_company, service_company_client, service_company_client_subservice
			WHERE service_company_client_subservice.service_company_client_id = service_company_client.id
			AND service_company_client.service_company_id = service_company.id
			AND (service_company_client_subservice.ending_date IS NULL OR YEAR(service_company_client_subservice.ending_date)>YEAR(CURDATE()))");
			

			
			$PendingBillingCurrentYearTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $PendingBillingCurrentYearTotalAmount;
			}else{
				return false;
			}
			
		}
		
		function getServiceCompanyClientSubservicesCurrentYearTotalAmountByServiceId($serviceId){
			// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id,  services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.observations AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id");
			// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id,  services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units, service_company_client_subservice.observations AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, service_company_client_subservice.billing_date, service_company_client_subservice.snap_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id AND snap_date IS NULL");
			// $result = mysqli_query($this->connection,"SELECT  SUM(`service_company_client_subservice`.price * `service_company_client_subservice`.units * (1+`service_company_client_subservice`.bonus/100)) AS amount  FROM `service_company_client_subservice` , `service_company_client`, service_company WHERE  service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client.service_company_id = service_company.id  ");
			$result = mysqli_query($this->connection,"SELECT  (
				SUM(
					ROUND(
						(
							SELECT  (
								CASE WHEN SUM(ROUND( price,2)) THEN SUM(ROUND( price,2))  ELSE 0 END
							)
							FROM billing, bills
							WHERE billing.bill_id=bills.id 
							AND service_company_client_subservice_id=service_company_client_subservice.id 
							AND  ADDDATE(LAST_DAY(SUBDATE(bills.operation_date, INTERVAL 1 MONTH)), 1)>=concat(year(now()),'-01-01')    
						),2
					)
				)
			) AS amount_billed
			FROM services, companies, clients, subservices, periodicities, service_company, service_company_client, service_company_client_subservice
			LEFT JOIN employees m
			ON service_company_client_subservice.main_employee_id = m.id
			LEFT JOIN employees se
			ON service_company_client_subservice.secondary_employee_id = se.id
			WHERE services.id=service_company.service_id
			AND companies.id=service_company.company_id
			AND clients.id=service_company_client.client_id
			AND service_company_client.service_company_id=service_company.id
			AND service_company_client_subservice.service_company_client_id=service_company_client.id
			AND service_company_client_subservice.subservice_id = subservices.id
			AND service_company_client_subservice.periodicity_id=periodicities.id
			AND snap_date IS NULL
			AND service_company.company_id='".$serviceId."'");
			$serviceCompanyClientSubserviceTotalAmount = mysqli_fetch_assoc($result);
			if($result){
				return $serviceCompanyClientSubserviceTotalAmount;
			}else{
				return false;
			}
		}

		function getServiceCompanyClientSubservicesHired(){
			// $result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id, service_company_client_subservice.service_company_client_id, service_company_client_subservice.subservice_id, service_company_client_subservice.price, service_company_client_subservice.units, service_company_client_subservice.`iva-type_id`, service_company_client_subservice.description, service_company_client_subservice.periodicity_id, service_company_client_subservice.billing_date, service_company_client_subservice.main_employee_id, service_company_client_subservice.secondary_employee_id, `iva-types`.percentage AS percentage FROM `service_company_client`, `service_company_client_subservice`, `contracts`, ``iva-types`` WHERE contracts.service_company_client_id = service_company_client.id AND service_company_client_subservice.service_company_client_id = service_company_client.id AND service_company_client_subservice.`iva-type_id` = `iva-types`.id AND contracts.signed_both_parts_date IS NOT NULL AND contracts.withdrawal_date IS NULL");
			$result = mysqli_query($this->connection,"SELECT 
			service_company_client_subservice.id, service_company_client_subservice.service_company_client_id, 
			service_company_client_subservice.subservice_id, service_company_client_subservice.price, 
			service_company_client_subservice.units, service_company_client_subservice.`iva-type_id`, 
			service_company_client_subservice.description, service_company_client_subservice.periodicity_id, 
			service_company_client_subservice.billing_date, service_company_client_subservice.main_employee_id, 
			service_company_client_subservice.secondary_employee_id, `iva-types`.percentage AS percentage 
			FROM `service_company_client`, `service_company_client_subservice`, `contracts`, `iva-types` 
			WHERE contracts.service_company_client_id = service_company_client.id 
			AND service_company_client_subservice.service_company_client_id = service_company_client.id 
			AND service_company_client_subservice.`iva-type_id` = `iva-types`.id 
			AND contracts.signed_both_parts_date 
			IS NOT NULL AND contracts.withdrawal_date IS NULL");

			$serviceCompanyClientSubservices = array();
			while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
				$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			}
			return $serviceCompanyClientSubservices;
		}

		function getServiceCompanyClientSubserviceById($id){
			$result = mysqli_query($this->connection,"SELECT service_company_client_subservice.id, service_company_client_subservice.name, service_company_client_subservice.price, service_company_client_subservice.bonus, service_company_client_subservice.units, service_company_client_subservice.`iva-type_id`, service_company_client_subservice.description, service_company_client_subservice.periodicity_id, service_company_client_subservice.starting_date, service_company_client_subservice.ending_date, service_company_client_subservice.billing_date, service_company_client_subservice.snap_date, service_company_client_subservice.signup_date, service_company_client_subservice.withdrawal_date, services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, periodicities.name AS periodicity_name, `iva-types`.id AS `iva-type_id`, `iva-types`.percentage AS percentage,  m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name, 
				CASE WHEN (SELECT COUNT(*) FROM billing b WHERE b.service_company_client_subservice_id=service_company_client_subservice.id) THEN 1
				ELSE 0 END 
				AS billed
				FROM `services`, companies, clients, subservices, periodicities, `iva-types`, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id AND service_company_client_subservice.`iva-type_id`=`iva-types`.id 
				AND service_company_client_subservice.id='".$id."'"
			);
			$fontType = mysqli_fetch_assoc($result);
			if($result){
				return $fontType;
			}else{
				return false;
			}
		}
		
		function getServiceCompanyClientSubservicesByServiceCompanyClientId($serviceCompanyClientId){
			// $result = mysqli_query($this->connection, "SELECT service_company_client_subservice.id, service_company_client_subservice.name, service_company_client_subservice.price, service_company_client_subservice.bonus, service_company_client_subservice.units, service_company_client_subservice.`iva-type_id`, service_company_client_subservice.description, service_company_client_subservice.periodicity_id, service_company_client_subservice.starting_date, service_company_client_subservice.ending_date, service_company_client_subservice.billing_date, service_company_client_subservice.snap_date, service_company_client_subservice.signup_date, service_company_client_subservice.withdrawal_date, services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units As units, service_company_client_subservice.bonus AS bonus, service_company_client_subservice.description AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, `iva-types`.id AS `iva-type_id`, `iva-types`.percentage AS percentage, service_company_client_subservice.billing_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, `iva-types`, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id AND service_company_client_subservice.`iva-type_id`=`iva-types`.id AND service_company_client_subservice.service_company_client_id= '".$serviceCompanyClientId."'");
			$result = mysqli_query($this->connection, "SELECT service_company_client_subservice.id, service_company_client_subservice.name,
				service_company_client_subservice.price, service_company_client_subservice.bonus,
				service_company_client_subservice.units, service_company_client_subservice.`iva-type_id`,
				service_company_client_subservice.description, service_company_client_subservice.periodicity_id,
				service_company_client_subservice.starting_date, service_company_client_subservice.ending_date,
				service_company_client_subservice.billing_date, service_company_client_subservice.snap_date,
				service_company_client_subservice.signup_date, service_company_client_subservice.withdrawal_date, services.id AS
				service_id, services.name AS service_name, companies.id AS company_id, companies.name AS company_name, companies.image
				AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS
				subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate,
				service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price,
				service_company_client_subservice.units As units, service_company_client_subservice.bonus AS bonus,
				service_company_client_subservice.description AS observations, service_company_client_subservice.hired AS hired, 
				service_company_client_subservice.budget_date AS budget_date, service_company_client_subservice.periodicity_id,
				periodicities.name AS periodicity_name, `iva-types`.id AS `iva-type_id`, `iva-types`.percentage AS `iva-type_percentage`, (
					CASE WHEN (
						SELECT count(*) 
						FROM `accounting-accounts` 
						WHERE `accounting-accounts`.company_id = companies.id and `accounting-accounts`.`subservice_id` = subservices.id
					) 
					THEN (
						SELECT number 
						FROM `accounting-accounts` 
						WHERE `accounting-accounts`.company_id = companies.id and `accounting-accounts`.`subservice_id` = subservices.id 
					)
					ELSE null 
					END
				) AS `accounting-account_number`, 
				CASE WHEN (SELECT COUNT(*) FROM billing b WHERE b.service_company_client_subservice_id=service_company_client_subservice.id) THEN 1 ELSE 0 END
				AS billed,
				service_company_client_subservice.billing_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS
				secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices,
				periodicities, `iva-types`, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m
				ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON
				service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND
				companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND
				service_company_client.service_company_id=service_company.id AND
				service_company_client_subservice.service_company_client_id=service_company_client.id AND
				service_company_client_subservice.subservice_id = subservices.id AND
				service_company_client_subservice.periodicity_id=periodicities.id AND service_company_client_subservice.`iva-type_id`=`iva-types`.id
				AND service_company_client_subservice.`snap_date` IS NULL AND
				service_company_client_subservice.service_company_client_id= '".$serviceCompanyClientId."'"
			);
			$serviceCompanyClientSubservices = array();
			while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
				$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			}
			return $serviceCompanyClientSubservices;
		}
		function getServiceCompanyClientSubservicesSnapsByServiceCompanyClientId($serviceCompanyClientId){
			// $result = mysqli_query($this->connection, "SELECT service_company_client_subservice.id, service_company_client_subservice.name, service_company_client_subservice.price, service_company_client_subservice.bonus, service_company_client_subservice.units, service_company_client_subservice.`iva-type_id`, service_company_client_subservice.description, service_company_client_subservice.periodicity_id, service_company_client_subservice.starting_date, service_company_client_subservice.ending_date, service_company_client_subservice.billing_date, service_company_client_subservice.snap_date, service_company_client_subservice.signup_date, service_company_client_subservice.withdrawal_date, services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price, service_company_client_subservice.units As units, service_company_client_subservice.bonus AS bonus, service_company_client_subservice.description AS observations, service_company_client_subservice.periodicity_id, periodicities.name AS periodicity_name, `iva-types`.id AS `iva-type_id`, `iva-types`.percentage AS percentage, service_company_client_subservice.billing_date, m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name FROM `services`, companies, clients, subservices, periodicities, `iva-types`, service_company, service_company_client, service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id AND service_company_client_subservice.`iva-type_id`=`iva-types`.id AND service_company_client_subservice.service_company_client_id= '".$serviceCompanyClientId."'");
			$result = mysqli_query($this->connection, "SELECT service_company_client_subservice.id, service_company_client_subservice.name, 
				service_company_client_subservice.price, service_company_client_subservice.bonus, service_company_client_subservice.units,
				service_company_client_subservice.`iva-type_id`, service_company_client_subservice.description, service_company_client_subservice.periodicity_id, 
				service_company_client_subservice.starting_date, service_company_client_subservice.ending_date, service_company_client_subservice.billing_date,
				service_company_client_subservice.snap_date, service_company_client_subservice.signup_date, service_company_client_subservice.withdrawal_date,
				services.id AS service_id, services.name AS service_name, companies.id  AS company_id, companies.name  AS company_name, 
				companies.image AS company_image, clients.id AS client_id, clients.name AS client_name, clients.web AS client_web, 
				subservices.id AS subservice_id, subservices.name AS subservice_name, subservices.base_rate AS subservice_base_rate, 
				service_company_client.id AS service_company_client_id, service_company_client_subservice.price AS price,
				service_company_client_subservice.units As units, service_company_client_subservice.bonus AS bonus,
				service_company_client_subservice.description AS observations, service_company_client_subservice.periodicity_id,
				periodicities.name AS periodicity_name, `iva-types`.id AS `iva-type_id`, `iva-types`.percentage AS `iva-type_percentage`, service_company_client_subservice.billing_date as billing_date,
				CASE WHEN (SELECT COUNT(*) FROM billing b WHERE b.service_company_client_subservice_id=service_company_client_subservice.id) THEN 1
						ELSE NULL END 
				AS billed ,
				m.id AS main_employee_id, m.name AS main_employee_name, se.id AS secondary_employee_id ,se.name AS secondary_employee_name
				FROM `services`, companies, clients, subservices, periodicities, `iva-types`, service_company, service_company_client,
				service_company_client_subservice LEFT JOIN employees m ON service_company_client_subservice.main_employee_id = m.id
				LEFT JOIN employees se ON service_company_client_subservice.secondary_employee_id = se.id WHERE services.id=service_company.service_id
				AND companies.id=service_company.company_id AND clients.id=service_company_client.client_id AND 
				service_company_client.service_company_id=service_company.id AND service_company_client_subservice.service_company_client_id=service_company_client.id
				AND service_company_client_subservice.subservice_id = subservices.id AND service_company_client_subservice.periodicity_id=periodicities.id
				AND service_company_client_subservice.`iva-type_id`=`iva-types`.id AND service_company_client_subservice.`snap_date` 
				IS NOT NULL AND service_company_client_subservice.service_company_client_id= '".$serviceCompanyClientId."'");
			$serviceCompanyClientSubservices = array();
			while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
				$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			}
			return $serviceCompanyClientSubservices;
		}

		function getServiceCompanyClientSubservicesOfBill($billId){
			// UPDATE bills b SET `name`='nombrePrueba',`file_extension`='fileExtensionPrueba',`bill_number`=(SELECT b2.bill_number FROM bills b2 WHERE b2.`id`= (SELECT MAX(b3.id) FROM bills b3)+1) WHERE b.`id`='420'
			$result = mysqli_query($this->connection, "SELECT service_company_client_subservice.`id`
				,service_company_client_subservice.`service_company_client_id`
				,service_company_client_subservice.`subservice_id`
				,service_company_client_subservice.`name`
				,`iva-types`.`percentage` AS `iva-type_percentage`
				,service_company_client_subservice.`units`
				,service_company_client_subservice.`bonus`
				,service_company_client_subservice.`iva-type_id`
				,service_company_client_subservice.`description`
				,service_company_client_subservice.`periodicity_id`
				,service_company_client_subservice.`billing_date`
				,service_company_client_subservice.`main_employee_id`
				,service_company_client_subservice.`secondary_employee_id`
				,subservices.name AS subservice_name
				,
				(
					CASE WHEN service_company_client_subservice.periodicity_id = 1 AND service_company_client_subservice.snap_date IS NOT NULL THEN service_company_client_subservice.price ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 2
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/12 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 3
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)<=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR), 1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL 
											 1 DAY  END, 
								service_company_client_subservice.starting_date) +1) * service_company_client_subservice.price/
										(DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											  THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR),1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								  ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL  1 DAY  END , 
											  MAKEDATE(YEAR(service_company_client_subservice.starting_date), 1) + INTERVAL QUARTER(service_company_client_subservice.starting_date) QUARTER - INTERVAL 1 QUARTER )+1)/4
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/4 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 4 
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/2 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 5
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price END
					) ELSE 0 END
				) AS price
				FROM service_company_client_subservice, service_company_client, subservices, bills, `iva-types`
				WHERE subservices.id=service_company_client_subservice.subservice_id
				AND service_company_client_subservice.service_company_client_id=service_company_client.id
				AND bills.service_company_client_id=service_company_client.id
				AND service_company_client_subservice.service_company_client_id=bills.service_company_client_id
				AND service_company_client_subservice.`iva-type_id`=`iva-types`.id AND service_company_client_subservice.hired=1
				AND 
				(
					(
						service_company_client_subservice.periodicity_id=1 
						AND service_company_client_subservice.snap_date IS NOT NULL
						AND (
							MONTH(service_company_client_subservice.billing_date)=MONTH(bills.reference_date) 
							AND YEAR(service_company_client_subservice.billing_date)=YEAR(bills.reference_date)
						)
						AND service_company_client_subservice.id NOT IN (SELECT billing.service_company_client_subservice_id FROM billing, bills WHERE billing.bill_id = bills.id AND bills.amending_date IS NULL AND bills.bill_id IS NULL)
					)
					OR (
						service_company_client_subservice.periodicity_id=2
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND billing.bill_id = b2.id
							AND b2.amending_date IS NULL
							AND b2.bill_id IS NULL
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
					OR (
						service_company_client_subservice.periodicity_id=3
						AND (
							MONTH(bills.reference_date)=03 OR MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=09 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND billing.bill_id = b2.id
							AND b2.amending_date IS NULL
							AND b2.bill_id IS NULL
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=4 
						AND (
							MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND billing.bill_id = b2.id
							AND b2.amending_date IS NULL
							AND b2.bill_id IS NULL
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=5 
						AND (
							MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND billing.bill_id = b2.id
							AND b2.amending_date IS NULL
							AND b2.bill_id IS NULL
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
				)
				AND bills.id='".$billId."'"
			);
			$serviceCompanyClientSubservices = array();
			while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
				$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			}
			return $serviceCompanyClientSubservices;
		}

		function getServiceCompanyClientSubservicesByEmployeeId($employeeId){
			$result = mysqli_query($this->connection, "SELECT  service_company_client_subservice.`id`
				,service_company_client_subservice.`service_company_client_id`
				,service_company_client_subservice.`subservice_id`
				,service_company_client_subservice.`name`
				,services.name            AS service_name
				,clients.name             AS client_name
				,companies.name           AS company_name
				,`iva-types`.`percentage` AS `iva-type_percentage`
				,service_company_client_subservice.`units`
				,service_company_client_subservice.`iva-type_id`
				,service_company_client_subservice.`description`
				,service_company_client_subservice.`periodicity_id`
				,service_company_client_subservice.`billing_date`
				,service_company_client_subservice.`main_employee_id`
				,service_company_client_subservice.`secondary_employee_id`
				,subservices.name         AS subservice_name
			FROM service_company_client_subservice, service_company_client, service_company, services, companies, clients, subservices, `iva-types`
			WHERE subservices.id=service_company_client_subservice.subservice_id
			AND service_company_client_subservice.service_company_client_id=service_company_client.id
			AND service_company_client.service_company_id=service_company.id
			AND service_company_client.client_id=clients.id
			AND service_company.company_id=companies.id
			AND service_company.service_id=services.id
			AND service_company_client_subservice.`iva-type_id`=`iva-types`.id
			AND service_company_client_subservice.snap_date IS NULL
			AND (service_company_client_subservice.main_employee_id='".$employeeId."' OR service_company_client_subservice.secondary_employee_id='".$employeeId."');"
			);
			$serviceCompanyClientSubservices = array();
			while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
				$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			}
			return $serviceCompanyClientSubservices;
		}


		function getServiceCompanyClientSubserviceAmountSumByServiceCompanyClientId($serviceCompanyClientId){
			$result = mysqli_query($this->connection, "SELECT SUM(price) AS result  FROM `service_company_client_subservice` WHERE `service_company_client_subservice`.`service_company_client_id`='".$serviceCompanyClientId."'");
			$serviceCompanyClientSubserviceAmountSum = mysqli_fetch_assoc($result);
			if($result){
				return $serviceCompanyClientSubserviceAmountSum;
			}else{
				return false;
			}
		}

		function addServiceCompanyClientSubservice($serviceCompanyClientId, $subserviceId, $name, $price, $bonus, $units, $ivaTypeId, $description, $periodicityId, $startingDate, $endingDate, $billingDate, $mainEmployeeId, $secondaryEmployeeId, $hired, $budgetDate){
			$signupDate = date('Y-m-d H:i:s');

			if($startingDate == null){
				$startingDate="NULL";
			} else {
				$startingDate="'".$startingDate."'";
			}
			if($endingDate == null){
				$endingDate="NULL";
			} else {
				$endingDate="'".$endingDate."'";
			}
			if($billingDate == null){
				$billingDate="NULL";
			} else {
				$billingDate="'".$billingDate."'";
			}
			if($signupDate == null){
				$signupDate="NULL";
			} else {
				$signupDate="'".$signupDate."'";
			}
			if($mainEmployeeId == null){
				$mainEmployeeId="null";
			}
			if($secondaryEmployeeId == null){
				$secondaryEmployeeId="null";
			}
			if($budgetDate == null){
				$budgetDate="NULL";
			} else {
				$budgetDate="'".$budgetDate."'";
			}
			$result = mysqli_query($this->connection, "INSERT INTO `service_company_client_subservice` (`service_company_client_id`, `subservice_id`, `name`, `price`, `bonus`, `units`, `iva-type_id`, `description`, `periodicity_id`, `starting_date`, `ending_date`, `billing_date`, `snap_date`, `signup_date`, `withdrawal_date`, `main_employee_id`, `secondary_employee_id`, `hired`, `budget_date` ) VALUES ('".$serviceCompanyClientId."','".$subserviceId."','".$name."','".$price."','".$bonus."','".$units."','".$ivaTypeId."','".$description."','".$periodicityId."',".$startingDate.",".$endingDate.",".$billingDate.",null,".$signupDate.",null,".$mainEmployeeId.",".$secondaryEmployeeId.", ".$hired.", ".$budgetDate.")");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function addServiceCompanyClientSubservicePunctualSnap($serviceCompanyClientId, $subserviceId, $name, $price, $bonus, $units, $ivaTypeId, $description, $periodicityId, $startingDate, $endingDate, $billingDate, $signupDate, $withdrawalDate, $mainEmployeeId, $secondaryEmployeeId, $hired, $budgetDate){
			$snapDate = date('Y-m-d H:i:s');

			if($startingDate == null){
				$startingDate="NULL";
			} else {
				$startingDate="'".$startingDate."'";
			}
			if($endingDate == null){
				$endingDate="NULL";
			} else {
				$endingDate="'".$endingDate."'";
			}
			if($billingDate == null){
				$billingDate="NULL";
			} else {
				$billingDate="'".$billingDate."'";
			}
			if($snapDate == null){
				$snapDate="NULL";
			} else {
				$snapDate="'".$snapDate."'";
			}
			if($signupDate == null){
				$signupDate="NULL";
			} else {
				$signupDate="'".$signupDate."'";
			}
			if($withdrawalDate == null){
				$withdrawalDate="NULL";
			} else {
				$withdrawalDate="'".$withdrawalDate."'";
			}
			if($mainEmployeeId == null){
				$mainEmployeeId="null";
			}
			if($secondaryEmployeeId == null){
				$secondaryEmployeeId="null";
			}
			if($budgetDate == null){
				$budgetDate="NULL";
			} else {
				$budgetDate="'".$budgetDate."'";
			}

			$result = mysqli_query($this->connection, "INSERT INTO `service_company_client_subservice` (`service_company_client_id`, `subservice_id`, `name`, `price`, `bonus`, `units`, `iva-type_id`, `description`, `periodicity_id`, `starting_date`, `ending_date`, `billing_date`, `snap_date`, `signup_date`, `withdrawal_date`, `main_employee_id`, `secondary_employee_id`,  `hired`, `budget_date`) VALUES ('".$serviceCompanyClientId."','".$subserviceId."','".$name."','".$price."','".$bonus."','".$units."','".$ivaTypeId."','".$description."','".$periodicityId."',".$startingDate.",".$endingDate.",".$billingDate.",".$snapDate.",".$signupDate.",".$withdrawalDate.",".$mainEmployeeId.",".$secondaryEmployeeId.", ".$hired.", ".$budgetDate.")");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function setServiceCompanyClientSubservice($id, $name, $price, $bonus, $units, $ivaTypeId, $description, $periodicityId, $startingDate, $endingDate, $billingDate, $mainEmployeeId, $secondaryEmployeeId, $hired, $budgetDate){
			if($startingDate == null){
				$startingDate="NULL";
			} else {
				$startingDate="'".$startingDate."'";
			}
			if($endingDate == null){
				$endingDate="NULL";
			} else {
				$endingDate="'".$endingDate."'";
			}
			if($billingDate == null){
				$billingDate="NULL";
			} else {
				$billingDate="'".$billingDate."'";
			}
			if($budgetDate == null){
				$budgetDate="NULL";
			} else {
				$budgetDate="'".$budgetDate."'";
			}
			if($mainEmployeeId == null){
				$mainEmployeeId="null";
			}
			if($secondaryEmployeeId == null){
				$secondaryEmployeeId="null";
			}
			
			$result = mysqli_query($this->connection, "UPDATE `service_company_client_subservice` SET `name`='".$name."', `price`='".$price."',  `bonus`='".$bonus."', `units`='".$units."', `iva-type_id`='".$ivaTypeId."', `description`='".$description."', `periodicity_id`=".$periodicityId.", `starting_date`=".$startingDate.", `ending_date`=".$endingDate.", `billing_date`=".$billingDate.", `main_employee_id`= ".$mainEmployeeId.", `secondary_employee_id`= ".$secondaryEmployeeId.", `hired`= ".$hired." , `budget_date`=".$budgetDate." WHERE `id`='".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD Client contacts
		function getClientContactsOfClient($clientId){
			$result = mysqli_query($this->connection,"SELECT * FROM `client-contacts` WHERE client_id='".$clientId."' AND  active=1 ");
			$clientContacts = array();
			while($clientContact =  mysqli_fetch_assoc($result)){
				$clientContacts[] = $clientContact;
			}
			return $clientContacts;
		}

		function getClientContactById($clientContactId){
			$result = mysqli_query($this->connection,"SELECT * FROM `client-contacts` WHERE id='".$clientContactId."'");
			$clientContact = mysqli_fetch_assoc($result);
			if($result){
				return $clientContact;
			}else{
				return false;
			}
		}

		function addClientContact($name, $surnames, $phone, $email, $dni, $department, $employment, $position, $observations, $clientId ){
			$result = mysqli_query($this->connection, "INSERT INTO `client-contacts` (`name`, `surnames`, `phone`, `email`, `dni`, `department`, `employment`, `position`, `observations`, `client_id`, `active`)  VALUES ('".$name."','".$surnames."','".$phone."','".$email."','".$dni."','".$department."','".$employment."','".$position."','".$observations."','".$clientId."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function disableClientContact($clientContactId){
			$result = mysqli_query($this->connection, "UPDATE `client-contacts` SET `active`=0 WHERE `id` = '".$clientContactId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}


		function setClientContact($id, $name, $surnames, $phone, $email, $dni, $department, $employment, $position, $observations, $clientId ){
			$result = mysqli_query($this->connection, "UPDATE `client-contacts` SET `name`='".$name."',`surnames`='".$surnames."',`phone`='".$phone."',`email`='".$email."',`dni`='".$dni."',`department`='".$department."',`employment`='".$employment."',`position`='".$position."',`observations`='".$observations."',`client_id`='".$clientId."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function clientContactDniExists($dni){
			$result = mysqli_query($this->connection, "SELECT dni FROM `client-contacts` WHERE dni='".$dni."'");
			
			if(mysqli_num_rows($result)>0){
				return true;
			}else{
				return false;
			}
		}

		//CRUD Shipping Addresses
		function getShippingAddressById($clientShippingAddressId){
			// $result = mysqli_query($this->connection,"SELECT * FROM `client-addresses` WHERE id='".$clientShippingAddressId."'");
			$result = mysqli_query($this->connection,"SELECT ca.`id`, ca.`name`, ca.`last_name`, ca.`email`, ca.`sending_type_id`, st.name AS sending_type_name FROM `client-addresses` ca ,`sending-types` st
			WHERE ca.sending_type_id = st.id AND ca.`id`='".$clientShippingAddressId."'");
			$clientShippingAddress = mysqli_fetch_assoc($result);
			if($result){
				return $clientShippingAddress;
			}else{
				return false;
			}
		}

		/*Add an shipping address*/
		function addShippingAddress($name, $lastName, $email, $clientId, $sendingTypeId){
			$result = mysqli_query($this->connection, "INSERT INTO `client-addresses`(`name`, `last_name`, `email`, `client_id`, `sending_type_id`, `active`) VALUES ('".$name."','".$lastName."','".$email."','".$clientId."','".$sendingTypeId."',1)");
			// $result = mysqli_query($this->connection, "INSERT INTO `employees`(`name`, `image`, `company_id`, `department_id`, `employment_id`, `contract`, `email`, `phone`, `personal_email`, `personal_phone`, `observations`, `postal_code`, `province`, `city`, `country`, `facebook`, `instagram`, `twitter`, `linkedin`, `active`, `signup_date`) VALUES ('".$name."','".$image."','".$companyId."','".$departmentId."',".$employmentId.",'c','e','p','pe','pp','o','pc','p','c','c','f','i','t','l',1, $signupDate)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		
		function disableShippingAddress($id){
			$result = mysqli_query($this->connection, "UPDATE `client-addresses` SET `active`=0 WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		/* Update a shipping address */
		function setShippingAddress($id, $name, $lastName, $email, $sendingTypeId){
			$result = mysqli_query($this->connection, "UPDATE `client-addresses` SET `name`='".$name."', `last_name`='".$lastName."', `email`='".$email."', `sending_type_id`='".$sendingTypeId."' WHERE `id`='".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}


		// CRUD Company contacts

		function getCompanyContactsOfCompany($companyId){
			$result = mysqli_query($this->connection,"SELECT * FROM `company-contacts` WHERE company_id='".$companyId."' AND  active=1 ");
			$companyContacts = array();
			while($companyContact =  mysqli_fetch_assoc($result)){
				$companyContacts[] = $companyContact;
			}
			return $companyContacts;
		}

		function getCompanyContactById($companyContactId){
			$result = mysqli_query($this->connection,"SELECT * FROM `company-contacts` WHERE id='".$companyContactId."' ");
			$companyContact = mysqli_fetch_assoc($result);
			if($result){
				return $companyContact;
			}else{
				return false;
			}
		}

		function addCompanyContact($name, $surnames, $dni, $address, $position, $companyId){
			$result = mysqli_query($this->connection, "INSERT INTO `company-contacts` (`name`, `surnames`, `dni`, `address`, `position`,`company_id`, `active`)  VALUES ('".$name."','".$surnames."','".$dni."','".$address."', '".$position."','".$companyId."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function disableCompanyContact($companyContactId){
			$result = mysqli_query($this->connection, "UPDATE `company-contacts` SET `active`=0 WHERE `id` = '".$companyContactId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function deleteCompanyById($deleteCompanyById){
			$result = mysqli_query($this->connection, "DELETE FROM `companies` WHERE id='".$deleteCompanyById."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}


		function setCompanyContact($id, $name, $surnames, $dni, $address, $position, $companyId ){
			$result = mysqli_query($this->connection, "UPDATE `company-contacts` SET `name`='".$name."',`surnames`='".$surnames."',`dni`='".$dni."',`address`='".$address."', `position`='".$position."',`company_id`='".$companyId."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function companyContactDniExists($dni){
			$result = mysqli_query($this->connection, "SELECT dni FROM `company-contacts` WHERE dni='".$dni."'");
			
			if(mysqli_num_rows($result)>0){
				return true;
			}else{
				return false;
			}
		}
		

		// CRUD CONTACT ROLES

		function getContactRoles(){
			$result = mysqli_query($this->connection,"SELECT * FROM `contact-roles`");
			$contactRoles = array();
			while($contactRole =  mysqli_fetch_assoc($result)){
				$contactRoles[] = $contactRole;
			}
			return $contactRoles;
		}

		// CRUD COMPANY DOCUMENTS

		function getCompanyDocuments($companyId){
			$result = mysqli_query($this->connection,"SELECT * FROM `document-companies` WHERE company_id='".$companyId."' AND active=1");
			$companyDocuments = array();
			while($companyDocument =  mysqli_fetch_assoc($result)){
				$companyDocuments[] = $companyDocument;
			}
			return $companyDocuments;
		}

		function addCompanyDocument($name, $fileExtension, $description, $companyId){
			$lastModifiedDate = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "INSERT INTO `document-companies`(`name`, `file_extension`, `description`, `last_modified_date` , `company_id`, `active`) VALUES ('".$name."', '".$fileExtension."', '".$description."', '".$lastModifiedDate."','".$companyId."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function setCompanyDocument($id, $name, $description){
			$lastModifiedDate = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "UPDATE `document-companies` SET `name`='".$name."',`description`='".$description."',`last_modified_date`='".$lastModifiedDate."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function disableCompanyDocument($companyDocumentId){
			$result = mysqli_query($this->connection, "UPDATE `document-companies` SET `active`=0 WHERE `id` = '".$companyDocumentId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function deleteCompanyDocument($companyDocumentId){
			$result = mysqli_query($this->connection, "DELETE FROM `document-companies` WHERE `id` = '".$companyDocumentId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD SERVICE DOCUMENTS

		function getServiceDocuments($serviceId){
			$result = mysqli_query($this->connection,"SELECT * FROM `document-services` WHERE service_id='".$serviceId."' AND  active=1");
			$serviceDocuments = array();
			while($serviceDocument =  mysqli_fetch_assoc($result)){
				$serviceDocuments[] = $serviceDocument;
			}
			return $serviceDocuments;
		}

		function addServiceDocument($name, $fileExtension, $description, $serviceId){
			$uploadDate = date('Y-m-d H:i:s');
			// $result = mysqli_query($this->connection, "INSERT INTO `document-services`(`name`, `description`, `upload_date` , `service_company_id`) VALUES ('".$name."', '".$description."', '".$uploadDate."','".$serviceCompanyId."')");
			$result = mysqli_query($this->connection, "INSERT INTO `document-services`(`name`, `file_extension`, `description`, `last_modified_date` , `service_id`, `active`) VALUES ('".$name."', '".$fileExtension."', '".$description."', '".$uploadDate."','".$serviceId."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD EMPLOYEE DOCUMENTS

		function getEmployeeDocuments($employeeId){
			$result = mysqli_query($this->connection,"SELECT * FROM `document-employees` WHERE employee_id='".$employeeId."' AND  active=1");
			$employeeDocuments = array();
			while($employeeDocument =  mysqli_fetch_assoc($result)){
				$employeeDocuments[] = $employeeDocument;
			}
			return $employeeDocuments;
		}

		function addEmployeeDocument($name, $fileExtension, $description, $employeeId){
			$uploadDate = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "INSERT INTO `document-employees`(`name`, `file_extension`, `description`, `upload_date`, `employee_id`, `active`) VALUES ('".$name."', '".$fileExtension."', '".$description."', '".$uploadDate."','".$employeeId."', 1)");
			// $result = mysqli_query($this->connection, "INSERT INTO `document-employees`(`name`, `file_extension`, `description`, `upload_date` , `employee_id`, `active`) VALUES ('".$name."', '".$fileExtension."', '".$description."', '".$uploadDate."','".$companyId."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function disableEmployeeDocument($employeeDocumentId){
			$result = mysqli_query($this->connection, "UPDATE `document-employees` SET `active`=0 WHERE `id` = '".$employeeDocumentId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function deleteEmployeeDocument($employeeDocumentId){
			$result = mysqli_query($this->connection, "DELETE FROM `document-employees` WHERE `id` = '".$employeeDocumentId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD CLIENT DOCUMENTS

		function getClientDocuments($clientId){
			$result = mysqli_query($this->connection,"SELECT * FROM `document-clients` WHERE client_id='".$clientId."' AND  active=1");
			$clientDocuments = array();
			while($clientDocument =  mysqli_fetch_assoc($result)){
				$clientDocuments[] = $clientDocument;
			}
			return $clientDocuments;
		}

		function addClientDocument($name, $fileExtension, $description, $clientId){
			$uploadDate = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "INSERT INTO `document-clients`(`name`, `file_extension`, `description`, `last_modified_date` , `client_id`, `active`) VALUES ('".$name."', '".$fileExtension."', '".$description."', '".$uploadDate."','".$clientId."', 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function disableClientDocument($clientDocumentId){
			$result = mysqli_query($this->connection, "UPDATE `document-clients` SET `active`=0 WHERE `id` = '".$clientDocumentId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function deleteClientDocument($clientDocumentId){
			$result = mysqli_query($this->connection, "DELETE FROM `document-clients` WHERE `id` = '".$clientDocumentId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function setClientDocument($id, $name, $description){
			$lastModifiedDate = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "UPDATE `document-clients` SET `name`='".$name."',`description`='".$description."',`last_modified_date`='".$lastModifiedDate."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD CONTRACT
		function getContracts(){
			$result = mysqli_query($this->connection,"SELECT contracts.name AS name, contracts.file_extension AS file_extension, contracts.content AS content, contracts.registration_date AS registration_date, contracts.withdrawal_date AS withdrawal_date, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  contracts, `services`, `companies`, `clients`, service_company,  service_company_client WHERE contracts.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id");
			$clients = array();
			while($client =  mysqli_fetch_assoc($result)){
				$clients[] = $client;
			}
			return $clients;

		}

		function getPendingContracts(){
			$result = mysqli_query($this->connection,"SELECT contracts.id, contracts.name, contracts.file_extension, contracts.content, contracts.registration_date, contracts.preparated_date, contracts.signed_part_1_date, contracts.signed_part_2_date, contracts.signed_both_parts_date, contracts.withdrawal_date, contracts.observations, contracts.service_company_client_id, contracts.active, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  contracts , `services`, `companies`, `clients`, service_company,  service_company_client WHERE contracts.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id AND contracts.preparated_date IS NULL AND contracts.active=1" );
			$contracts = array();
			while($contract =  mysqli_fetch_assoc($result)){
				$contracts[] = $contract;
			}
			return $contracts;
		}

		function getPreparedContracts(){
			$result = mysqli_query($this->connection,"SELECT contracts.id, contracts.name, contracts.file_extension, contracts.content, contracts.registration_date, contracts.preparated_date, contracts.signed_part_1_date, contracts.signed_part_2_date, contracts.signed_both_parts_date, contracts.withdrawal_date, contracts.observations, contracts.service_company_client_id, contracts.active, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  contracts , `services`, `companies`, `clients`, service_company,  service_company_client WHERE contracts.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id AND contracts.preparated_date IS NOT NULL AND contracts.withdrawal_date IS NULL AND contracts.active=1");
			$contracts = array();
			while($contract =  mysqli_fetch_assoc($result)){
				$contracts[] = $contract;
			}
			return $contracts;
		}

		function getDisabledContracts(){
			$result = mysqli_query($this->connection,"SELECT contracts.id, contracts.name, contracts.file_extension, contracts.content, contracts.registration_date, contracts.preparated_date, contracts.signed_part_1_date, contracts.signed_part_2_date, contracts.signed_both_parts_date, contracts.withdrawal_date, contracts.observations, contracts.service_company_client_id, contracts.active, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  contracts , `services`, `companies`, `clients`, service_company,  service_company_client WHERE contracts.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id AND contracts.withdrawal_date IS NOT NULL AND contracts.active=1");
			$contracts = array();
			while($contract =  mysqli_fetch_assoc($result)){
				$contracts[] = $contract;
			}
			return $contracts;
		}

		function addContract($name, $fileExtension, $content, $serviceCompanyClientId){
			$registrationDate = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "INSERT INTO contracts(`name`, `file_extension`, `content`, `registration_date`, `service_company_client_id`, `active`) VALUES ('".$name."', '".$fileExtension."', '".$content."', '".$registrationDate."', '".$serviceCompanyClientId."', 1)");

			if($result){
				return true;
			}else{
				return false;
			}
		}

		function setContract($id, $name, $content, $registrationDate, $preparatedDate, $signedPart1Date, $signedPart2Date, $signedBothPartsDate, $withdrawalDate, $observations){
			// $lastModifiedDate = date('Y-m-d H:i:s');
			if($registrationDate == null){
				$registrationDate="NULL";
			} else {
				$registrationDate="'".$registrationDate."'";
			}
			if($preparatedDate == null){
				$preparatedDate="NULL";
			} else {
				$preparatedDate="'".$preparatedDate."'";
			}
			if($signedPart1Date == null){
				$signedPart1Date="NULL";
			} else {
				$signedPart1Date="'".$signedPart1Date."'";
			}
			if($signedPart2Date == null){
				$signedPart2Date="NULL";
			} else {
				$signedPart2Date="'".$signedPart2Date."'";
			}
			if($signedBothPartsDate == null){
				$signedBothPartsDate="NULL";
			} else {
				$signedBothPartsDate="'".$signedBothPartsDate."'";
			}
			if($withdrawalDate == null){
				$withdrawalDate="NULL";
			} else {
				$withdrawalDate="'".$withdrawalDate."'";
			}
			// $result = mysqli_query($this->connection, "UPDATE `document-companies` SET `name`='".$name."',`description`='".$description."',`last_modified_date`='".$lastModifiedDate."' WHERE `id` = '".$id."'");
			$result = mysqli_query($this->connection, "UPDATE contracts SET `id`='".$id."',`name`='".$name."',`content`='".$content."', `registration_date`= ".$registrationDate.", `preparated_date`=".$preparatedDate.",`signed_part_1_date`=".$signedPart1Date.",`signed_part_2_date`=".$signedPart2Date.",`signed_both_parts_date`=".$signedBothPartsDate.",`withdrawal_date`=".$withdrawalDate.",`observations`='".$observations."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function prepareContractToSign($id){
			$dateTime = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "UPDATE contracts SET `preparated_date`='".$dateTime."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}


		function deleteContract($contractId){
			$result = mysqli_query($this->connection, "DELETE FROM contracts WHERE `id` = '".$contractId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}
		
		function deleteContractByName($name){
			$result = mysqli_query($this->connection, "DELETE FROM contracts WHERE `name` = '".$name."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function disableContract($contractId){
			$result = mysqli_query($this->connection, "UPDATE contracts SET `active`=0 WHERE `id` = '".$contractId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD BUDGETS
		function getBudgets(){
			// $result = mysqli_query($this->connection,"SELECT budgets.id AS id, budgets.name AS name, budgets.file_extension AS file_extension, budgets.content AS content, budgets.registration_date AS registration_date, budgets.withdrawal_date AS withdrawal_date, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  budgets, `services`, `companies`, `clients`, service_company,  service_company_client WHERE budgets.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id" );
			$result = mysqli_query($this->connection,"SELECT budgets.id, budgets.name, budgets.file_extension, budgets.content, budgets.registration_date, budgets.observations, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  budgets , `services`, `companies`, `clients`, service_company,  service_company_client WHERE budgets.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id AND budgets.active=1" );
			$clients = array();
			while($client =  mysqli_fetch_assoc($result)){
				$clients[] = $client;
			}
			return $clients;
		}

		function getPendingBudgets(){
			// $result = mysqli_query($this->connection,"SELECT budgets.id AS id, budgets.name AS name, budgets.file_extension AS file_extension, budgets.content AS content, budgets.registration_date AS registration_date, budgets.withdrawal_date AS withdrawal_date, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  budgets, `services`, `companies`, `clients`, service_company,  service_company_client WHERE budgets.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id" );
			$result = mysqli_query($this->connection,"SELECT budgets.id, budgets.name, budgets.file_extension, budgets.content, budgets.registration_date, budgets.preparated_date, budgets.signed_part_1_date, budgets.signed_part_2_date, budgets.signed_both_parts_date, budgets.withdrawal_date, budgets.observations, budgets.service_company_client_id, budgets.active, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  budgets , `services`, `companies`, `clients`, service_company,  service_company_client WHERE budgets.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id AND (budgets.preparated_date IS NULL OR budgets.preparated_date=0) AND budgets.active=1" );
			$budgets = array();
			while($budget =  mysqli_fetch_assoc($result)){
				$budgets[] = $budget;
			}
			return $budgets;
		}

		function getPreparedBudgets(){
			// $result = mysqli_query($this->connection,"SELECT budgets.id AS id, budgets.name AS name, budgets.file_extension AS file_extension, budgets.content AS content, budgets.registration_date AS registration_date, budgets.withdrawal_date AS withdrawal_date, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  budgets, `services`, `companies`, `clients`, service_company,  service_company_client WHERE budgets.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id" );
			$result = mysqli_query($this->connection,"SELECT budgets.id, budgets.name, budgets.file_extension, budgets.content, budgets.registration_date, budgets.preparated_date, budgets.signed_part_1_date, budgets.signed_part_2_date, budgets.signed_both_parts_date, budgets.withdrawal_date, budgets.observations, budgets.service_company_client_id, budgets.active, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  budgets , `services`, `companies`, `clients`, service_company,  service_company_client WHERE budgets.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id AND (budgets.preparated_date IS NOT NULL OR budgets.preparated_date=0) AND budgets.withdrawal_date IS NULL AND budgets.active=1");
			$budgets = array();
			while($budget =  mysqli_fetch_assoc($result)){
				$budgets[] = $budget;
			}
			return $budgets;
		}

		function getDisabledBudgets(){
			// $result = mysqli_query($this->connection,"SELECT budgets.id AS id, budgets.name AS name, budgets.file_extension AS file_extension, budgets.content AS content, budgets.registration_date AS registration_date, budgets.withdrawal_date AS withdrawal_date, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  budgets, `services`, `companies`, `clients`, service_company,  service_company_client WHERE budgets.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id" );
			$result = mysqli_query($this->connection,"SELECT budgets.id, budgets.name, budgets.file_extension, budgets.content, budgets.registration_date, budgets.preparated_date, budgets.signed_part_1_date, budgets.signed_part_2_date, budgets.signed_both_parts_date, budgets.withdrawal_date, budgets.observations, budgets.service_company_client_id, budgets.active, services.name AS service_name, companies.name AS company_name, clients.name AS client_name FROM  budgets , `services`, `companies`, `clients`, service_company,  service_company_client WHERE budgets.service_company_client_id = service_company_client.id AND service_company_client.client_id = clients.id AND service_company_client.service_company_id = service_company.id AND service_company.service_id = services.id AND service_company.company_id = companies.id AND budgets.withdrawal_date IS NOT NULL AND budgets.active=1");
			$budgets = array();
			while($budget =  mysqli_fetch_assoc($result)){
				$budgets[] = $budget;
			}
			return $budgets;
		}

		function addBudget($name, $fileExtension, $content, $serviceCompanyClientId){
			$registrationDate = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "INSERT INTO budgets(`name`, `file_extension`, `content`, `registration_date`, `service_company_client_id`, `active`) VALUES ('".$name."', '".$fileExtension."', '".$content."', '".$registrationDate."', '".$serviceCompanyClientId."',1)");

			if($result){
				return true;
			}else{
				return false;
			}
		}

		function setBudget($id, $name, $content, $registrationDate, $preparatedDate, $signedPart1Date, $signedPart2Date, $signedBothPartsDate, $withdrawalDate, $observations){
			// $lastModifiedDate = date('Y-m-d H:i:s');
			if($registrationDate == null){
				$registrationDate="NULL";
			} else {
				$registrationDate="'".$registrationDate."'";
			}
			if($preparatedDate == null){
				$preparatedDate="NULL";
			} else {
				$preparatedDate="'".$preparatedDate."'";
			}
			if($signedPart1Date == null){
				$signedPart1Date="NULL";
			} else {
				$signedPart1Date="'".$signedPart1Date."'";
			}
			if($signedPart2Date == null){
				$signedPart2Date="NULL";
			} else {
				$signedPart2Date="'".$signedPart2Date."'";
			}
			if($signedBothPartsDate == null){
				$signedBothPartsDate="NULL";
			} else {
				$signedBothPartsDate="'".$signedBothPartsDate."'";
			}
			if($withdrawalDate == null){
				$withdrawalDate="NULL";
			} else {
				$withdrawalDate="'".$withdrawalDate."'";
			}
			// $result = mysqli_query($this->connection, "UPDATE `document-companies` SET `name`='".$name."',`description`='".$description."',`last_modified_date`='".$lastModifiedDate."' WHERE `id` = '".$id."'");
			$result = mysqli_query($this->connection, "UPDATE budgets SET `id`='".$id."',`name`='".$name."',`content`='".$content."', `registration_date`= ".$registrationDate.", `preparated_date`=".$preparatedDate.",`signed_part_1_date`=".$signedPart1Date.",`signed_part_2_date`=".$signedPart2Date.",`signed_both_parts_date`=".$signedBothPartsDate.",`withdrawal_date`=".$withdrawalDate.",`observations`='".$observations."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function prepareBudgetToSign($id){
			$dateTime = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "UPDATE budgets SET `preparated_date`='".$dateTime."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}


		function deleteBudget($budgetId){
			$result = mysqli_query($this->connection, "DELETE FROM budgets WHERE `id` = '".$budgetId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}
		
		function deleteBudgetByName($name){
			$result = mysqli_query($this->connection, "DELETE FROM budgets WHERE `name` = '".$name."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function disableBudget($budgetId){
			$result = mysqli_query($this->connection, "UPDATE budgets SET `active`=0 WHERE `id` = '".$budgetId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD BILLS

		function getBillById($id){
			$result = mysqli_query($this->connection,"SELECT bills.id, bills.name, bills.file_extension, bills.bill_number, bills.generation_date, bills.operation_date, bills.reference_date,
			DATE_FORMAT(bills.generation_date, '%M') AS generation_date_month, bills.issue_date, bills.preparated_date,
			bills.expiration_date, bills.sending_date, bills.observations, bills.collecting_date, services.name AS service_name, services.acronym AS service_acronym,
			companies.id AS company_id, companies.name AS company_name, companies.image AS company_image, companies.cif AS company_cif, companies.address AS
			company_address, companies.phone AS company_phone, companies.email AS company_email, companies.iban AS company_iban,companies.acronym AS company_acronym,
			clients.name AS client_name, clients.cif AS client_cif, clients.address AS client_address, clients.postal_code AS
			client_postal_code, clients.city AS client_city, service_company_client.id AS service_company_client_id FROM bills, `services`, `companies`, `clients`, service_company,
			service_company_client WHERE
			bills.service_company_client_id=service_company_client.id AND
			service_company_client.service_company_id=service_company.id AND service_company.service_id=services.id AND
			service_company.company_id=companies.id AND service_company_client.client_id=clients.id AND bills.id='".$id."'");
			$bill = mysqli_fetch_assoc($result);
			if($result){
				return $bill;
			}else{
				return false;
			}
		}

		function getBillByBillId($billId){
			$result = mysqli_query($this->connection,"SELECT bills.id, bills.name, bills.file_extension, bills.bill_number, bills.generation_date, bills.operation_date, bills.reference_date,
			DATE_FORMAT(bills.generation_date, '%M') AS generation_date_month, bills.issue_date, bills.preparated_date,
			bills.expiration_date, bills.sending_date, bills.observations, bills.collecting_date, services.name AS service_name, services.acronym AS service_acronym,
			companies.id AS company_id, companies.name AS company_name, companies.image AS company_image, companies.cif AS company_cif, companies.address AS
			company_address, companies.phone AS company_phone, companies.email AS company_email, companies.iban AS company_iban,companies.acronym AS company_acronym,
			clients.name AS client_name, clients.cif AS client_cif, clients.address AS client_address, clients.postal_code AS
			client_postal_code, clients.city AS client_city, service_company_client.id AS service_company_client_id FROM bills, `services`, `companies`, `clients`, service_company,
			service_company_client WHERE
			bills.service_company_client_id=service_company_client.id AND
			service_company_client.service_company_id=service_company.id AND service_company.service_id=services.id AND
			service_company.company_id=companies.id AND service_company_client.client_id=clients.id AND bills.bill_id='".$billId."'");
			$bill = mysqli_fetch_assoc($result);
			if($result){
				return $bill;
			}else{
				return false;
			}
		}

		function getPendingBills(){
			$this->connection->query("SET lc_time_names = 'es_ES'");
			$result = mysqli_query($this->connection,"SELECT bpb.id, bpb.name, bpb.file_extension, bpb.bill_number, bpb.generation_date,
				DATE_FORMAT(bpb.reference_date, '%m') AS reference_date_month, DATE_FORMAT(bpb.reference_date, '%Y') AS reference_date_year, bpb.issue_date, bpb.preparated_date,
				bpb.operation_date, bpb.expiration_date, bpb.sending_date, bpb.observations, bpb.collecting_date, spb.name AS service_name, spb.acronym AS service_acronym,
				copb.id AS company_id, copb.name AS company_name, copb.image AS company_image, copb.cif AS company_cif, copb.address AS
				company_address, copb.phone AS company_phone, copb.city AS company_city, copb.postal_code AS company_postal_code, copb.email AS company_email, copb.iban AS company_iban, 
				copb.billing_email AS company_billing_email, copb.web AS company_web, copb.acronym AS company_acronym,
				clpb.name AS client_name, clpb.cif AS client_cif, clpb.address AS client_address, clpb.payment_method AS client_payment_method, clpb.postal_code AS
				client_postal_code, clpb.city AS client_city, sccpb.id AS service_company_client_id,
				
				(
				SELECT ROUND(SUM(((
					CASE WHEN service_company_client_subservice.periodicity_id = 1 AND service_company_client_subservice.snap_date IS NOT NULL THEN service_company_client_subservice.price ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 2
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/12 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 3
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)<=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR), 1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL 
											 1 DAY  END, 
								service_company_client_subservice.starting_date) +1) * service_company_client_subservice.price/
										(DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											  THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR),1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								  ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL  1 DAY  END , 
											  MAKEDATE(YEAR(service_company_client_subservice.starting_date), 1) + INTERVAL QUARTER(service_company_client_subservice.starting_date) QUARTER - INTERVAL 1 QUARTER )+1)/4
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/4 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 4 
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/2 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 5
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price END
					) ELSE 0 END
				)) * service_company_client_subservice.units * (service_company_client_subservice.bonus/100 + 1)),2) AS price
				FROM service_company_client_subservice, service_company_client, subservices, bills, `iva-types`
				WHERE subservices.id=service_company_client_subservice.subservice_id
				AND service_company_client_subservice.service_company_client_id=service_company_client.id
				AND bills.service_company_client_id=service_company_client.id
				AND service_company_client_subservice.service_company_client_id=bills.service_company_client_id
				AND service_company_client_subservice.`iva-type_id`=`iva-types`.id
				AND service_company_client_subservice.hired =1 AND
				(
					(
						service_company_client_subservice.periodicity_id=1 
						AND service_company_client_subservice.snap_date IS NOT NULL
						AND (
							MONTH(service_company_client_subservice.billing_date)=MONTH(bills.reference_date) 
							AND YEAR(service_company_client_subservice.billing_date)=YEAR(bills.reference_date)
						)
						AND service_company_client_subservice.id NOT IN (SELECT service_company_client_subservice_id FROM billing)
					)
					OR (
						service_company_client_subservice.periodicity_id=2
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
							-- (MONTH(service_company_client_subservice.starting_date)<=MONTH(bills.reference_date) 
							-- AND YEAR(service_company_client_subservice.starting_date)<=YEAR(bills.reference_date))
							-- AND
							-- (MONTH(service_company_client_subservice.ending_date)>=MONTH(bills.reference_date) 
							-- AND YEAR(service_company_client_subservice.ending_date)>=YEAR(bills.reference_date))
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND billing.bill_id = b2.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
					OR (
						service_company_client_subservice.periodicity_id=3
						AND (
							MONTH(bills.reference_date)=03 OR MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=09 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=4 
						AND (
							MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=5 
						AND (
							MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
				)
				AND bills.id=bpb.id ) AS
				amount FROM bills bpb, `services` spb, `companies` copb, `clients` clpb, service_company scpb,
				service_company_client sccpb WHERE
				bpb.service_company_client_id=sccpb.id AND
				sccpb.service_company_id=scpb.id AND scpb.service_id=spb.id AND
				scpb.company_id=copb.id AND sccpb.client_id=clpb.id AND 
				bpb.issue_date IS NULL "
			);
			$pendingBills = array();
			while($pendingBill =  mysqli_fetch_assoc($result)){
				$pendingBills[] = $pendingBill;
			}
			return $pendingBills;
		}
        
        function getSentBills(){
			$this->connection->query("SET lc_time_names = 'es_ES'");
			$result = mysqli_query($this->connection,"SELECT bpb.id, bpb.name, bpb.file_extension, bpb.bill_number, bpb.generation_date,
				DATE_FORMAT(bpb.reference_date, '%M') AS reference_date_month, DATE_FORMAT(bpb.reference_date, '%Y') AS reference_date_year, bpb.issue_date, bpb.preparated_date,
				bpb.operation_date, bpb.expiration_date, bpb.sending_date, bpb.amending_date, bpb.observations, bpb.collecting_date, spb.name AS service_name, spb.acronym AS service_acronym,
				copb.id AS company_id, copb.name AS company_name, copb.image AS company_image, copb.cif AS company_cif,  copb.web AS company_web,copb.address AS
				company_address, copb.phone AS company_phone, copb.city AS company_city, copb.postal_code AS company_postal_code, copb.email AS company_email, copb.billing_email AS company_billing_email, copb.iban AS company_iban,copb.acronym AS company_acronym,
				clpb.id AS client_id, clpb.name AS client_name, clpb.cif AS client_cif, clpb.address AS client_address, clpb.payment_method AS client_payment_method, clpb.postal_code AS
				client_postal_code, clpb.city AS client_city, sccpb.id AS service_company_client_id,
				(SELECT ROUND(SUM(`price`*units*(bonus/100 + 1)), 2) FROM `billing` WHERE `bill_id`= bpb.id) AS amount_sum,
				(SELECT ROUND(SUM(`price`*units*(bonus/100 + 1)*`iva-type_percentage`/100), 2) FROM `billing` WHERE `bill_id`= bpb.id) AS iva_amount_sum,
				(SELECT ROUND(SUM(`total_amount`), 2) FROM `billing` WHERE `bill_id`= bpb.id) AS total_amount_sum,
				(
				SELECT ROUND(SUM(((
					CASE WHEN service_company_client_subservice.periodicity_id = 1 AND service_company_client_subservice.snap_date IS NOT NULL THEN service_company_client_subservice.price ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 2
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/12 END
					) ELSE 0 END
				)
				-- + (
				-- 	CASE WHEN service_company_client_subservice.periodicity_id = 3
				-- 	THEN (
				-- 		CASE WHEN (
				-- 			(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
				-- 			AND
				-- 			(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
				-- 		) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
				-- 		WHEN (
				-- 			MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
				-- 		) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
				-- 		WHEN (
				-- 			MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
				-- 		) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
				-- 		ELSE service_company_client_subservice.price/4 END
				-- 	) ELSE 0 END
				-- )
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 3
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)<=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR), 1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL 
											 1 DAY  END, 
								service_company_client_subservice.starting_date) +1) * service_company_client_subservice.price/
										(DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											  THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR),1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								  ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL  1 DAY  END , 
											  MAKEDATE(YEAR(service_company_client_subservice.starting_date), 1) + INTERVAL QUARTER(service_company_client_subservice.starting_date) QUARTER - INTERVAL 1 QUARTER )+1)/4
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/4 END
					) ELSE 0 END
				)		
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 4 
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/2 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 5
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price END
					) ELSE 0 END
				)) * service_company_client_subservice.units * (service_company_client_subservice.bonus/100 + 1)),2) AS price
				FROM service_company_client_subservice, service_company_client, subservices, bills, `iva-types`
				WHERE subservices.id=service_company_client_subservice.subservice_id
				AND service_company_client_subservice.service_company_client_id=service_company_client.id
				AND bills.service_company_client_id=service_company_client.id
				AND service_company_client_subservice.service_company_client_id=bills.service_company_client_id
				AND service_company_client_subservice.`iva-type_id`=`iva-types`.id
				AND 
				(
					(
						service_company_client_subservice.periodicity_id=1 
						AND service_company_client_subservice.snap_date IS NOT NULL
						AND (
							MONTH(service_company_client_subservice.billing_date)=MONTH(bills.reference_date) 
							AND YEAR(service_company_client_subservice.billing_date)=YEAR(bills.reference_date)
						)
						AND service_company_client_subservice.id NOT IN (SELECT service_company_client_subservice_id FROM billing)
					)
					OR (
						service_company_client_subservice.periodicity_id=2
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
							-- (MONTH(service_company_client_subservice.starting_date)<=MONTH(bills.reference_date) 
							-- AND YEAR(service_company_client_subservice.starting_date)<=YEAR(bills.reference_date))
							-- AND
							-- (MONTH(service_company_client_subservice.ending_date)>=MONTH(bills.reference_date) 
							-- AND YEAR(service_company_client_subservice.ending_date)>=YEAR(bills.reference_date))
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND billing.bill_id = b2.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
					OR (
						service_company_client_subservice.periodicity_id=3
						AND (
							MONTH(bills.reference_date)=03 OR MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=09 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=4 
						AND (
							MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=5 
						AND (
							MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
				)
				AND bills.id=bpb.id) AS
				amount FROM bills bpb, `services` spb, `companies` copb, `clients` clpb, service_company scpb,
				service_company_client sccpb WHERE
				bpb.service_company_client_id=sccpb.id AND
				sccpb.service_company_id=scpb.id AND scpb.service_id=spb.id AND
				scpb.company_id=copb.id AND sccpb.client_id=clpb.id AND
				bpb.issue_date IS NOT NULL AND 
				bpb.sending_date IS NOT NULL"
			);
			$pendingBills = array();
			while($pendingBill =  mysqli_fetch_assoc($result)){
				$pendingBills[] = $pendingBill;
			}
			return $pendingBills;
		}

		function getIssuedBills(){
	
			$result = mysqli_query($this->connection,"SELECT bpb.id, bpb.name, bpb.file_extension, bpb.bill_number, bpb.generation_date,
				DATE_FORMAT(bpb.reference_date, '%M') AS reference_date_month, DATE_FORMAT(bpb.reference_date, '%Y') AS reference_date_year, bpb.issue_date, bpb.preparated_date,
				bpb.operation_date, bpb.expiration_date, bpb.sending_date, bpb.observations, bpb.collecting_date, spb.name AS service_name, spb.acronym AS service_acronym,
				copb.id AS company_id, copb.name AS company_name, copb.image AS company_image, copb.cif AS company_cif,  copb.web AS company_web,copb.address AS
				company_address, copb.phone AS company_phone, copb.city AS company_city, copb.postal_code AS company_postal_code, copb.email AS company_email, copb.billing_email AS company_billing_email, copb.iban AS company_iban,copb.acronym AS company_acronym,
				clpb.id AS client_id, clpb.name AS client_name, clpb.cif AS client_cif, clpb.address AS client_address, clpb.payment_method AS client_payment_method, clpb.postal_code AS
				client_postal_code, clpb.city AS client_city, sccpb.id AS service_company_client_id,
				(SELECT ROUND(SUM(`price`*units*(bonus/100 + 1)), 2) FROM `billing` WHERE `bill_id`= bpb.id) AS amount_sum,
				(SELECT ROUND(SUM(`price`*units*(bonus/100 + 1)*`iva-type_percentage`/100), 2) FROM `billing` WHERE `bill_id`= bpb.id) AS iva_amount_sum,
				(SELECT ROUND(SUM(`total_amount`), 2) FROM `billing` WHERE `bill_id`= bpb.id) AS total_amount_sum,
				(
				SELECT ROUND(SUM(((
					CASE WHEN service_company_client_subservice.periodicity_id = 1 AND service_company_client_subservice.snap_date IS NOT NULL THEN service_company_client_subservice.price ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 2
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/12 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 3
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)<=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR), 1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL 
											 1 DAY  END, 
								service_company_client_subservice.starting_date) +1) * service_company_client_subservice.price/
										(DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											  THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR),1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								  ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL  1 DAY  END , 
											  MAKEDATE(YEAR(service_company_client_subservice.starting_date), 1) + INTERVAL QUARTER(service_company_client_subservice.starting_date) QUARTER - INTERVAL 1 QUARTER )+1)/4
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/4 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 4 
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/2 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 5
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price END
					) ELSE 0 END
				)) * service_company_client_subservice.units * (service_company_client_subservice.bonus/100 + 1)),2) AS price
				FROM service_company_client_subservice, service_company_client, subservices, bills, `iva-types`
				WHERE subservices.id=service_company_client_subservice.subservice_id
				AND service_company_client_subservice.service_company_client_id=service_company_client.id
				AND bills.service_company_client_id=service_company_client.id
				AND service_company_client_subservice.service_company_client_id=bills.service_company_client_id
				AND service_company_client_subservice.`iva-type_id`=`iva-types`.id
				AND 
				(
					(
						service_company_client_subservice.periodicity_id=1 
						AND service_company_client_subservice.snap_date IS NOT NULL
						AND (
							MONTH(service_company_client_subservice.billing_date)=MONTH(bills.reference_date) 
							AND YEAR(service_company_client_subservice.billing_date)=YEAR(bills.reference_date)
						)
						AND service_company_client_subservice.id NOT IN (SELECT service_company_client_subservice_id FROM billing)
					)
					OR (
						service_company_client_subservice.periodicity_id=2
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
							-- (MONTH(service_company_client_subservice.starting_date)<=MONTH(bills.reference_date) 
							-- AND YEAR(service_company_client_subservice.starting_date)<=YEAR(bills.reference_date))
							-- AND
							-- (MONTH(service_company_client_subservice.ending_date)>=MONTH(bills.reference_date) 
							-- AND YEAR(service_company_client_subservice.ending_date)>=YEAR(bills.reference_date))
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND billing.bill_id = b2.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
					OR (
						service_company_client_subservice.periodicity_id=3
						AND (
							MONTH(bills.reference_date)=03 OR MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=09 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=4 
						AND (
							MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=5 
						AND (
							MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
				)
				AND bills.id=bpb.id) AS
				amount FROM bills bpb, `services` spb, `companies` copb, `clients` clpb, service_company scpb,
				service_company_client sccpb WHERE
				bpb.service_company_client_id=sccpb.id AND
				sccpb.service_company_id=scpb.id AND scpb.service_id=spb.id AND
				scpb.company_id=copb.id AND sccpb.client_id=clpb.id AND
				bpb.issue_date IS NOT NULL AND bpb.sending_date IS NULL AND
				(bpb.amending_date IS NULL AND bpb.bill_id IS NULL)"
				
			);
			$pendingBills = array();
			while($pendingBill =  mysqli_fetch_assoc($result)){
				$pendingBills[] = $pendingBill;
			}
			return $pendingBills;
		}

		function getNullifiedBills(){
			// $result = mysqli_query($this->connection,"SELECT bills.id, bills.name, bills.file_extension, bills.bill_number, bills.issue_date, bills.expiration_date, bills.sending_date, bills.collecting_date, services.name AS service_name, companies.name AS company_name, companies.image AS company_image, companies.cif AS company_cif, companies.address AS company_address, companies.phone AS company_phone, companies.email AS company_email, companies.iban AS company_iban, clients.name AS client_name, clients.cif AS client_cif, clients.address AS client_address, clients.postal_code AS client_postal_code, clients.city AS client_city, service_company_client.id AS service_company_client_id FROM  bills, `services`, `companies`, `clients`, service_company,  service_company_client WHERE bills.service_company_client_id=service_company_client.id AND service_company_client.service_company_id=service_company.id AND service_company.service_id=services.id AND service_company.company_id=companies.id AND service_company_client.client_id=clients.id AND  bills.issue_date IS NOT NULL ");
			$result = mysqli_query($this->connection,"SELECT bpb.id, bpb.name, bpb.file_extension, bpb.bill_number, bpb.generation_date,
				DATE_FORMAT(bpb.reference_date, '%M') AS reference_date_month, DATE_FORMAT(bpb.reference_date, '%Y') AS reference_date_year, bpb.issue_date, bpb.preparated_date,
				bpb.operation_date, bpb.expiration_date, bpb.sending_date, bpb.observations, bpb.collecting_date, spb.name AS service_name, spb.acronym AS service_acronym,
				copb.id AS company_id, copb.name AS company_name, copb.image AS company_image, copb.cif AS company_cif, copb.address AS
				company_address, copb.phone AS company_phone, copb.city AS company_city, copb.postal_code AS company_postal_code, copb.email AS company_email, copb.iban AS company_iban,copb.acronym AS company_acronym,
				clpb.id AS client_id, clpb.name AS client_name, clpb.cif AS client_cif, clpb.address AS client_address, clpb.postal_code AS
				client_postal_code, clpb.city AS client_city, sccpb.id AS service_company_client_id,
				(SELECT ROUND(SUM(`price`*units*(bonus/100 + 1)), 2) FROM `billing` WHERE `bill_id`= bpb.id) AS amount_sum,
				(SELECT ROUND(SUM(`price`*units*(bonus/100 + 1)*`iva-type_percentage`/100), 2) FROM `billing` WHERE `bill_id`= bpb.id) AS iva_amount_sum,
				(SELECT ROUND(SUM(`total_amount`), 2) FROM `billing` WHERE `bill_id`= bpb.id) AS total_amount_sum,
				(
				SELECT ROUND(SUM((
					CASE WHEN service_company_client_subservice.periodicity_id = 1 AND service_company_client_subservice.snap_date IS NOT NULL THEN service_company_client_subservice.price ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 2
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/12 END
					) ELSE 0 END
				)
				-- + (
				-- 	CASE WHEN service_company_client_subservice.periodicity_id = 3
				-- 	THEN (
				-- 		CASE WHEN (
				-- 			(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
				-- 			AND
				-- 			(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
				-- 		) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
				-- 		WHEN (
				-- 			MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
				-- 		) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
				-- 		WHEN (
				-- 			MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
				-- 		) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
				-- 		ELSE service_company_client_subservice.price/4 END
				-- 	) ELSE 0 END
				-- )
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 3
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)<=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR), 1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL 
											 1 DAY  END, 
								service_company_client_subservice.starting_date) +1) * service_company_client_subservice.price/
										(DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											  THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR),1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								  ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL  1 DAY  END , 
											  MAKEDATE(YEAR(service_company_client_subservice.starting_date), 1) + INTERVAL QUARTER(service_company_client_subservice.starting_date) QUARTER - INTERVAL 1 QUARTER )+1)/4
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/4 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 4 
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/2 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 5
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price END
					) ELSE 0 END
				)),2) AS price
				FROM service_company_client_subservice, service_company_client, subservices, bills, `iva-types`
				WHERE subservices.id=service_company_client_subservice.subservice_id
				AND service_company_client_subservice.service_company_client_id=service_company_client.id
				AND bills.service_company_client_id=service_company_client.id
				AND service_company_client_subservice.service_company_client_id=bills.service_company_client_id
				AND service_company_client_subservice.`iva-type_id`=`iva-types`.id
				AND 
				(
					(
						service_company_client_subservice.periodicity_id=1 
						AND service_company_client_subservice.snap_date IS NOT NULL
						AND (
							MONTH(service_company_client_subservice.billing_date)=MONTH(bills.reference_date) 
							AND YEAR(service_company_client_subservice.billing_date)=YEAR(bills.reference_date)
						)
						AND service_company_client_subservice.id NOT IN (SELECT service_company_client_subservice_id FROM billing)
					)
					OR (
						service_company_client_subservice.periodicity_id=2
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
							-- (MONTH(service_company_client_subservice.starting_date)<=MONTH(bills.reference_date) 
							-- AND YEAR(service_company_client_subservice.starting_date)<=YEAR(bills.reference_date))
							-- AND
							-- (MONTH(service_company_client_subservice.ending_date)>=MONTH(bills.reference_date) 
							-- AND YEAR(service_company_client_subservice.ending_date)>=YEAR(bills.reference_date))
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND billing.bill_id = b2.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
					OR (
						service_company_client_subservice.periodicity_id=3
						AND (
							MONTH(bills.reference_date)=03 OR MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=09 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=4 
						AND (
							MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=5 
						AND (
							MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
				)
				AND bills.id=bpb.id) AS
				amount FROM bills bpb, `services` spb, `companies` copb, `clients` clpb, service_company scpb,
				service_company_client sccpb WHERE
				bpb.service_company_client_id=sccpb.id AND
				sccpb.service_company_id=scpb.id AND scpb.service_id=spb.id AND
				scpb.company_id=copb.id AND sccpb.client_id=clpb.id AND 
				bpb.sending_date IS NULL  AND 
				(bpb.amending_date IS NOT NULL OR bpb.bill_id IS NOT NULL)"
			);
			$nullifiedBills = array();
			while($nullifiedBill =  mysqli_fetch_assoc($result)){
				$nullifiedBills[] = $nullifiedBill;
			}
			return $nullifiedBills;
		}

		function getPendingBillsByServiceCompanyClientId($serviceCompanyClientId){
			$result = mysqli_query($this->connection,"SELECT * FROM bills WHERE id='".$serviceCompanyClientId."' ");
			$serviceCompanyClient = mysqli_fetch_assoc($result);
			if($result){
				return $serviceCompanyClient;
			}else{
				return false;
			}
		}

		function addBill($name, $fileExtension, $billNumber, $generationDate, $preparatedDate, $expirationDate, $issueDate, $sendingDate, $collectingDate, $observations, $serviceCompanyClientId){
			if($generationDate == null){
				$generationDate="NULL";
			} else {
				$generationDate="'".$generationDate."'";
			}
			if($issueDate == null){
				$issueDate="NULL";
			} else {
				$issueDate="'".$issueDate."'";
			}
			if($preparatedDate == null){
				$preparatedDate="NULL";
			} else {
				$preparatedDate="'".$preparatedDate."'";
			}
			if($expirationDate == null){
				$expirationDate="NULL";
			} else {
				$expirationDate="'".$expirationDate."'";
			}
			if($sendingDate == null){
				$sendingDate="NULL";
			} else {
				$sendingDate="'".$sendingDate."'";
			}
			if($collectingDate == null){
				$collectingDate="NULL";
			} else {
				$collectingDate="'".$collectingDate."'";
			}
			$registrationDate = date('Y-m-d H:i:s');
			$result = mysqli_query($this->connection, "INSERT INTO `bills`(`name`, `file_extension`, `bill_number`, `generation_date`, `preparated_date`, `issue_date`, `expiration_date`, `sending_date`, `collecting_date`, `observations`, `service_company_client_id`) VALUES ('".$name."','".$fileExtension."','".$billNumber."','".$registrationDate."',".$preparatedDate.",".$expirationDate.",".$issueDate.",".$sendingDate.",".$collectingDate.",'".$observations."','".$serviceCompanyClientId."')");
			
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function issueBill($id, $operationDate, $expirationDate){
			// $result = mysqli_query($this->connection, "UPDATE bills SET bills. `name`='".$name."', bills. `file_extension`='".$fileExtension."', bills.issue_date = '".date("Y-m-d H:i:s")."',bills.`bill_number`=(SELECT(SELECT(CASE WHEN (SELECT count(*) FROM bills b2, service_company_client scc2, service_company sc2 WHERE b2.service_company_client_id=scc2.id AND scc2.service_company_id=sc2.id AND NOT b2.bill_number='' AND YEAR(b2.generation_date)=year(bills.generation_date) AND sc2.company_id=sc.company_id) THEN(SELECT MAX(b3.bill_number) FROM bills b3, service_company_client scc3, service_company sc3 WHERE b3.service_company_client_id=scc3.id AND scc3.service_company_id=sc3.id AND NOT b3.bill_number='' AND YEAR(b3.generation_date)=year(bills.generation_date) AND sc3.company_id=sc.company_id) + 1 ELSE 1 END)) FROM bills b, service_company_client scc, service_company sc WHERE b.service_company_client_id=scc.id AND scc.service_company_id=sc.id AND b. `id`='".$id."') WHERE bills. `id`='".$id."'");
			// $result = mysqli_query($this->connection, "SELECT bills.operation_date FROM bills WHERE id='".$id."'");
			// $generationDate = mysqli_fetch_assoc($result)["operation_date"];
			$result = mysqli_query($this->connection,

			"SELECT(
				SELECT(
					CASE WHEN (
						SELECT count(*) FROM bills b2, service_company_client scc2, service_company sc2 
						WHERE b2.service_company_client_id=scc2.id 
						AND scc2.service_company_id=sc2.id 
						AND NOT b2.bill_number='' 
						AND YEAR(b2.operation_date)=year('".$operationDate."') 
						AND sc2.company_id=sc.company_id
						AND b2.bill_id IS NULL
					)
					THEN(
						SELECT MAX(b3.bill_number) 
						FROM bills b3, service_company_client scc3, service_company sc3 
						WHERE b3.service_company_client_id=scc3.id 
						AND scc3.service_company_id=sc3.id 
						AND NOT b3.bill_number='' 
						AND YEAR(b3.operation_date)=year('".$operationDate."') 
						AND sc3.company_id=sc.company_id
						AND b3.bill_id IS NULL
					) + 1 ELSE 1 END
				)
			) AS bill_number 
			FROM bills b, service_company_client scc, service_company sc 
			WHERE b.service_company_client_id=scc.id 
			AND scc.service_company_id=sc.id 
			AND b. `id`='".$id."'");
			
			$billNumber = mysqli_fetch_assoc($result)["bill_number"];
			$result = mysqli_query($this->connection, "SELECT bills.id, bills.name, bills.file_extension, bills.bill_number, bills.generation_date, bills.operation_date, bills.reference_date,
			DATE_FORMAT(bills.generation_date, '%M') AS generation_date_month, bills.issue_date, bills.preparated_date,
			bills.expiration_date, bills.sending_date, bills.observations, bills.collecting_date, services.name AS service_name, services.acronym AS service_acronym,
			companies.id AS company_id, companies.name AS company_name, companies.image AS company_image, companies.cif AS company_cif, companies.address AS
			company_address, companies.postal_code AS company_postal_code, companies.capital AS company_capital, companies.city AS company_city, companies.country AS company_country, companies.phone AS company_phone, 
			companies.social_object AS company_social_object, companies.observations AS company_observations, companies.email AS company_email, companies.iban AS company_iban, companies.acronym AS company_acronym,
			clients.name AS client_name, clients.cnmv AS client_cnmv, clients.cif AS client_cif, clients.phone AS client_phone, clients.address AS client_address, clients.web AS client_web, clients.postal_code AS
			client_postal_code, clients.city AS client_city, clients.country AS client_country, clients.accounting_account AS client_accounting_account, clients.iban AS client_iban,
			clients.commercial_register AS client_commercial_register, clients.social_object AS client_social_object, clients.observations AS client_observations, clients.drive AS client_drive, 
			service_company_client.id AS service_company_client_id FROM bills, `services`, `companies`, `clients`, service_company,
			service_company_client WHERE
			bills.service_company_client_id=service_company_client.id AND
			service_company_client.service_company_id=service_company.id AND service_company.service_id=services.id AND
			service_company.company_id=companies.id AND service_company_client.client_id=clients.id AND bills.id='".$id."'");
			$bill = mysqli_fetch_assoc($result);
			$result = mysqli_query($this->connection, 
			"UPDATE bills SET bills.issue_date = '".date("Y-m-d H:i:s")."', bills.`bill_number`= '".$billNumber."', bills.`operation_date`= '".$operationDate."',
			bills.`expiration_date`= '".$expirationDate."', bills.`service_name`='".$bill["service_name"]."', bills.`service_acronym`='".$bill["service_acronym"]."',
			bills.`company_name`='".$bill["company_name"]."', bills.`company_cif`='".$bill["company_cif"]."', bills.`company_address`='".$bill["company_address"]."', 
			bills.`company_postal_code`='".$bill["company_postal_code"]."', bills.`company_phone`='".$bill["company_phone"]."', bills.`company_capital`='".$bill["company_capital"]."',
			bills.`company_city`='".$bill["company_city"]."', bills.`company_country`='".$bill["company_country"]."', bills.`company_email`='".$bill["company_email"]."', bills.`company_iban`='".$bill["company_iban"]."',
			bills.`company_acronym`='".$bill["company_acronym"]."', bills.`company_social_object`='".$bill["company_social_object"]."', bills.`company_observations`='".$bill["company_observations"]."',
			bills.`client_name`='".$bill["client_name"]."', bills.`client_cnmv`='".$bill["client_cnmv"]."', bills.`client_cif`='".$bill["client_cif"]."', bills.`client_phone`='".$bill["client_phone"]."',
			bills.`client_address`='".$bill["client_address"]."', bills.`client_postal_code`='".$bill["client_postal_code"]."', bills.`client_web`='".$bill["client_web"]."',
			bills.`client_city`='".$bill["client_city"]."', bills.`client_country`='".$bill["client_country"]."', bills.`client_accounting_account`='".$bill["client_accounting_account"]."',
			bills.`client_iban`='".$bill["client_iban"]."', bills.`client_commercial_register`='".$bill["client_commercial_register"]."', bills.`client_social_object`='".$bill["client_social_object"]."',
			bills.`client_observations`='".$bill["client_observations"]."', bills.`client_drive`='".$bill["client_drive"]."'  WHERE bills. `id`='".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		
		function billSubservicesOfBill($id){
			// $result = mysqli_query($this->connection, "UPDATE bills SET bills. `name`='".$name."', bills. `file_extension`='".$fileExtension."', bills.issue_date = '".date("Y-m-d H:i:s")."',bills.`bill_number`=(SELECT(SELECT(CASE WHEN (SELECT count(*) FROM bills b2, service_company_client scc2, service_company sc2 WHERE b2.service_company_client_id=scc2.id AND scc2.service_company_id=sc2.id AND NOT b2.bill_number='' AND YEAR(b2.generation_date)=year(bills.generation_date) AND sc2.company_id=sc.company_id) THEN(SELECT MAX(b3.bill_number) FROM bills b3, service_company_client scc3, service_company sc3 WHERE b3.service_company_client_id=scc3.id AND scc3.service_company_id=sc3.id AND NOT b3.bill_number='' AND YEAR(b3.generation_date)=year(bills.generation_date) AND sc3.company_id=sc.company_id) + 1 ELSE 1 END)) FROM bills b, service_company_client scc, service_company sc WHERE b.service_company_client_id=scc.id AND scc.service_company_id=sc.id AND b. `id`='".$id."') WHERE bills. `id`='".$id."'");
			$result = mysqli_query($this->connection, "SELECT service_company_client_subservice.`id`
				,service_company_client_subservice.`service_company_client_id`
				,service_company_client_subservice.`subservice_id`
				,service_company_client_subservice.`name`
				,`iva-types`.`percentage` AS `iva-type_percentage`
				,service_company_client_subservice.`units`
				,service_company_client_subservice.`bonus`
				,service_company_client_subservice.`iva-type_id`
				,service_company_client_subservice.`description`
				,service_company_client_subservice.`periodicity_id`
				,service_company_client_subservice.`billing_date`
				,service_company_client_subservice.`main_employee_id`
				,service_company_client_subservice.`secondary_employee_id`
				,subservices.name AS subservice_name
				,
				(
					CASE WHEN service_company_client_subservice.periodicity_id = 1 AND service_company_client_subservice.snap_date IS NOT NULL THEN service_company_client_subservice.price ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 2
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/12 END
					) ELSE 0 END
				)
				-- + (
				-- 	CASE WHEN service_company_client_subservice.periodicity_id = 3
				-- 	THEN (
				-- 		CASE WHEN (
				-- 			(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
				-- 			AND
				-- 			(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
				-- 		) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
				-- 		WHEN (
				-- 			MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
				-- 		) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
				-- 		WHEN (
				-- 			MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
				-- 			AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
				-- 		) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/4/DAY(LAST_DAY(service_company_client_subservice.starting_date))
				-- 		ELSE service_company_client_subservice.price/4 END
				-- 	) ELSE 0 END
				-- )
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 3
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)<=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR), 1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL 
											 1 DAY  END, 
								service_company_client_subservice.starting_date) +1) * service_company_client_subservice.price/
										(DATEDIFF(CASE WHEN QUARTER(service_company_client_subservice.starting_date) = 4
											  THEN MAKEDATE(YEAR(service_company_client_subservice.starting_date + INTERVAL 1 YEAR),1)  + INTERVAL 4 QUARTER - INTERVAL    1 DAY
            								  ELSE MAKEDATE(YEAR(service_company_client_subservice.starting_date),1)+ INTERVAL QUARTER(service_company_client_subservice.starting_date) - 0 QUARTER - INTERVAL  1 DAY  END , 
											  MAKEDATE(YEAR(service_company_client_subservice.starting_date), 1) + INTERVAL QUARTER(service_company_client_subservice.starting_date) QUARTER - INTERVAL 1 QUARTER )+1)/4
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/12/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/4 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 4 
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/2/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price/2 END
					) ELSE 0 END
				)
				+ (
					CASE WHEN service_company_client_subservice.periodicity_id = 5
					THEN (
						CASE WHEN (
							(MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date))
							AND
							(MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date))
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.starting_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.starting_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(LAST_DAY(service_company_client_subservice.starting_date), service_company_client_subservice.starting_date)+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						WHEN (
							MONTH(service_company_client_subservice.ending_date)=MONTH(bills.reference_date)
							AND YEAR(service_company_client_subservice.ending_date)=YEAR(bills.reference_date)
						) THEN (DATEDIFF(service_company_client_subservice.ending_date, ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1))+1) * service_company_client_subservice.price/DAY(LAST_DAY(service_company_client_subservice.starting_date))
						ELSE service_company_client_subservice.price END
					) ELSE 0 END
				) AS price
				FROM service_company_client_subservice, service_company_client, subservices, bills, `iva-types`
				WHERE subservices.id=service_company_client_subservice.subservice_id
				AND service_company_client_subservice.service_company_client_id=service_company_client.id
				AND bills.service_company_client_id=service_company_client.id
				AND service_company_client_subservice.service_company_client_id=bills.service_company_client_id
				AND service_company_client_subservice.`iva-type_id`=`iva-types`.id
				AND 
				(
					(
						service_company_client_subservice.periodicity_id=1 
						AND service_company_client_subservice.snap_date IS NOT NULL
						AND (
							MONTH(service_company_client_subservice.billing_date)=MONTH(bills.reference_date) 
							AND YEAR(service_company_client_subservice.billing_date)=YEAR(bills.reference_date)
						)
						AND service_company_client_subservice.id NOT IN (SELECT service_company_client_subservice_id FROM billing)
					)
					OR (
						service_company_client_subservice.periodicity_id=2
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
							-- (MONTH(service_company_client_subservice.starting_date)<=MONTH(bills.reference_date) 
							-- AND YEAR(service_company_client_subservice.starting_date)<=YEAR(bills.reference_date))
							-- AND
							-- (MONTH(service_company_client_subservice.ending_date)>=MONTH(bills.reference_date) 
							-- AND YEAR(service_company_client_subservice.ending_date)>=YEAR(bills.reference_date))
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND billing.bill_id = b2.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
					OR (
						service_company_client_subservice.periodicity_id=3
						AND (
							MONTH(bills.reference_date)=03 OR MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=09 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=4 
						AND (
							MONTH(bills.reference_date)=06 OR MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					) 
					OR (
						service_company_client_subservice.periodicity_id=5 
						AND (
							MONTH(bills.reference_date)=12
							-- Taking care about punctual snaps ending_date is allways null
							OR ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
						)
						AND (
							ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.starting_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
							AND
								CASE WHEN service_company_client_subservice.ending_date IS NOT NULL 
								THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(bills.reference_date, INTERVAL 1 MONTH)), 1)
								ELSE TRUE END
						)
						AND NOT(
							SELECT COUNT(*) 
							FROM billing, bills b2
							WHERE billing.service_company_client_subservice_id = service_company_client_subservice.id
							AND (
								MONTH(bills.reference_date)=MONTH(b2.reference_date)
								AND YEAR(bills.reference_date)=YEAR(b2.reference_date)
							)
						)
					)
				)
				AND service_company_client_subservice.hired = 1 AND bills.id='".$id."'"
			);
			$serviceCompanyClientSubservices = array();
			while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
				$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			}

			$query ="INSERT INTO `billing`(`name`, `price`, `bonus`, `units`, `total_amount`, `iva-type_id`, `iva-type_percentage`, `description`, `main_employee_id`, `secondary_employee_id`, `periodicity_id`, `service_company_client_subservice_id`, `bill_id`) VALUES ";
			foreach ($serviceCompanyClientSubservices as $serviceCompanyClientSubservice){
				$mainEmployeeId = $serviceCompanyClientSubservice["main_employee_id"];
				$secondaryEmployeeId = $serviceCompanyClientSubservice["secondary_employee_id"];
				if($mainEmployeeId == null){
					$mainEmployeeId="null";
				}
				if($secondaryEmployeeId == null){
					$secondaryEmployeeId="null";
				}
				$amount = $serviceCompanyClientSubservice["price"] * $serviceCompanyClientSubservice["units"] + $serviceCompanyClientSubservice["price"] * $serviceCompanyClientSubservice["units"] * $serviceCompanyClientSubservice["bonus"]/100;
				$totalAmount =  $amount + $amount * $serviceCompanyClientSubservice["iva-type_percentage"]/100;
				if($serviceCompanyClientSubservice["name"]){
					$query.="('".$serviceCompanyClientSubservice["name"]."' ,'".$serviceCompanyClientSubservice["price"]."','".$serviceCompanyClientSubservice["bonus"]."', '".$serviceCompanyClientSubservice["units"]."','".$totalAmount."' ,'".$serviceCompanyClientSubservice["iva-type_id"]."', '".$serviceCompanyClientSubservice["iva-type_percentage"]."', '".$serviceCompanyClientSubservice["description"]."', ".$mainEmployeeId.", ".$secondaryEmployeeId.", '".$serviceCompanyClientSubservice["periodicity_id"]."', '".$serviceCompanyClientSubservice["id"]."', '".$id."'),";
				}else{
					$query.="('".$serviceCompanyClientSubservice["subservice_name"]."' ,'".$serviceCompanyClientSubservice["price"]."','".$serviceCompanyClientSubservice["bonus"]."', '".$serviceCompanyClientSubservice["units"]."','".$totalAmount."' ,'".$serviceCompanyClientSubservice["iva-type_id"]."', '".$serviceCompanyClientSubservice["iva-type_percentage"]."', '".$serviceCompanyClientSubservice["description"]."', ".$mainEmployeeId.", ".$secondaryEmployeeId.", '".$serviceCompanyClientSubservice["periodicity_id"]."', '".$serviceCompanyClientSubservice["id"]."', '".$id."'),";
				}
			}
			$query = rtrim($query, ",");
			$query.=";";

			$result = mysqli_query($this->connection, $query);
			
			if($result){
				return true;
			}else{
				return $query;
			}
		}

		function setBill($id, $name, $fileExtension, $billNumber, $preparatedDate, $expirationDate, $issueDate, $sendingDate, $collectingDate, $observations, $serviceCompanyClientId){
			// $result = mysqli_query($this->connection, "UPDATE `clients` SET `name`='".$name."', `cnmv`='".$cnmv."',`cif`='".$cif."', `phone`='".$phone."', `image`='".$image."', `address`='".$address."', `postal_code`='".$postalCode."',`web`='".$web."', `city`='".$city."', `country`='".$country."',`commercial_register`='".$commercialRegister."',`social_object`='".$socialObject."',`observations`='".$observations."', `drive`='".$drive."' WHERE `id`='".$id."'");
			if($issueDate == null){
				$issueDate="NULL";
			} else {
				$issueDate="'".$issueDate."'";
			}
			if($preparatedDate == null){
				$preparatedDate="NULL";
			} else {
				$preparatedDate="'".$preparatedDate."'";
			}
			if($expirationDate == null){
				$expirationDate="NULL";
			} else {
				$expirationDate="'".$expirationDate."'";
			}
			if($sendingDate == null){
				$sendingDate="NULL";
			} else {
				$sendingDate="'".$sendingDate."'";
			}
			if($collectingDate == null){
				$collectingDate="NULL";
			} else {
				$collectingDate="'".$collectingDate."'";
			}
			
			$result = mysqli_query($this->connection, "UPDATE bills SET `name`='".$name."',`file_extension`='".$fileExtension."',`bill_number`='".$billNumber."',`preparated_date`=".$preparatedDate.",`expiration_date`=".$expirationDate.",`issue_date`=".$issueDate.",`sending_date`=".$sendingDate.",`collecting_date`=".$collectingDate.",`observations`='".$observations."',`service_company_client_id`='".$serviceCompanyClientId."' WHERE `id`='".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function sendBill($id){
			$result = mysqli_query($this->connection, "UPDATE bills SET `sending_date`='".date('Y-m-d H:i:s')."' WHERE `id`='".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}


		function refreshPendingBills(){
			$result = mysqli_query($this->connection,
				"SELECT service_company_client.id,
				(CASE WHEN service_company_client_subservice.billing_date IS NOT NULL THEN service_company_client_subservice.billing_date ELSE CURDATE() END) 
				AS billing_date, service_company_client_subservice.periodicity_id
				FROM service_company_client, service_company_client_subservice 
				WHERE service_company_client.id = service_company_client_subservice.service_company_client_id
				AND service_company_client_subservice.hired = 1
				-- Comprueba los subservicios
				AND (
						(
							service_company_client_subservice.periodicity_id = 1
							AND service_company_client_subservice.snap_date IS NOT NULL
						)
							OR service_company_client_subservice.periodicity_id = 2
							OR service_company_client_subservice.periodicity_id = 3
							OR service_company_client_subservice.periodicity_id = 4
							OR service_company_client_subservice.periodicity_id = 5
					)
				AND NOT
				(
					-- Comprueba si están facturados los subservicios en el mes actual
					SELECT count( * )
					FROM bills, billing
					WHERE billing.bill_id = bills.id AND billing.service_company_client_subservice_id=service_company_client_subservice.id
					
					AND (
						service_company_client_subservice.periodicity_id = 1
						OR (
							(
								service_company_client_subservice.periodicity_id = 2
								OR service_company_client_subservice.periodicity_id = 3
								OR service_company_client_subservice.periodicity_id = 4
								OR service_company_client_subservice.periodicity_id = 5
							)
							AND(
								YEAR(bills.generation_date) = YEAR(CURDATE())
								AND MONTH(bills.generation_date) = MONTH(CURDATE())
							)
						)
					)
				)
				AND NOT 
				(
					-- Comprueba que no haya una línea de factura ya creada para el mes corriente
					SELECT COUNT(*)
					FROM bills
					WHERE service_company_client.id = bills.service_company_client_id
					AND (
						(
							service_company_client_subservice.periodicity_id = 1
							AND YEAR(bills.reference_date) = YEAR(service_company_client_subservice.billing_date)
							AND MONTH(bills.reference_date) = MONTH(service_company_client_subservice.billing_date)
							AND service_company_client_subservice.snap_date IS NOT NULL
						)
						OR (
							(
								service_company_client_subservice.periodicity_id = 2
								OR service_company_client_subservice.periodicity_id = 3
								OR service_company_client_subservice.periodicity_id = 4
								OR service_company_client_subservice.periodicity_id = 5
							)
							AND(
								YEAR(bills.generation_date) = YEAR(CURDATE())
								AND MONTH(bills.generation_date) = MONTH(CURDATE())
							)
						)
					)
					AND bills.issue_date IS NULL
				)
				-- Comprueba que sea el mes correspondiente en el que debe aparecer la factura
				AND(
					(
						service_company_client_subservice.periodicity_id = 1
						AND ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.billing_date, INTERVAL 1 MONTH)), 1)<=ADDDATE(LAST_DAY(SUBDATE(CURDATE(), INTERVAL 1 MONTH)), 1)
					)
					OR (
						service_company_client_subservice.periodicity_id = 2
						AND (CASE WHEN service_company_client_subservice.ending_date IS NOT NULL THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(CURDATE(), INTERVAL 1 MONTH)), 1) ELSE TRUE END)
					)
					OR (
						service_company_client_subservice.periodicity_id = 3
						AND (CASE WHEN service_company_client_subservice.ending_date IS NOT NULL THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(CURDATE(), INTERVAL 1 MONTH)), 1) ELSE TRUE END)
						AND (
							MONTH(CURDATE()) = 03
							OR MONTH(CURDATE()) = 06
							OR MONTH(CURDATE()) = 09
							OR MONTH(CURDATE()) = 12
							OR (
								YEAR(service_company_client_subservice.ending_date) = YEAR(CURDATE())
								AND MONTH(service_company_client_subservice.ending_date) = MONTH(CURDATE())
							)
						)
					)
					OR (
						service_company_client_subservice.periodicity_id = 4
						AND (CASE WHEN service_company_client_subservice.ending_date IS NOT NULL THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(CURDATE(), INTERVAL 1 MONTH)), 1) ELSE TRUE END)
						AND (
							MONTH(CURDATE()) = 06
							OR MONTH(CURDATE()) = 12
							OR (
								YEAR(service_company_client_subservice.ending_date) = YEAR(CURDATE())
								AND MONTH(service_company_client_subservice.ending_date) = MONTH(CURDATE())
							)
						)
					)
					OR (
						service_company_client_subservice.periodicity_id = 5
						AND (CASE WHEN service_company_client_subservice.ending_date IS NOT NULL THEN ADDDATE(LAST_DAY(SUBDATE(service_company_client_subservice.ending_date, INTERVAL 1 MONTH)), 1)>=ADDDATE(LAST_DAY(SUBDATE(CURDATE(), INTERVAL 1 MONTH)), 1) ELSE TRUE END)

						AND (
							MONTH(CURDATE()) = 12
							OR (
								YEAR(service_company_client_subservice.ending_date) = YEAR(CURDATE())
								AND MONTH(service_company_client_subservice.ending_date) = MONTH(CURDATE())
							)
						)
					)
				)
				GROUP BY YEAR((CASE WHEN service_company_client_subservice.billing_date IS NOT NULL THEN service_company_client_subservice.billing_date ELSE CURDATE() END)), MONTH((CASE WHEN service_company_client_subservice.billing_date IS NOT NULL THEN service_company_client_subservice.billing_date ELSE CURDATE() END)), service_company_client_subservice.service_company_client_id
			"
			);
			
			$serviceCompanyClientSubservices = array();
			while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result ) ){
				$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			}
			
			$query ="INSERT INTO bills (`name`, `file_extension`, `bill_number`, `generation_date`, `reference_date`, `preparated_date`, `issue_date`, `expiration_date`, `sending_date`, `collecting_date`, `observations`, `service_company_client_id`) VALUES";
			foreach ($serviceCompanyClientSubservices as $serviceCompanyClientSubservice){
				if($serviceCompanyClientSubservice["periodicity_id"] == 1){
					$query.="('','',null, '".date('Y-m-d H:i:s')."','".$serviceCompanyClientSubservice["billing_date"]."',null,null,null,null,null,'',".$serviceCompanyClientSubservice["id"]."),";
				} else {
					$query.="('','',null, '".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."',null,null,null,null,null,'',".$serviceCompanyClientSubservice["id"]."),";
				}
			}
			$query = rtrim($query, ",");
			$query.=";";

			$result = mysqli_query($this->connection, $query);

			if($result){
				return $query;
			}else{
				return $query;
			}

			// return $serviceCompanyClientIds;
		}

		function getLastOperationDateOfCorrelativeBill($id){
			$result = mysqli_query($this->connection,
				"SELECT (
					SELECT(
						CASE WHEN (
							SELECT  COUNT(*)
							FROM bills b2, service_company_client scc2, service_company sc2
							WHERE b2.service_company_client_id=scc2.id
							AND scc2.service_company_id=sc2.id
							AND NOT b2.bill_number=''
							AND sc2.company_id=sc.company_id
							AND b2.bill_id IS NULL
						)
				
						THEN(
							SELECT MAX(b3.operation_date)
							FROM bills b3, service_company_client scc3, service_company sc3
							WHERE b3.service_company_client_id=scc3.id
							AND scc3.service_company_id=sc3.id
							AND NOT b3.bill_number=''
							AND sc3.company_id=sc.company_id
							AND b3.bill_id IS NULL
						) ELSE NULL END
					)
				)
				AS last_operation_date
				FROM bills b, service_company_client scc, service_company sc
				WHERE b.service_company_client_id=scc.id
				AND scc.service_company_id=sc.id
				AND b. `id`='".$id."'"
			);
			$bill = mysqli_fetch_assoc($result);
			if($result){
				return $bill;
			}else{
				return false;
			}
		}

		function nullifyBill($id, $operationDate){
			$result = mysqli_query($this->connection, "UPDATE `bills` SET `amending_date`='".date('Y-m-d H:i:s')."' WHERE `id` = '".$id."'");
			$result = mysqli_query($this->connection, "SELECT * FROM `bills` WHERE id='".$id."'");
			$bill = mysqli_fetch_assoc($result);
			$result = mysqli_query($this->connection,
				"SELECT(
					SELECT(
						CASE WHEN (
							SELECT  COUNT(*)
							FROM bills b2, service_company_client scc2, service_company sc2
							WHERE b2.service_company_client_id=scc2.id
							AND scc2.service_company_id=sc2.id
							AND NOT b2.bill_number=''
							AND YEAR(b2.operation_date)=year('".$bill["operation_date"]."')
							AND sc2.company_id=sc.company_id
							AND b2.bill_id IS NOT NULL
						) THEN(
							SELECT  MAX(b3.bill_number)
							FROM bills b3, service_company_client scc3, service_company sc3
							WHERE b3.service_company_client_id=scc3.id
							AND scc3.service_company_id=sc3.id
							AND NOT b3.bill_number=''
							AND YEAR(b3.operation_date)=year('".$bill["operation_date"]."')
							AND sc3.company_id=sc.company_id
							AND b3.bill_id IS NOT NULL
						) + 1 ELSE 1 END
					)
				) AS bill_number
				FROM bills b, service_company_client scc, service_company sc
				WHERE b.service_company_client_id=scc.id
				AND scc.service_company_id=sc.id
				AND b. `id`='".$id."'"
			);
			$billNumber = mysqli_fetch_assoc($result)["bill_number"];

			if($bill["reference_date"] == null){
				$bill["reference_date"]="NULL";
			} else {
				$bill["reference_date"]="'".$bill["reference_date"]."'";
			}
			if($operationDate == null){
				$operationDate="NULL";
			} else {
				$operationDate="'".$operationDate."'";
			}
			$result = mysqli_query($this->connection, "INSERT INTO `bills`(`name`, `file_extension`, `bill_number`, `generation_date`, `reference_date`, `operation_date`, `preparated_date`, `issue_date`, `expiration_date`, `amending_date`, `sending_date`, `recording_date`, `collecting_date`, `observations`, `service_name`, `service_acronym`, `company_name`, `company_cif`, `company_address`, `company_postal_code`, `company_phone`, `company_capital`, `company_city`, `company_country`, `company_email`, `company_iban`, `company_acronym`, `company_social_object`, `company_observations`, `client_name`, `client_cnmv`, `client_cif`, `client_phone`, `client_address`, `client_postal_code`, `client_web`, `client_city`, `client_country`, `client_accounting_account`, `client_iban`, `client_commercial_register`, `client_social_object`, `client_observations`, `client_drive`, `service_company_client_id`, `bill_id`)
				VALUES ('".$bill["name"]."', '".$bill["file_extension"]."', '".$billNumber."', '".date('Y-m-d H:i:s')."', ".$bill["reference_date"].", ".$operationDate.", null, '".date('Y-m-d H:i:s')."', null, null, null, null, null, '".$bill["observations"]."', '".$bill["service_name"]."', '".$bill["service_acronym"]."', '".$bill["company_name"]."', '".$bill["company_cif"]."', '".$bill["company_address"]."', '".$bill["company_postal_code"]."', '".$bill["company_phone"]."', '".$bill["company_capital"]."', '".$bill["company_city"]."', '".$bill["company_country"]."', '".$bill["company_email"]."', '".$bill["company_iban"]."', '".$bill["company_acronym"]."', '".$bill["company_social_object"]."', '".$bill["company_observations"]."', '".$bill["client_name"]."', '".$bill["client_cnmv"]."', '".$bill["client_cif"]."', '".$bill["client_phone"]."', '".$bill["client_address"]."', '".$bill["client_postal_code"]."', '".$bill["client_web"]."', '".$bill["client_city"]."', '".$bill["client_country"]."', '".$bill["client_accounting_account"]."', '".$bill["client_iban"]."', '".$bill["client_commercial_register"]."', '".$bill["client_social_object"]."', '".$bill["client_observations"]."', '".$bill["client_drive"]."', '".$bill["service_company_client_id"]."', '".$bill["id"]."')");
			$result = mysqli_query($this->connection,"SELECT * FROM `billing` WHERE bill_id='".$id."'");
			$billing = array();
			while($billingRow =  mysqli_fetch_assoc($result)){
				if($billingRow["main_employee_id"] == null){
					$billingRow["main_employee_id"]="null";
				}
				if($billingRow["secondary_employee_id"] == null){
					$billingRow["secondary_employee_id"]="null";
				}
				$billing[] = $billingRow;
			}

			// Get last bill
			$result=mysqli_query($this->connection,"SELECT * FROM `bills` ORDER BY `generation_date` DESC LIMIT 1"); 
			$bill = mysqli_fetch_assoc($result);

			$query ="INSERT INTO billing (`name`, `price`, `bonus`, `units`, `total_amount`, `iva-type_id`, `iva-type_percentage`, `description`, `periodicity_id`, `main_employee_id`, `secondary_employee_id`, `service_company_client_subservice_id`, `bill_id`) VALUES";
			foreach ($billing as $billingRow){
				$query.="('".$billingRow["name"]."', '".($billingRow["price"]*-1)."', '".$billingRow["bonus"]."', '".$billingRow["units"]."', '".($billingRow["total_amount"]*-1)."', '".$billingRow["iva-type_id"]."', '".$billingRow["iva-type_percentage"]."', '".$billingRow["description"]."', '".$billingRow["periodicity_id"]."', ".$billingRow["main_employee_id"].", ".$billingRow["secondary_employee_id"].", '".$billingRow["service_company_client_subservice_id"]."', '".$bill["id"]."'),";
			}
			$query = rtrim($query, ",");
			$query.=";";

			$result = mysqli_query($this->connection, $query);

			if($bill["generation_date"] == null){
				$bill["generation_date"]="NULL";
			} else {
				$bill["generation_date"]="'".$bill["generation_date"]."'";
			}
			if($bill["reference_date"] == null){
				$bill["reference_date"]="NULL";
			} else {
				$bill["reference_date"]="'".$bill["reference_date"]."'";
			}

			$query = "INSERT INTO bills (`name`, `file_extension`, `bill_number`, `generation_date`, `reference_date`, `preparated_date`, `issue_date`, `expiration_date`, `sending_date`, `collecting_date`, `observations`, `service_company_client_id`) VALUES ('','',null, ".$bill["generation_date"].",".$bill["reference_date"].",null,null,null,null,null,'',".$bill["service_company_client_id"].")";

			
			$result = mysqli_query($this->connection, $query);


			if($result){
				return true;
			}else{
				return false;
			}


			// $serviceCompanyClientSubservices = array();
			// while($serviceCompanyClientSubservice =  mysqli_fetch_assoc($result)){
			// 	$serviceCompanyClientSubservices[] = $serviceCompanyClientSubservice;
			// }

			// $query ="INSERT INTO bills (`name`, `file_extension`, `bill_number`, `generation_date`, `reference_date`, `preparated_date`, `issue_date`, `expiration_date`, `sending_date`, `collecting_date`, `observations`, `service_company_client_id`) VALUES";
			// foreach ($serviceCompanyClientSubservices as $serviceCompanyClientSubservice){
			// 	if($serviceCompanyClientSubservice["periodicity_id"] == 1){
			// 		$query.="('','',null, '".date('Y-m-d H:i:s')."','".$serviceCompanyClientSubservice["billing_date"]."',null,null,null,null,null,'',".$serviceCompanyClientSubservice["id"]."),";
			// 	} else {
			// 		$query.="('','',null, '".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."',null,null,null,null,null,'',".$serviceCompanyClientSubservice["id"]."),";
			// 	}
			// }
			// $query = rtrim($query, ",");
			// $query.=";";

			// $result = mysqli_query($this->connection, $query);

			// if($result){
			// 	return true;
			// }else{
			// 	return false;
			// }
		}

		// function recordBills(){
		// 	$result = mysqli_query($this->connection, "SELECT * FROM `bills` WHERE issue_date IS NOT NULL AND recording_date IS NULL");

		// 	$query = "INSERT INTO `bills`(`id`, `name`, `file_extension`, `bill_number`, `generation_date`, `reference_date`, `operation_date`, `preparated_date`, `issue_date`, `expiration_date`, `amending_date`, `sending_date`, `recording_date`, `collecting_date`, `observations`, `service_name`, `service_acronym`, `company_name`, `company_cif`, `company_address`, `company_postal_code`, `company_phone`, `company_capital`, `company_city`, `company_country`, `company_email`, `company_iban`, `company_acronym`, `company_social_object`, `company_observations`, `client_name`, `client_cnmv`, `client_cif`, `client_phone`, `client_address`, `client_postal_code`, `client_web`, `client_city`, `client_country`, `client_accounting_account`, `client_iban`, `client_commercial_register`, `client_social_object`, `client_observations`, `client_drive`, `service_company_client_id`, `bill_id`) VALUES ";
			
		// 	while($bill =  mysqli_fetch_assoc($result)){
		// 		if($bill["generation_date"] == null){
		// 			$bill["generation_date"] ="NULL";
		// 		} else {
		// 			$bill["generation_date"] ="'".$bill["generation_date"]."'";
		// 		}
		// 		if($bill["reference_date"] == null){
		// 			$bill["reference_date"] ="NULL";
		// 		} else {
		// 			$bill["reference_date"] ="'".$bill["reference_date"]."'";
		// 		}
		// 		if($bill["operation_date"] == null){
		// 			$bill["operation_date"] ="NULL";
		// 		} else {
		// 			$bill["operation_date"] ="'".$bill["operation_date"]."'";
		// 		}
		// 		if($bill["preparated_date"] == null){
		// 			$bill["preparated_date"] ="NULL";
		// 		} else {
		// 			$bill["preparated_date"] ="'".$bill["preparated_date"]."'";
		// 		}
		// 		if($bill["issue_date"] == null){
		// 			$bill["issue_date"] ="NULL";
		// 		} else {
		// 			$bill["issue_date"] ="'".$bill["issue_date"]."'";
		// 		}
		// 		if($bill["expiration_date"] == null){
		// 			$bill["expiration_date"] ="NULL";
		// 		} else {
		// 			$bill["expiration_date"] ="'".$bill["expiration_date"]."'";
		// 		}
		// 		if($bill["amending_date"] == null){
		// 			$bill["amending_date"] ="NULL";
		// 		} else {
		// 			$bill["amending_date"] ="'".$bill["amending_date"]."'";
		// 		}
		// 		if($bill["sending_date"] == null){
		// 			$bill["sending_date"] ="NULL";
		// 		} else {
		// 			$bill["sending_date"] ="'".$bill["sending_date"]."'";
		// 		}
		// 		if($bill["recording_date"] == null){
		// 			$bill["recording_date"] ="NULL";
		// 		} else {
		// 			$bill["recording_date"] ="'".$bill["recording_date"]."'";
		// 		}
		// 		if($bill["collecting_date"] == null){
		// 			$bill["collecting_date"] ="NULL";
		// 		} else {
		// 			$bill["collecting_date"] ="'".$bill["collecting_date"]."'";
		// 		}
		// 		if($bill["bill_id"] == null){
		// 			$bill["bill_id"] ="NULL";
		// 		} else {
		// 			$bill["bill_id"] ="'".$bill["bill_id"]."'";
		// 		}
		// 		$query.="('".$bill["id"]."', '".$bill["name"]."', '".$bill["file_extension"]."', '".$bill["bill_number"]."', ".$bill["generation_date"].", ".$bill["reference_date"].", ".$bill["operation_date"].", ".$bill["preparated_date"].", ".$bill["issue_date"].", ".$bill["expiration_date"].", ".$bill["amending_date"].", ".$bill["sending_date"].", '".date('Y-m-d H:i:s')."', ".$bill["collecting_date"].", '".$bill["observations"]."', '".$bill["service_name"]."', '".$bill["service_acronym"]."', '".$bill["company_name"]."', '".$bill["company_cif"]."', '".$bill["company_address"]."', '".$bill["company_postal_code"]."', '".$bill["company_phone"]."', '".$bill["company_capital"]."', '".$bill["company_city"]."', '".$bill["company_country"]."', '".$bill["company_email"]."', '".$bill["company_iban"]."', '".$bill["company_acronym"]."', '".$bill["company_social_object"]."', '".$bill["company_observations"]."', '".$bill["client_name"]."', '".$bill["client_cnmv"]."', '".$bill["client_cif"]."', '".$bill["client_phone"]."', '".$bill["client_address"]."', '".$bill["client_postal_code"]."', '".$bill["client_web"]."', '".$bill["client_city"]."', '".$bill["client_country"]."', '".$bill["client_accounting_account"]."', '".$bill["client_iban"]."', '".$bill["client_commercial_register"]."', '".$bill["client_social_object"]."', '".$bill["client_observations"]."', '".$bill["client_drive"]."', '".$bill["service_company_client_id"]."', ".$bill["bill_id"]."),";
		// 	}

		// 	$query = rtrim($query, ",");
		// 	$query.="ON DUPLICATE KEY UPDATE name=VALUES(name),	file_extension=VALUES(file_extension),	bill_number=VALUES(bill_number),	generation_date=VALUES(generation_date),	reference_date=VALUES(reference_date),	operation_date=VALUES(operation_date),	preparated_date=VALUES(preparated_date),	issue_date=VALUES(issue_date),	expiration_date=VALUES(expiration_date),	amending_date=VALUES(amending_date),	sending_date=VALUES(sending_date),	recording_date=VALUES(recording_date),	collecting_date=VALUES(collecting_date),	observations=VALUES(observations),	service_name=VALUES(service_name),	service_acronym=VALUES(service_acronym),	company_name=VALUES(company_name),	company_cif=VALUES(company_cif),	company_address=VALUES(company_address),	company_postal_code=VALUES(company_postal_code),	company_phone=VALUES(company_phone),	company_capital=VALUES(company_capital),	company_city=VALUES(company_city),	company_country=VALUES(company_country),	company_email=VALUES(company_email),	company_iban=VALUES(company_iban),	company_acronym=VALUES(company_acronym),	company_social_object=VALUES(company_social_object),	company_observations=VALUES(company_observations),	client_name=VALUES(client_name),	client_cnmv=VALUES(client_cnmv),	client_cif=VALUES(client_cif),	client_phone=VALUES(client_phone),	client_address=VALUES(client_address),	client_postal_code=VALUES(client_postal_code),	client_web=VALUES(client_web),	client_city=VALUES(client_city),	client_country=VALUES(client_country),	client_accounting_account=VALUES(client_accounting_account),	client_iban=VALUES(client_iban),	client_commercial_register=VALUES(client_commercial_register),	client_social_object=VALUES(client_social_object),	client_observations=VALUES(client_observations),	client_drive=VALUES(client_drive),	service_company_client_id=VALUES(service_company_client_id),	bill_id=VALUES(bill_id);";

		// 	$result = mysqli_query($this->connection, $query);

		// 	if($result){
		// 		return true;
		// 	}else{
		// 		return false;
		// 	}
		// }

		function recordBillsOfCompany($companyId){
			$result = mysqli_query($this->connection, "SELECT bills.`id`, bills.`name`, bills.`file_extension`, bills.`bill_number`, bills.`generation_date`, bills.`reference_date`, bills.`operation_date`, bills.`preparated_date`, bills.`issue_date`, bills.`expiration_date`, bills.`amending_date`, bills.`sending_date`, bills.`recording_date`, bills.`collecting_date`, bills.`accounting_date`, bills.`observations`, bills.`service_name`, bills.`service_acronym`, bills.`company_name`, bills.`company_cif`, bills.`company_address`, bills.`company_postal_code`, bills.`company_phone`, bills.`company_capital`, bills.`company_city`, bills.`company_country`, bills.`company_email`, bills.`company_iban`, bills.`company_acronym`, bills.`company_social_object`, bills.`company_observations`, bills.`client_name`, bills.`client_cnmv`, bills.`client_cif`, bills.`client_phone`, bills.`client_address`, bills.`client_postal_code`, bills.`client_web`, bills.`client_city`, bills.`client_country`, bills.`client_accounting_account`, bills.`client_iban`, bills.`client_commercial_register`, bills.`client_social_object`, bills.`client_observations`, bills.`client_drive`, bills.`bank-accounting-account_id`, bills.`bank-accounting-account_name`, bills.`bank-accounting-account_number`, bills.`service_company_client_id`, bills.`bill_id` FROM `bills`, service_company_client, service_company, companies WHERE bills.service_company_client_id = service_company_client.id AND service_company_client.service_company_id=service_company.id AND service_company.company_id=companies.id AND issue_date IS NOT NULL AND recording_date IS NULL AND companies.id='".$companyId."'");

			$query = "INSERT INTO `bills`(`id`, `name`, `file_extension`, `bill_number`, `generation_date`, `reference_date`, `operation_date`, `preparated_date`, `issue_date`, `expiration_date`, `amending_date`, `sending_date`, `recording_date`, `collecting_date`, `observations`, `service_name`, `service_acronym`, `company_name`, `company_cif`, `company_address`, `company_postal_code`, `company_phone`, `company_capital`, `company_city`, `company_country`, `company_email`, `company_iban`, `company_acronym`, `company_social_object`, `company_observations`, `client_name`, `client_cnmv`, `client_cif`, `client_phone`, `client_address`, `client_postal_code`, `client_web`, `client_city`, `client_country`, `client_accounting_account`, `client_iban`, `client_commercial_register`, `client_social_object`, `client_observations`, `client_drive`, `service_company_client_id`, `bill_id`) VALUES ";
			
			while($bill =  mysqli_fetch_assoc($result)){
				if($bill["generation_date"] == null){
					$bill["generation_date"] ="NULL";
				} else {
					$bill["generation_date"] ="'".$bill["generation_date"]."'";
				}
				if($bill["reference_date"] == null){
					$bill["reference_date"] ="NULL";
				} else {
					$bill["reference_date"] ="'".$bill["reference_date"]."'";
				}
				if($bill["operation_date"] == null){
					$bill["operation_date"] ="NULL";
				} else {
					$bill["operation_date"] ="'".$bill["operation_date"]."'";
				}
				if($bill["preparated_date"] == null){
					$bill["preparated_date"] ="NULL";
				} else {
					$bill["preparated_date"] ="'".$bill["preparated_date"]."'";
				}
				if($bill["issue_date"] == null){
					$bill["issue_date"] ="NULL";
				} else {
					$bill["issue_date"] ="'".$bill["issue_date"]."'";
				}
				if($bill["expiration_date"] == null){
					$bill["expiration_date"] ="NULL";
				} else {
					$bill["expiration_date"] ="'".$bill["expiration_date"]."'";
				}
				if($bill["amending_date"] == null){
					$bill["amending_date"] ="NULL";
				} else {
					$bill["amending_date"] ="'".$bill["amending_date"]."'";
				}
				if($bill["sending_date"] == null){
					$bill["sending_date"] ="NULL";
				} else {
					$bill["sending_date"] ="'".$bill["sending_date"]."'";
				}
				if($bill["recording_date"] == null){
					$bill["recording_date"] ="NULL";
				} else {
					$bill["recording_date"] ="'".$bill["recording_date"]."'";
				}
				if($bill["collecting_date"] == null){
					$bill["collecting_date"] ="NULL";
				} else {
					$bill["collecting_date"] ="'".$bill["collecting_date"]."'";
				}
				if($bill["bill_id"] == null){
					$bill["bill_id"] ="NULL";
				} else {
					$bill["bill_id"] ="'".$bill["bill_id"]."'";
				}
				$query.="('".$bill["id"]."', '".$bill["name"]."', '".$bill["file_extension"]."', '".$bill["bill_number"]."', ".$bill["generation_date"].", ".$bill["reference_date"].", ".$bill["operation_date"].", ".$bill["preparated_date"].", ".$bill["issue_date"].", ".$bill["expiration_date"].", ".$bill["amending_date"].", ".$bill["sending_date"].", '".date('Y-m-d H:i:s')."', ".$bill["collecting_date"].", '".$bill["observations"]."', '".$bill["service_name"]."', '".$bill["service_acronym"]."', '".$bill["company_name"]."', '".$bill["company_cif"]."', '".$bill["company_address"]."', '".$bill["company_postal_code"]."', '".$bill["company_phone"]."', '".$bill["company_capital"]."', '".$bill["company_city"]."', '".$bill["company_country"]."', '".$bill["company_email"]."', '".$bill["company_iban"]."', '".$bill["company_acronym"]."', '".$bill["company_social_object"]."', '".$bill["company_observations"]."', '".$bill["client_name"]."', '".$bill["client_cnmv"]."', '".$bill["client_cif"]."', '".$bill["client_phone"]."', '".$bill["client_address"]."', '".$bill["client_postal_code"]."', '".$bill["client_web"]."', '".$bill["client_city"]."', '".$bill["client_country"]."', '".$bill["client_accounting_account"]."', '".$bill["client_iban"]."', '".$bill["client_commercial_register"]."', '".$bill["client_social_object"]."', '".$bill["client_observations"]."', '".$bill["client_drive"]."', '".$bill["service_company_client_id"]."', ".$bill["bill_id"]."),";
			}

			$query = rtrim($query, ",");
			$query.="ON DUPLICATE KEY UPDATE name=VALUES(name),	file_extension=VALUES(file_extension),	bill_number=VALUES(bill_number),	generation_date=VALUES(generation_date),	reference_date=VALUES(reference_date),	operation_date=VALUES(operation_date),	preparated_date=VALUES(preparated_date),	issue_date=VALUES(issue_date),	expiration_date=VALUES(expiration_date),	amending_date=VALUES(amending_date),	sending_date=VALUES(sending_date),	recording_date=VALUES(recording_date),	collecting_date=VALUES(collecting_date),	observations=VALUES(observations),	service_name=VALUES(service_name),	service_acronym=VALUES(service_acronym),	company_name=VALUES(company_name),	company_cif=VALUES(company_cif),	company_address=VALUES(company_address),	company_postal_code=VALUES(company_postal_code),	company_phone=VALUES(company_phone),	company_capital=VALUES(company_capital),	company_city=VALUES(company_city),	company_country=VALUES(company_country),	company_email=VALUES(company_email),	company_iban=VALUES(company_iban),	company_acronym=VALUES(company_acronym),	company_social_object=VALUES(company_social_object),	company_observations=VALUES(company_observations),	client_name=VALUES(client_name),	client_cnmv=VALUES(client_cnmv),	client_cif=VALUES(client_cif),	client_phone=VALUES(client_phone),	client_address=VALUES(client_address),	client_postal_code=VALUES(client_postal_code),	client_web=VALUES(client_web),	client_city=VALUES(client_city),	client_country=VALUES(client_country),	client_accounting_account=VALUES(client_accounting_account),	client_iban=VALUES(client_iban),	client_commercial_register=VALUES(client_commercial_register),	client_social_object=VALUES(client_social_object),	client_observations=VALUES(client_observations),	client_drive=VALUES(client_drive),	service_company_client_id=VALUES(service_company_client_id),	bill_id=VALUES(bill_id);";

			$result = mysqli_query($this->connection, $query);

			if($result){
				return true;
			}else{
				return false;
			}
		}

		function accountBills(){
			$result = mysqli_query($this->connection, "SELECT * FROM `bills` WHERE collecting_date IS NOT NULL AND accounting_date IS NULL");

			$query = "INSERT INTO `bills`(`id`, `name`, `file_extension`, `bill_number`, `generation_date`, `reference_date`, `operation_date`, `preparated_date`, `issue_date`, `expiration_date`, `amending_date`, `sending_date`, `recording_date`, `collecting_date`, `accounting_date`, `observations`, `service_name`, `service_acronym`, `company_name`, `company_cif`, `company_address`, `company_postal_code`, `company_phone`, `company_capital`, `company_city`, `company_country`, `company_email`, `company_iban`, `company_acronym`, `company_social_object`, `company_observations`, `client_name`, `client_cnmv`, `client_cif`, `client_phone`, `client_address`, `client_postal_code`, `client_web`, `client_city`, `client_country`, `client_accounting_account`, `client_iban`, `client_commercial_register`, `client_social_object`, `client_observations`, `client_drive`, `bank-accounting-account_id`, `bank-accounting-account_name`, `bank-accounting-account_number`,`service_company_client_id`, `bill_id`) VALUES ";
			
			while($bill =  mysqli_fetch_assoc($result)){
				if($bill["generation_date"] == null){
					$bill["generation_date"] ="NULL";
				} else {
					$bill["generation_date"] ="'".$bill["generation_date"]."'";
				}
				if($bill["reference_date"] == null){
					$bill["reference_date"] ="NULL";
				} else {
					$bill["reference_date"] ="'".$bill["reference_date"]."'";
				}
				if($bill["operation_date"] == null){
					$bill["operation_date"] ="NULL";
				} else {
					$bill["operation_date"] ="'".$bill["operation_date"]."'";
				}
				if($bill["preparated_date"] == null){
					$bill["preparated_date"] ="NULL";
				} else {
					$bill["preparated_date"] ="'".$bill["preparated_date"]."'";
				}
				if($bill["issue_date"] == null){
					$bill["issue_date"] ="NULL";
				} else {
					$bill["issue_date"] ="'".$bill["issue_date"]."'";
				}
				if($bill["expiration_date"] == null){
					$bill["expiration_date"] ="NULL";
				} else {
					$bill["expiration_date"] ="'".$bill["expiration_date"]."'";
				}
				if($bill["amending_date"] == null){
					$bill["amending_date"] ="NULL";
				} else {
					$bill["amending_date"] ="'".$bill["amending_date"]."'";
				}
				if($bill["sending_date"] == null){
					$bill["sending_date"] ="NULL";
				} else {
					$bill["sending_date"] ="'".$bill["sending_date"]."'";
				}
				if($bill["recording_date"] == null){
					$bill["recording_date"] ="NULL";
				} else {
					$bill["recording_date"] ="'".$bill["recording_date"]."'";
				}
				if($bill["collecting_date"] == null){
					$bill["collecting_date"] ="NULL";
				} else {
					$bill["collecting_date"] ="'".$bill["collecting_date"]."'";
				}
				if($bill["accounting_date"] == null){
					$bill["accounting_date"] ="NULL";
				} else {
					$bill["accounting_date"] ="'".$bill["accounting_date"]."'";
				}
				if($bill["bill_id"] == null){
					$bill["bill_id"] ="NULL";
				} else {
					$bill["bill_id"] ="'".$bill["bill_id"]."'";
				}
				$query.="('".$bill["id"]."', '".$bill["name"]."', '".$bill["file_extension"]."', '".$bill["bill_number"]."', ".$bill["generation_date"].", ".$bill["reference_date"].", ".$bill["operation_date"].", ".$bill["preparated_date"].", ".$bill["issue_date"].", ".$bill["expiration_date"].", ".$bill["amending_date"].", ".$bill["sending_date"].", ".$bill["recording_date"].", ".$bill["collecting_date"].", '".date('Y-m-d H:i:s')."', '".$bill["observations"]."', '".$bill["service_name"]."', '".$bill["service_acronym"]."', '".$bill["company_name"]."', '".$bill["company_cif"]."', '".$bill["company_address"]."', '".$bill["company_postal_code"]."', '".$bill["company_phone"]."', '".$bill["company_capital"]."', '".$bill["company_city"]."', '".$bill["company_country"]."', '".$bill["company_email"]."', '".$bill["company_iban"]."', '".$bill["company_acronym"]."', '".$bill["company_social_object"]."', '".$bill["company_observations"]."', '".$bill["client_name"]."', '".$bill["client_cnmv"]."', '".$bill["client_cif"]."', '".$bill["client_phone"]."', '".$bill["client_address"]."', '".$bill["client_postal_code"]."', '".$bill["client_web"]."', '".$bill["client_city"]."', '".$bill["client_country"]."', '".$bill["client_accounting_account"]."', '".$bill["client_iban"]."', '".$bill["client_commercial_register"]."', '".$bill["client_social_object"]."', '".$bill["client_observations"]."', '".$bill["client_drive"]."', '".$bill["bank-accounting-account_id"]."', '".$bill["bank-accounting-account_name"]."', '".$bill["bank-accounting-account_number"]."', '".$bill["service_company_client_id"]."', ".$bill["bill_id"]."),";
			}

			$query = rtrim($query, ",");
			$query.="ON DUPLICATE KEY UPDATE name=VALUES(name),	file_extension=VALUES(file_extension),	bill_number=VALUES(bill_number),	generation_date=VALUES(generation_date),	reference_date=VALUES(reference_date),	operation_date=VALUES(operation_date),	preparated_date=VALUES(preparated_date),	issue_date=VALUES(issue_date),	expiration_date=VALUES(expiration_date),	amending_date=VALUES(amending_date),	sending_date=VALUES(sending_date),	recording_date=VALUES(recording_date),	collecting_date=VALUES(collecting_date),	accounting_date=VALUES(accounting_date),	observations=VALUES(observations),	service_name=VALUES(service_name),	service_acronym=VALUES(service_acronym),	company_name=VALUES(company_name),	company_cif=VALUES(company_cif),	company_address=VALUES(company_address),	company_postal_code=VALUES(company_postal_code),	company_phone=VALUES(company_phone),	company_capital=VALUES(company_capital),	company_city=VALUES(company_city),	company_country=VALUES(company_country),	company_email=VALUES(company_email),	company_iban=VALUES(company_iban),	company_acronym=VALUES(company_acronym),	company_social_object=VALUES(company_social_object),	company_observations=VALUES(company_observations),	client_name=VALUES(client_name),	client_cnmv=VALUES(client_cnmv),	client_cif=VALUES(client_cif),	client_phone=VALUES(client_phone),	client_address=VALUES(client_address),	client_postal_code=VALUES(client_postal_code),	client_web=VALUES(client_web),	client_city=VALUES(client_city),	client_country=VALUES(client_country),	client_accounting_account=VALUES(client_accounting_account),	client_iban=VALUES(client_iban),	client_commercial_register=VALUES(client_commercial_register),	client_social_object=VALUES(client_social_object),	client_observations=VALUES(client_observations),	client_drive=VALUES(client_drive),	`bank-accounting-account_id`=VALUES(`bank-accounting-account_id`),	`bank-accounting-account_name`=VALUES(`bank-accounting-account_name`),	`bank-accounting-account_number`=VALUES(`bank-accounting-account_number`),	service_company_client_id=VALUES(service_company_client_id),	bill_id=VALUES(bill_id);";

			$result = mysqli_query($this->connection, $query);

			if($result){
				return true;
			}else{
				return false;
			}
		}

		function collectBill($id, $collectingDate, $bankAccountingAccountId, $bankAccountingAccountName, $bankAccountingAccountNumber){
			if($collectingDate == null){
				$collectingDate ="NULL";
			} else {
				$collectingDate ="'".$collectingDate."'";
			}
			$result = mysqli_query($this->connection, "UPDATE `bills` SET `collecting_date`=".$collectingDate.", `bank-accounting-account_id`='".$bankAccountingAccountId."', `bank-accounting-account_name`='".$bankAccountingAccountName."', `bank-accounting-account_number`='".$bankAccountingAccountNumber."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD Periodicities

		function getPeriodicities(){
			$result = mysqli_query($this->connection,"SELECT * FROM  periodicities");
			$periodicities = array();
			while($periodicity =  mysqli_fetch_assoc($result)){
				$periodicities[] = $periodicity;
			}
			return $periodicities;
		}

		function getAccountingAccountByCompanyIdAndSubserviceId($companyId, $subserviceId){
			$result = mysqli_query($this->connection,"SELECT * FROM `accounting-accounts` WHERE `accounting-accounts`.company_id='".$companyId."' AND `accounting-accounts`.subservice_id='".$subserviceId."'");
			$accountingAccount = mysqli_fetch_assoc($result);
			if($result){
				return $accountingAccount;
			}else{
				return false;
			}
		}

		// CRUD iva-types
		/* Get all iva types */
		function getIvaTypes(){
			$result = mysqli_query($this->connection,"SELECT * FROM `iva-types`");
			$ivaTypes = array();
			while($ivaType =  mysqli_fetch_assoc($result)){
				$ivaTypes[] = $ivaType;
			}
			return $ivaTypes;
		}

		/* Get all iva types */
		function getIvaTypeById($id){
			$result = mysqli_query($this->connection,"SELECT * FROM `iva-types` WHERE id='".$id."'");
			$ivaType = mysqli_fetch_assoc($result);
			if($result){
				return $ivaType;
			}else{
				return false;
			}
		}
		
		// CRUD exemption-text
		/* Get all exemption texts */
		function getIvaExemptionTexts(){
			$result = mysqli_query($this->connection,"SELECT * FROM `iva-exemption-texts`");
			$employees = array();
			while($employee =  mysqli_fetch_assoc($result)){
				$employees[] = $employee;
			}
			return $employees;
		}
		
		// CRUD retention-types
		/* Get all retention types */
		function getRetentionTypes(){
			$result = mysqli_query($this->connection,"SELECT * FROM `retention-types`");
			$retentionTypes = array();
			while($retentionType =  mysqli_fetch_assoc($result)){
				$retentionTypes[] = $retentionType;
			}
			return $retentionTypes;
		}

		// 
		function getSendingTypes(){
			$result = mysqli_query($this->connection,"SELECT * FROM `sending-types`");
			$retentionTypes = array();
			while($retentionType =  mysqli_fetch_assoc($result)){
				$retentionTypes[] = $retentionType;
			}
			return $retentionTypes;
		}

		// CRUD accounting-accounts
		function addAccountingAccount($companyId, $subserviceId, $accountingAccountNumber){
			$result = mysqli_query($this->connection, "INSERT INTO `accounting-accounts`(`company_id`, `subservice_id`, `number`) VALUES ('".$companyId."', '".$subserviceId."', '".$accountingAccountNumber."')");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function setAccountingAccount($id, $accountingAccountNumber){
			$result = mysqli_query($this->connection, "UPDATE `accounting-accounts` SET `number`='".$accountingAccountNumber."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD bank-accounting-accounts
		function addBankAccountingAccount($name, $number, $companyId){
			$result = mysqli_query($this->connection, "INSERT INTO `bank-accounting-accounts`(`name`, `number`, `company_id` ,`active`) VALUES ('".$name."', '".$number."', '".$companyId."' , 1)");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function setBankAccountingAccount($id, $name, $number){
			$result = mysqli_query($this->connection, "UPDATE `bank-accounting-accounts` SET `name`='".$name."', `number`='".$number."' WHERE `id` = '".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		function getBankAccountingAccountsOfCompany($companyId){
			$result = mysqli_query($this->connection,"SELECT * FROM `bank-accounting-accounts` WHERE company_id='".$companyId."' AND active=1");
			$departments = array();
			while($department =  mysqli_fetch_assoc($result)){
				$departments[] = $department;
			}
			return $departments;
		}

		function getBankAccountingAccountById($bankAccountingAccountId){
			$result = mysqli_query($this->connection,"SELECT * FROM `bank-accounting-accounts` WHERE id='".$bankAccountingAccountId."'");
			$bankAccountingAccount = mysqli_fetch_assoc($result);
			if($result){
				return $bankAccountingAccount;
			}else{
				return false;
			}
		}

		function disableBankAccountingAccount($bankAccountingAccountId){
			$result = mysqli_query($this->connection, "UPDATE `bank-accounting-accounts` SET `active`=0 WHERE `id` = '".$bankAccountingAccountId."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}


		// CRUD Controls
		
		/* Get all employees */
		function getControls(){
			$result = mysqli_query($this->connection,"SELECT * FROM `controls` WHERE active=1");
			$controls = array();
			while($control =  mysqli_fetch_assoc($result)){
				$controls[] = $control;
			}
			return $controls;
		}

		function getControlsOfServiceCompanyClientSubservice($serviceCompanyClientSubserviceId){
			$result = mysqli_query($this->connection,"SELECT * FROM `controls` WHERE service_company_client_subservice_id='".$serviceCompanyClientSubserviceId."' AND active=1");
			$controls = array();
			while($control =  mysqli_fetch_assoc($result)){
				$controls[] = $control;
			}
			return $controls;
		}

		function addControlsToServiceCompanyClient($serviceCompanyClientSubserviceId){
			$result = mysqli_query($this->connection,
				"SELECT * 
				FROM service_company_client_subservice
				WHERE service_company_client_subservice.id='".$serviceCompanyClientSubserviceId."'"
			);
			$serviceCompanyClientSubservice = mysqli_fetch_assoc($result);

			$query ="INSERT INTO controls (`reference_date`, `service_company_client_subservice_id`, `finished`, `active`) VALUES";

			switch ($serviceCompanyClientSubservice["periodicity_id"]) {
				case 1:
					$query.="('".$serviceCompanyClientSubservice["billing_date"]."', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					break;
				case 2:
					$query.="('".date("Y")."-01-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-02-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-03-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-04-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-05-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-06-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-07-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-08-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-09-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-10-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-11-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-12-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					break;
				case 3:
					$query.="('".date("Y")."-03-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-06-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-09-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-12-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					break;
				case 3:
					$query.="('".date("Y")."-06-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					$query.="('".date("Y")."-12-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					break;
				case 3:
					$query.="('".date("Y")."-12-01', '".$serviceCompanyClientSubserviceId."', 0, 1),";
					break;
			}

			$query = rtrim($query, ",");
			$query.=";";

			$result = mysqli_query($this->connection, $query);

			if($result){
				return true;
			}else{
				return false;
			}
		}

		function checkControl($id){
			$result = mysqli_query($this->connection,"UPDATE `controls` SET `finished`=1 WHERE id='".$id."'");
			if($result){
				return true;
			}else{
				return false;
			}
		}

		// CRUD Billing

		function getBillingOfBill($id){
			$result = mysqli_query($this->connection,"SELECT * FROM `billing` WHERE bill_id='".$id."'");
			$billing = array();
			while($billingRow =  mysqli_fetch_assoc($result)){
				$billing[] = $billingRow;
			}
			return $billing;
		}

		function getBillingToRecord(){
			$result = mysqli_query($this->connection,
				"SELECT  bills.id AS bill_id, bills.bill_number AS bill_number, bills.operation_date AS bill_operation_date, bills.bill_id AS bill_bill_id, clients.name AS client_name, clients.cif AS client_cif, companies.id AS company_id
					, REPLACE(FORMAT(ROUND(SUM(`billing`.`price`*`billing`.`units`*(`billing`.`bonus`/100 + 1)),2),2,'de_DE' ), '.','')AS `amount_sum`
					, `iva-type_percentage`
					, REPLACE(FORMAT(ROUND(SUM(`billing`.`price`*`billing`.`units`*(`billing`.`bonus`/100 + 1)*`billing`.`iva-type_percentage`/100),2),2,'de_DE'), '.','') AS `iva_amount_sum`
					, REPLACE(FORMAT(ROUND(SUM(`billing`.`total_amount`),2),2,'de_DE' ), '.','') AS `total_amount_sum`
					, `accounting-accounts`.`number` AS `accounting_account`
				FROM `billing`, `bills`, `service_company_client`, `service_company_client_subservice`, `service_company`, `clients`, services, companies, subservices, `accounting-accounts`
				WHERE `billing`.`bill_id` = `bills`.`id`
				AND bills.service_company_client_id = service_company_client.id
				AND billing.service_company_client_subservice_id = service_company_client_subservice.id
				AND service_company_client.client_id = clients.id
				AND service_company.service_id = services.id
				AND service_company.company_id = companies.id
				AND service_company_client_subservice.subservice_id = subservices.id
				AND service_company_client.service_company_id = service_company.id
				AND `accounting-accounts`.company_id = companies.id
				AND `accounting-accounts`.subservice_id = subservices.id
				AND (`bills`.`issue_date` IS NOT NULL AND `bills`.`recording_date` IS NULL)
				GROUP BY `billing`.`bill_id`, `billing`.`iva-type_id`, `accounting-accounts`.`number`
			");
			$billing = array();
			while($billingRow =  mysqli_fetch_assoc($result)){
				$billing[] = $billingRow;
			}
			return $billing;
		}

		function getBillingOfCompanyToRecord($companyId){
			$result = mysqli_query($this->connection,
				"SELECT  bills.id AS bill_id, bills.bill_number AS bill_number, bills.operation_date AS bill_operation_date, bills.bill_id AS bill_bill_id, clients.name AS client_name, clients.cif AS client_cif, companies.id AS company_id
					, REPLACE(FORMAT(ROUND(SUM(`billing`.`price`*`billing`.`units`*(`billing`.`bonus`/100 + 1)),2),2,'de_DE' ), '.','')AS `amount_sum`
					, `iva-type_percentage`
					, REPLACE(FORMAT(ROUND(SUM(`billing`.`price`*`billing`.`units`*(`billing`.`bonus`/100 + 1)*`billing`.`iva-type_percentage`/100),2),2,'de_DE'), '.','') AS `iva_amount_sum`
					, REPLACE(FORMAT(ROUND(SUM(`billing`.`total_amount`),2),2,'de_DE' ), '.','') AS `total_amount_sum`
					, `accounting-accounts`.`number` AS `accounting_account`
				FROM `billing`, `bills`, `service_company_client`, `service_company_client_subservice`, `service_company`, `clients`, services, companies, subservices, `accounting-accounts`
				WHERE `billing`.`bill_id` = `bills`.`id`
				AND bills.service_company_client_id = service_company_client.id
				AND billing.service_company_client_subservice_id = service_company_client_subservice.id
				AND service_company_client.client_id = clients.id
				AND service_company.service_id = services.id
				AND service_company.company_id = companies.id
				AND service_company_client_subservice.subservice_id = subservices.id
				AND service_company_client.service_company_id = service_company.id
				AND `accounting-accounts`.company_id = companies.id
				AND `accounting-accounts`.subservice_id = subservices.id
				AND (`bills`.`issue_date` IS NOT NULL AND `bills`.`recording_date` IS NULL)
				AND companies.id = '".$companyId."'
				GROUP BY `billing`.`bill_id`, `billing`.`iva-type_id`, `accounting-accounts`.`number`
			");
			$billing = array();
			while($billingRow =  mysqli_fetch_assoc($result)){
				$billing[] = $billingRow;
			}
			return $billing;
		}

		function getBillsToAccount(){
			$result = mysqli_query($this->connection,
				"SELECT SUM(billing.total_amount) AS billing_total_ammount, bills.id AS bill_id, bills.bill_number AS bill_number, bills.operation_date AS bill_operation_date, bills.bill_id AS bill_bill_id, bills.`bank-accounting-account_number` AS `bill_bank-accounting-account_number`, clients.name AS client_name, clients.accounting_account AS client_accounting_account, clients.cif AS client_cif, companies.id AS company_id
				FROM `billing`, `bills`, `service_company_client`, `service_company`, `clients`, services, companies
				WHERE `billing`.`bill_id` = `bills`.`id`
				AND bills.service_company_client_id = service_company_client.id
				AND service_company_client.client_id = clients.id
				AND service_company.service_id = services.id
				AND service_company.company_id = companies.id
				AND service_company_client.service_company_id = service_company.id
				AND service_company.company_id = companies.id
				AND (`bills`.`collecting_date` IS NOT NULL AND `bills`.`recording_date` IS NOT NULL AND `bills`.`accounting_date` IS NULL)
				GROUP BY bills.id
			");
			$billing = array();
			while($billingRow =  mysqli_fetch_assoc($result)){
				$billing[] = $billingRow;
			}
			return $billing;
		}

		// CRUD Permissions
		/* Get all permissions*/
		function getPermissions(){
			$result = mysqli_query($this->connection,"SELECT * FROM `permissions`");
			$permissions = array();
			while($permission =  mysqli_fetch_assoc($result)){
				$permissions[] = $permission;
			}
			return $permissions;
		}

		// CRUD Permissions
		/* Get all permissions*/
		function getEntities(){
			$result = mysqli_query($this->connection,"SELECT * FROM `entities`");
			$entities = array();
			while($entity =  mysqli_fetch_assoc($result)){
				$entities[] = $entity;
			}
			return $entities;
		}
	}

?>