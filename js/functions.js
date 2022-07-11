function ajaxGet(url){
    var result = false;
    $.ajax({
        type : "GET",
        dataType: "json",
        url : url,
        async: false,
        // contentType: "application/json; charset=utf-8",
        success : function(data) {
            // console.log(data);
            result = data;
            console.log(data);
        },
        error : function(error) {
            console.log("Error en la petición.");
            console.log(error);
        }
    });
    return result;
}

function ajaxPost(url, data){
    var result = false;
    $.ajax({
        type : "POST",
        data: data,
        url : url,
        async: false,
        dataType: "json",
        success : function(data) {
            //console.log(data);
            result = data;
        },
        error : function(error) {
            console.log("Error en la petición.");
            console.log(error);
            
        }
    });
    return result;
}

function ajaxFunction(url, data){
    var result = false;
    $.ajax({
        url: url,
        data: data,
        cache: false,
        contentType: false,
        processData: false,
        method: 'POST',
        type: 'POST',
        dataType: "json",
        async: false,
        success : function(data) {
            // console.log("Petición correcta.");
            // console.log(data);
            result = data;
            
        },
        error : function(error) {
            console.log("Error en la petición.");
            console.log(error);
        }
    });
    return result;
}

Date.prototype.toDateInputValue = (function() {
    var local = new Date(this);
    local.setMinutes(this.getMinutes() - this.getTimezoneOffset());
    return local.toJSON().slice(0,10);
});

/////////////////////////////////////////////////////
/* LLAMADAS AJAX */
/////////////////////////////////////////////////////


// CRUD Suppliers
function getFilms() {
    return ajaxGet("ajax/getFilms.php");
}

// File uploader
function uploadFile(file, path) {
    ajaxPost("ajax/setFilePath.php", {
        "path": path,
    });
    // fileName = ajaxFunction("ajax/uploadFile.php", formData);
    // console.log(fileName);
    // console.log("NOMBRE DEL ARCHIVO: "+ ajaxFunction("ajax/uploadFile.php", formData));
    var formData = new FormData();
    formData.append("file", file);
    return ajaxFunction("ajax/uploadFile.php", formData);
    // return formData.getAll("file")[0]["name"];
}

function uploadValidatedFile(file, path) {
    ajaxPost("ajax/setFilePath.php", {
        "path": path,
    });
    var formData = new FormData();
    formData.append("file", file);
    return ajaxFunction("ajax/uploadValidatedFile.php", formData);
}

function validateFileName(path, fileBaseName, fileExtension){
    return ajaxPost("ajax/validateFileName.php", {
        "path": path,
        "file_base_name": fileBaseName,
        "file_extension": fileExtension,
    });
}

function renameFile(oldCompleteFilePath, newCompleteFilePath) {
    return ajaxPost("ajax/renameFile.php", {
        "old_complete_file_path": oldCompleteFilePath,
        "new_complete_file_path": newCompleteFilePath
    });
}

// File Deleter
function deleteFile(path) {
    return ajaxPost("ajax/deleteFile.php", {
        "path": path
    });
}

// CRUD User
function getUsers() {
    return ajaxGet("ajax/getUsers.php");
}

function  getEmployeesNotAssignedToUser() {
    return ajaxGet("ajax/getEmployeesNotAssignedToUser.php", {
    });
}

function addUser(name, email, password, avatar, roleId, employeeId){
    return ajaxPost("ajax/addUser.php", {
        'name': name,
        'email': email,
        'password': password,
        'avatar': avatar,
        'role_id': roleId,
        'employee_id': employeeId,
    });
}

function userEmailExists(email) {
    return ajaxPost("ajax/userEmailExists.php",{
        "email": email,
    });
}



// CRUD Role
function getRoles() {
    return ajaxGet("ajax/getRoles.php");
}

// CRUD Entities
function getEntities() {
    return ajaxGet("ajax/getEntities.php");
}

// CRUD Company

function getCompanies() {
    return ajaxGet("ajax/getCompanies.php");
}

function companyCifExists(cif) {
    return ajaxPost("ajax/companyCifExists.php",{
        "cif": cif,
    });
}


function getCompaniesNotAssignedToService(serviceId) {
    return ajaxPost("ajax/getCompaniesNotAssignedToService.php", {
        "service_id": serviceId,
    });
}

function getCompaniesWithBillingToRecord() {
    return ajaxGet("ajax/getCompaniesWithBillingToRecord.php");
}

function getCompanyById(id) {
    return ajaxPost("ajax/getCompanyById.php", {
        "id": id,
    });
}

function addCompany(name, image, cif, address, web, postalCode, constitutionDate, phone, capital, city, country, colorCorporative, fontTypeId, email, billinEmail, iban, acronym, commercialRegister, socialObject, observations){
    return ajaxPost("ajax/addCompany.php", {
        'name': name,
        'image': image,
        'cif': cif,
        'address': address,
        'web': web,
        'postal_code': postalCode,
        'constitution_date': constitutionDate,
        'phone': phone,
        'capital': capital,
        'city': city,
        'country': country,
        'corporative_color': colorCorporative,
        'font_type_id': fontTypeId,
        'email': email,
        'billing_email': billinEmail,
        'iban': iban,
        'acronym': acronym,
        'commercial_register': commercialRegister,
        'social_object': socialObject,
        'observations': observations,
    });
}

function setCompany(id, name, image, cif, address, web, postalCode, constitutionDate, phone, capital, city, country, colorCorporative, fontTypeId, email, iban,billingEmail, acronym, commercialRegister, socialObject, observations){
    return ajaxPost("ajax/setCompany.php", {
        'id':id,
        'name': name,
        'image': image,
        'cif': cif,
        'address': address,
        'web': web,
        'postal_code': postalCode,
        'constitution_date': constitutionDate,
        'phone': phone,
        'capital': capital,
        'city': city,
        'country': country,
        'corporative_color': colorCorporative,
        'font_type_id':fontTypeId,
        'email':email,
        'iban':iban,
		'billing_email':billingEmail,
        'acronym':acronym,
        'commercial_register': commercialRegister,
        'social_object': socialObject,
        'observations': observations,
    });
}

function disableCompany(companyId){
    return ajaxPost("ajax/disableCompany.php", {
        'company_id':companyId,
    });
}

// CRUD Client
function getClients() {
    return ajaxGet("ajax/getClients.php");
}

function getClientById(id) {
    return ajaxPost("ajax/getClientById.php", {
        "id": id,
    });
}

function getClientAdressesById(id) {
    return ajaxPost("ajax/getClientAdressesById.php", {
        "id": id,
    });
}


function addClient(name, cnmv, cif, phone, image, address, postalCode, web, city, country, accountingAccount, iban, paymentMethod, commercialRegister, socialObject, observations, drive) {
    return ajaxPost("ajax/addClient.php", {
        'name': name,
        'cnmv': cnmv,
        'cif': cif,
        'phone': phone,
        'image':image,
        'address': address,
        'postal_code': postalCode,
        'web': web,
        'city': city,
        'accounting_account': accountingAccount,
        'iban': iban,
        'payment_method': paymentMethod,
        'country': country,
        'commercial_register': commercialRegister,
        'social_object': socialObject,
        'observations': observations,
        'drive': drive,
        
    });
}

function clientCifExists(cif) {
    return ajaxPost("ajax/clientCifExists.php",{
        "cif": cif,
    });
}

function setClient(id, name, cnmv, cif, phone, image, address, postalCode, web, city, country, accountingAccount, iban, paymentMethod,commercialRegister, socialObject, observations, drive) {
    return ajaxPost("ajax/setClient.php", {
        
        'id': id,
        'name': name,
        'cnmv': cnmv,
        'cif': cif,
        'phone': phone,
        'image':image,
        'address': address,
        'postal_code': postalCode,
        'web': web,
        'city': city,
        'country': country,
        'accounting_account': accountingAccount,
        'iban': iban,
        'payment_method':paymentMethod,
        'commercial_register': commercialRegister,
        'social_object': socialObject,
        'observations': observations,
        'drive': drive,

    });
}

function disableClient(clientId){
    return ajaxPost("ajax/disableClient.php", {
        'client_id':clientId,
    });
}

//CRUD Shipping addresses
function getClientShippingAddressesOfClient(clientId) {
    return ajaxPost("ajax/getClientShippingAddressesOfClient.php", {
        "client_id": clientId,
    });
}

function addShippingAddress(name, lastName, email, clientId, sendingTypeId) {
    return ajaxPost("ajax/addShippingAddress.php", {
        'name': name,
        'last_name': lastName,
        'email': email,
        'client_id': clientId,
        'sending_type_id': sendingTypeId,
        
    });
}

function disableShippingAddress(id){
    return ajaxPost("ajax/disableShippingAddress.php", {
        'id':id,
    });
}

function setShippingAddress(id, name, lastName, email, sendingTypeId) {
    return ajaxPost("ajax/setShippingAddress.php", {
        'id': id,
        'name': name,
        'last_name': lastName,
        'email': email,
        'sending_type_id': sendingTypeId,
        
    });
}

function getShippingAddressById(clientShippingAddressId){
    return ajaxPost("ajax/getShippingAddressById.php", {
        "client_id": clientShippingAddressId
    });

}

// CRUD Suppliers
function getSuppliers() {
    return ajaxGet("ajax/getSuppliers.php");
}

function getSupplierById(id) {
    return ajaxPost("ajax/getSupplierById.php", {
        "id": id,
    });
}

function addSupplier(name, cif, contact, address, accountingAccount, spendingAccount, ivaTypeId, retentionTypeId) {
    return ajaxPost("ajax/addSupplier.php", {
        'name': name,
        'cif': cif,
        'contact': contact,
        'address': address,
        'accounting_account': accountingAccount,
        'spending_account': spendingAccount,
        'iva-type_id': ivaTypeId,
        'retention-type_id': retentionTypeId,
    });
}


function setSupplier(id, name, cif, contact, address, accountingAccount, spendingAccount, ivaTypeId, retentionTypeId) {
    return ajaxPost("ajax/setSupplier.php", {
        'id': id,
        'name': name,
        'cif': cif,
        'contact': contact,
        'address': address,
        'accounting_account': accountingAccount,
        'spending_account': spendingAccount,
        'iva-type_id': ivaTypeId,
        'retention-type_id': retentionTypeId,
    });
}

function disableSupplier(id){
    return ajaxPost("ajax/disableSupplier.php", {
        'id':id,
    });
}


function getSupplierBills(supplierId){
    return ajaxPost("ajax/getSupplierBills.php", {
        'supplier_id': supplierId
    });
}

function addSupplierBill(name, fileExtension, amount, billNumber, registrationDate, referenceDate, operationDate, preparatedDate, issueDate, expirationDate, amendingDate, sendingDate, recordingDate, collectingDate, accountingDate, observations, supplierName, supplierCif, supplierAccountingAccount, supplierSpendingAccount, supplierIvaTypePercentage, supplierRetentionTypePercentage, companyId, supplierId) {
    return ajaxPost("ajax/addSupplierBill.php", {
        'name': name,
        'file_extension': fileExtension,
        'amount': amount,
        'bill_number': billNumber,
        'registration_date': registrationDate,
        'reference_date': referenceDate,
        'operation_date': operationDate,
        'preparated_date': preparatedDate,
        'issue_date': issueDate,
        'expiration_date': expirationDate,
        'amending_date': amendingDate,
        'sending_date': sendingDate,
        'recording_date': recordingDate,
        'collecting_date': collectingDate,
        'accounting_date': accountingDate,
        'observations': observations,
        'supplier_name': supplierName,
        'supplier_cif': supplierCif,
        'supplier_accounting_account': supplierAccountingAccount,
        'supplier_spending_account': supplierSpendingAccount,
        'supplier_iva_type_percentage': supplierIvaTypePercentage,
        'supplier_retention_type_percentage': supplierRetentionTypePercentage,
        'company_id': companyId,
        'supplier_id': supplierId,
    });
}

function supplierCifExists(cif) {
    return ajaxPost("ajax/supplierCifExists.php",{
        "cif": cif,
    });
}

// CRUD Employee

function getEmployees() {
    return ajaxGet("ajax/getEmployees.php");
}


function getEmployeeById(id) {
    return ajaxPost("ajax/getEmployeeById.php", {
        "id": id,
    });
}

function getEmployeesOfCompany(companyId) {
    return ajaxPost("ajax/getEmployeesOfCompany.php", {
        "company_id": companyId,
    });
}

function getEmployeesOfDepartment(departmentId) {
    return ajaxPost("ajax/getEmployeesOfDepartment.php", {
        "department_id": departmentId,
    });
}

function getEmployeesOfEmployment(employmentId) {
    return ajaxPost("ajax/getEmployeesOfEmployment.php", {
        "employment_id": employmentId,
    });
}

function addEmployee(name, image, companyId, departmentId, employmentId, contract, email, phone, personalEmail, personalPhone, observations, postalCode, province, city, country, facebook, instagram, twitter, linkedin) {
    return ajaxPost("ajax/addEmployee.php", {
        "name": name,
        "image": image,
        "company_id": companyId,
        "department_id": departmentId,
        "employment_id": employmentId,
        "contract": contract,
        "email": email,
        "phone": phone,
        "personal_email": personalEmail,
        "personal_phone": personalPhone,
        "observations": observations,
        "postal_code": postalCode,
        "province": province,
        "city": city,
        "country": country,
        "facebook": facebook,
        "instagram": instagram,
        "twitter": twitter,
        "linkedin": linkedin,
    });
}


function setEmployee(id, name, image, companyId, departmentId, employmentId, contract, email, phone, personalEmail, personalPhone, observations, postalCode, province, city, country, facebook, instagram, twitter, linkedin) {
    return ajaxPost("ajax/setEmployee.php", {
        "id": id,
        "name": name,
        "image": image,
        "company_id": companyId,
        "department_id": departmentId,
        "employment_id": employmentId,
        "contract": contract,
        "email": email,
        "phone": phone,
        "personal_email": personalEmail,
        "personal_phone": personalPhone,
        "observations": observations,
        "postal_code": postalCode,
        "province": province,
        "city": city,
        "country": country,
        "facebook": facebook,
        "instagram": instagram,
        "twitter": twitter,
        "linkedin": linkedin,
    });
}

function disableEmployee(employeeId){
    return ajaxPost("ajax/disableEmployee.php", {
        'employee_id':employeeId,
    });
}

function  getClientsNotAssignedToEmployee(employeeId) {
    return ajaxPost("ajax/getClientsNotAssignedToEmployee.php", {
        "employee_id": employeeId,
    });
}



// CRUD Department
function getDepartmentsOfCompany(id) {
    return ajaxPost("ajax/getDepartmentsOfCompany.php", {
        "id": id,
    });
}

function getDepartmentById(id) {
    return ajaxPost("ajax/getDepartmentById.php", {
        "id": id,
    });
}

function addDepartment(name, description, companyId) {
    return ajaxPost("ajax/addDepartment.php", {
        "name": name,
        "description": description,
        "company_id": companyId,
    });
}

function addDepartments(departmentNames, companyId) {
    return ajaxPost("ajax/addDepartments.php", {
        "department_names": departmentNames,
        "company_id": companyId,
    });
}

function setDepartment(id, name, description){
    return ajaxPost("ajax/setDepartment.php", {
        "id": id,
        "name": name,
        "description": description,
    });
}

function setDepartments(departments) {
    return ajaxPost("ajax/setDepartments.php", {
        "departments": departments,
    });
}

function disableDepartment(departmentId){
    return ajaxPost("ajax/disableDepartment.php", {
        'department_id':departmentId,
    });
}

// CRUD Employment
function getEmploymentsOfCompany(companyId) {
    return ajaxPost("ajax/getEmploymentsOfCompany.php", {
        "company_id": companyId,
    });
}

function getEmploymentById(id) {
    return ajaxPost("ajax/getEmploymentById.php", {
        "id": id,
    });
}

function addEmployment(name, description, companyId) {
    return ajaxPost("ajax/addEmployment.php", {
        "name": name,
        "description": description,
        "company_id": companyId,
    });
}

function setEmployment(id, name, description) {
    return ajaxPost("ajax/setEmployment.php", {
        "id": id,
        "name": name,
        "description": description,
    });
}

function disableEmployment(employmentId){
    return ajaxPost("ajax/disableEmployment.php", {
        'employment_id':employmentId,
    });
}

// CRUD Service
function addService(name, acronym, description) {
    return ajaxPost("ajax/addService.php", {
        "name": name,
        "acronym": acronym,
        "description": description,
    });
}

function setService(id, name, acronym, description) {
    return ajaxPost("ajax/setService.php", {
        "id": id,
        "name": name,
        "acronym": acronym,
        "description": description,
    });
}

function setServiceBudgetTemplate(id, budgetTemplate) {
    return ajaxPost("ajax/setServiceBudgetTemplate.php", {
        "id": id,
        "budget_template": budgetTemplate,
    });
}

function setServiceContractTemplate(id, contractTemplate) {
    return ajaxPost("ajax/setServiceContractTemplate.php", {
        "id": id,
        "contract_template": contractTemplate,
    });
}


function getServices() {
    return ajaxGet("ajax/getServices.php");
}

function getServiceById(id) {
    return ajaxPost("ajax/getServiceById.php", {
        "id": id,
    });
}

function getLastService() {
    return ajaxGet("ajax/getLastService.php");
}

function disableService(serviceId){
    return ajaxPost("ajax/disableService.php", {
        'service_id':serviceId,
    });
}



// CRUD Subservice
function addSubservice(name, description, baseRate, serviceId) {
    return ajaxPost("ajax/addSubservice.php", {
        "name": name,
        "description": description,
        "base_rate": baseRate,
        "service_id": serviceId,
    });
}

function deleteSubserviceSnapById(subServiceSnapId) {
    return ajaxPost("ajax/deleteSubserviceSnapById.php", {
        "subservice_snap_id": subServiceSnapId,
      
    });
}

function getSubservicesOfService(serviceId) {
    return ajaxPost("ajax/getSubservicesOfService.php", {
        "service_id": serviceId,
    });
}

function getSubservicesOfServiceNotAssignedToServiceCompanyClient(serviceCompanyClientId) {
    return ajaxPost("ajax/getSubservicesOfServiceNotAssignedToServiceCompanyClient.php", {
        "service_company_client_id": serviceCompanyClientId,
    });
}

function getSubserviceById(subserviceId) {
    return ajaxPost("ajax/getSubserviceById.php", {
        "subservice_id": subserviceId,
    });
}

function setSubservice(id, name, baseRate, description, serviceId) {
    return ajaxPost("ajax/setSubservice.php", {
        "id": id,
        "name": name,
        "base_rate": baseRate,
        "description": description,
        "service_id": serviceId,
    });
}


// CRUD FontTypes
function getFontTypes() {
    return ajaxGet("ajax/getFontTypes.php");
}

function getFontTypeById(id){
    return ajaxPost("ajax/getFontTypeById.php", {
        "id": id,
    });
}


// CRUD ServiceCompany
function getServiceCompaniesByServiceId(serviceId){
    return ajaxPost("ajax/getServiceCompaniesByServiceId.php", {
        "service_id": serviceId,
    });
}

function getServiceCompaniesByCompanyId(companyId){
    return ajaxPost("ajax/getServiceCompaniesByCompanyId.php", {
        "company_id": companyId,
    });
}

function getServiceCompaniesByServiceIdNotAssignedToClient(serviceId, clientId){
    return ajaxPost("ajax/getServiceCompaniesByServiceIdNotAssignedToClient.php", {
        "service_id": serviceId,
        "client_id": clientId,
    });
}

function addServiceCompany(serviceId, companyId) {
    return ajaxPost("ajax/addServiceCompany.php", {
        "service_id": serviceId,
        "company_id": companyId,
    });
}

// CRUD EmployeeClient

function getEmployeeClientsByEmployeeId(employeeId){
    return ajaxPost("ajax/getEmployeeClientsByEmployeeId.php", {
        "employee_id": employeeId,
    });
}

function getEmployeeClientsByEmployeeIdNotAssignedToClient(serviceId, clientId){
    return ajaxPost("ajax/getServiceCompaniesByServiceIdNotAssignedToClient.php", {
        "service_id": serviceId,
        "client_id": clientId,
    });
}

function addEmployeeClient(employeeId, clientId) {
    return ajaxPost("ajax/addEmployeeClient.php", {
        "employee_id": employeeId,
        "client_id": clientId,
    });
}

// CRUD ServiceCompanyClient

function getServiceCompanyClients(){
    return ajaxGet("ajax/getServiceCompanyClients.php");
}

function getServiceCompanyClientsByServiceId(serviceId){
    return ajaxPost("ajax/getServiceCompanyClientsByServiceId.php", {
        "service_id": serviceId,
    });
}

function getServiceCompanyClientsByClientId(clientId){
    return ajaxPost("ajax/getServiceCompanyClientsByClientId.php", {
        "client_id": clientId,
    });
}

function getServiceCompanyClientsByCompanyId(companyId){
    return ajaxPost("ajax/getServiceCompanyClientsByCompanyId.php", {
        "company_id": companyId,
    });
}

///Subservice cliente details
function getServiceCompanyClientsBySubservicesId(subserviceId){
    return ajaxPost("ajax/getServiceCompanyClientsBySubservicesId.php", {
        "subservice_id": subserviceId,
    });
}

function getServiceCompanyClientById(id){
    return ajaxPost("ajax/getServiceCompanyClientById.php", {
        "id": id,
    });
}

function addServiceCompanyClient(serviceCompanyId, clientId) {
    return ajaxPost("ajax/addServiceCompanyClient.php", {
        "service_company_id": serviceCompanyId,
        "client_id": clientId,
    });
}

// CRUD ServiceCompanyClientSubservices

function getServiceCompanyClientSubservices(){
    return ajaxGet("ajax/getServiceCompanyClientSubservices.php"); 
}

function getServiceCompanyClientSubservicesByCompanyId(companyId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesByCompanyId.php",{ 
        "company_id": companyId, 
    }); 
}

function getServiceCgetServiceCompanyClientSubservicesOfCurrentYearByCompanyIdompanyClientSubservicesByCompanyId(companyId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesOfCurrentYearByCompanyId.php",{ 
        "company_id": companyId, 
    }); 
}

function getServiceCompanyClientSubservicesByServiceId(serviceId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesByServiceId.php",{ 
        "service_id": serviceId, 
    }); 
}

function getServiceCompanyClientSubservicesByClientId(clientId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesByClientId.php",{ 
        "client_id": clientId, 
    }); 
}

function getServiceCompanyClientSubservicesCurrentYearTotalAmountByCompanyId(companyId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesCurrentYearTotalAmountByCompanyId.php",{  
        "company_id": companyId,
    }); 
}
function getServiceCompanyClientSubservicesCurrentYearExecutedAmountByCompanyId(companyId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesCurrentYearExecutedAmountByCompanyId.php",{  
        "company_id": companyId,
    }); 
}
function getPendingBillingCurrentYearTotalAmountByCompanyId(companyId){
    return ajaxPost("ajax/getPendingBillingCurrentYearTotalAmountByCompanyId.php",{  
        "company_id": companyId,
    }); 
}
function getBillingCurrentYearTotalAmountByCompanyId(companyId){
    return ajaxPost("ajax/getBillingCurrentYearTotalAmountByCompanyId.php",{  
        "company_id": companyId,
    }); 
}

function getServiceCompanyClientSubservicesCurrentYearTotalAmountByClientId(clientId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesCurrentYearTotalAmountByClientId.php",{  
        "client_id": clientId,
    }); 
}
function getServiceCompanyClientSubservicesCurrentYearExecutedAmountByClientId(clientId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesCurrentYearExecutedAmountByClientId.php",{  
        "client_id": clientId,
    }); 
}
function getPendingBillingCurrentYearTotalAmountByClientId(clientId){
    return ajaxPost("ajax/getPendingBillingCurrentYearTotalAmountByClientId.php",{  
        "client_id": clientId,
    }); 
}
function getBillingCurrentYearTotalAmountByClientId(clientId){
    return ajaxPost("ajax/getBillingCurrentYearTotalAmountByClientId.php",{  
        "client_id": clientId,
    }); 
}

function getServiceCompanyClientSubservicesCurrentYearTotalAmount(){
     return ajaxGet("ajax/getServiceCompanyClientSubservicesCurrentYearTotalAmount.php"); 
}
function getServiceCompanyClientSubservicesCurrentYearExecutedAmount(){
    return ajaxGet("ajax/getServiceCompanyClientSubservicesCurrentYearExecutedAmount.php"); 
}
function getPendingBillingCurrentYearTotalAmount(){
    return ajaxGet("ajax/getPendingBillingCurrentYearTotalAmount.php");
}

function getBillingCurrentYearTotalAmount(){
    return ajaxGet("ajax/getBillingCurrentYearTotalAmount.php"); 
}


function getServiceCompanyClientSubservicesHired(){
    return ajaxGet("ajax/getServiceCompanyClientSubservicesHired.php"); 
}

function getServiceCompanyClientSubservicesByServiceCompanyClientId(serviceCompanyClientId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesByServiceCompanyClientId.php", {
        "service_company_client_id": serviceCompanyClientId,
    });
}

function getServiceCompanyClientSubservicesSnapsByServiceCompanyClientId(serviceCompanyClientId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesSnapsByServiceCompanyClientId.php", {
        "service_company_client_id": serviceCompanyClientId,
    });
}

function getServiceCompanyClientSubservicesOfBill(billId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesOfBill.php", {
        "bill_id": billId,
    });
}

function getServiceCompanyClientSubservicesByEmployeeId(employeeId){
    return ajaxPost("ajax/getServiceCompanyClientSubservicesByEmployeeId.php", {
        "employee_id": employeeId,
    });
}

function getServiceCompanyClientSubserviceAmountSumByServiceCompanyClientId(serviceCompanyClientId){
    return ajaxPost("ajax/getServiceCompanyClientSubserviceAmountSumByServiceCompanyClientId.php", {
        "service_company_client_id": serviceCompanyClientId,
    });
}

function addServiceCompanyClientSubservice(serviceCompanyClientId, subserviceId, name, price, bonus, units, ivaTypeId, description, periodicityId, startingDate, endingDate, billingDate, mainEmployeeId, secondaryEmployeeId, hired, budgetDate){
    return ajaxPost("ajax/addServiceCompanyClientSubservice.php", {
        'service_company_client_id': serviceCompanyClientId,
        'subservice_id': subserviceId,
        'name': name,
        'price': price,
        'bonus': bonus,
        'units': units,
        'iva-type_id': ivaTypeId,
        'description': description,
        'periodicity_id': periodicityId,
        'starting_date': startingDate,
        'ending_date': endingDate,
        'billing_date': billingDate,
        'main_employee_id': mainEmployeeId,
        'secondary_employee_id': secondaryEmployeeId,
        'hired': hired,
        'budget_date': budgetDate,
    });
}

function addServiceCompanyClientSubservicePunctualSnap(serviceCompanyClientId, subserviceId, name, price, bonus, units, ivaTypeId, description, periodicityId, startingDate, endingDate, billingDate, signupDate, withdrawalDate, mainEmployeeId, secondaryEmployeeId, hired, budgetDate){
    return ajaxPost("ajax/addServiceCompanyClientSubservicePunctualSnap.php", {
        'service_company_client_id': serviceCompanyClientId,
        'subservice_id': subserviceId,
        'name': name,
        'price': price,
        'bonus': bonus,
        'units': units,
        'iva-type_id': ivaTypeId,
        'description': description,
        'periodicity_id': periodicityId,
        'starting_date': startingDate,
        'ending_date': endingDate,
        'billing_date': billingDate,
        'signup_date': signupDate,
        'withdrawal_date': withdrawalDate,
        'main_employee_id': mainEmployeeId,
        'secondary_employee_id': secondaryEmployeeId,
        'hired': hired,
        'budget_date': budgetDate,
    });
}

function setServiceCompanyClientSubservice(id, name, price, bonus, units, ivaTypeId, description, periodicityId, startingDate, endingDate, billingDate, mainEmployeeId, secondaryEmployeeId, hired, budgetDate){
    return ajaxPost("ajax/setServiceCompanyClientSubservice.php", {
        "id": id,
        'name': name,
        'price': price,
        'bonus': bonus,
        'units': units,
        'iva-type_id': ivaTypeId,
        'description': description,
        'periodicity_id': periodicityId,
        'starting_date': startingDate,
        'ending_date': endingDate,
        'billing_date': billingDate,
        'main_employee_id': mainEmployeeId,
        'secondary_employee_id': secondaryEmployeeId,
        'hired': hired,
        'budget_date': budgetDate,
    });
}

function getServiceCompanyClientSubserviceById(id){
    return ajaxPost("ajax/getServiceCompanyClientSubserviceById.php", {
        "id": id,
    });
}

function deleteServiceCompanyClientSubservice(id){
    return ajaxPost("ajax/deleteServiceCompanyClientSubservice.php", {
        "id": id,
    });
}

// CRUD Client contacts 

function getClientContactsOfClient(clientId){
    return ajaxPost("ajax/getClientContactsOfClient.php", {
        "client_id": clientId,
    });
}

function addClientContact(name, surnames, phone, email, dni, department, employment, position, observations, clientId) {
    return ajaxPost("ajax/addClientContact.php", {
        'name': name,
        'surnames': surnames,
        'phone': phone,
        'email': email,
        'dni': dni,
        'department': department,
        'employment': employment,
        'position': position,
        'observations': observations,
        'client_id': clientId
        
    });
}

function setClientContact(id, name, surnames, phone, email, dni, department, employment, position, observations, clientId) {
    return ajaxPost("ajax/setClientContact.php", {

        "id": id,
        'name': name,
        'surnames': surnames,
        'phone': phone,
        'email': email,
        'dni': dni,
        'department': department,
        'employment': employment,
        'position': position,
        'observations': observations,
        'client_id': clientId
        
    });
}

function getClientContactsOfClient(clientId){
    return ajaxPost("ajax/getClientContactsOfClient.php", {
        "client_id": clientId
    });
}

function getClientContactById(clientContactId){
    return ajaxPost("ajax/getClientContactById.php", {
        "client_contact_id": clientContactId
    });

}

function disableClientContact(clientContactId){
    return ajaxPost("ajax/disableClientContact.php", {
        'client_contact_id':clientContactId,
    });
}

function clientContactDniExists(dni) {
    return ajaxPost("ajax/clientContactDniExists.php",{
        "dni": dni,
    });
}

// CRUD Company contacts 

function getCompanyContactsOfCompany(companyId){
    return ajaxPost("ajax/getCompanyContactsOfCompany.php", {
        "company_id": companyId,
    });
}

function addCompanyContact(name, surnames, dni, address, position, companyId) {
    return ajaxPost("ajax/addCompanyContact.php", {
        'name': name,
        'surnames': surnames,
        'dni': dni,
        'address': address,
        'position':position,
        'company_id': companyId
        
    });
}

function setCompanyContact(id, name, surnames, dni, address, position, companyId) {
    return ajaxPost("ajax/setCompanyContact.php", {
        'id': id,
        'name': name,
        'surnames': surnames,
        'dni': dni,
        'address': address,
        'position':position,
        'company_id': companyId
    });
}

function getCompanyContactById(companyContactId){
    return ajaxPost("ajax/getCompanyContactById.php", {
        "company_contact_id": companyContactId,
    });
}

function disableCompanyContact(companyContactId){
    return ajaxPost("ajax/disableCompanyContact.php", {
        'company_contact_id':companyContactId,
    });
}

function deleteCompanyById(companypId) {
    return ajaxPost("ajax/deleteCompanyById.php", {
        "company_id": companypId,
      
    });
}


function companyContactDniExists(dni) {
    return ajaxPost("ajax/companyContactDniExists.php",{
        "dni": dni,
    });
}

// CRUD CONTACT ROLES

function getContactRoles(){
    return ajaxGet("ajax/getContactRoles.php");
}


// CRUD document-companies

function addCompanyDocument(name, fileExtension, description, companyId){
    return ajaxPost("ajax/addCompanyDocument.php", {
        'name':name,
        'file_extension':fileExtension,
        'description':description,
        'company_id':companyId,
    });
}

function setCompanyDocument(id, name, description){
    return ajaxPost("ajax/setCompanyDocument.php", {
        'id':id,
        'name':name,
        'description':description,
    });
}

function disableCompanyDocument(companyDocumentId){
    return ajaxPost("ajax/disableCompanyDocument.php", {
        'company_document_id':companyDocumentId,
    });
}

function deleteCompanyDocument(companyDocumentId){
    return ajaxPost("ajax/deleteCompanyDocument.php", {
        'company_document_id':companyDocumentId,
    });
}

// CRUD document-services

function addServiceDocument(name, description,fileExtension, serviceId){
    return ajaxPost("ajax/addServiceDocument.php", {
        'name':name,
        'description':description,
        'file_extension':fileExtension,
        'service_id':serviceId,
    });
}

// CRUD document-employees

function addEmployeeDocument(name, fileExtension, description, uploadDate, employeeId){
    return ajaxPost("ajax/addEmployeeDocument.php", {
        'name':name,
        'file_extension':fileExtension,
        'description':description,
        'upload_date':uploadDate,
        'employee_id':employeeId,
    });
}

// CRUD document-clients

function addClientDocument(name, fileExtension, description, clientId){
    return ajaxPost("ajax/addClientDocument.php", {
        'name':name,
        'file_extension':fileExtension,
        'description':description,
        'client_id':clientId,
    });
}

function deleteClientDocument(clientDocumentId){
    return ajaxPost("ajax/deleteClientDocument.php", {
        'client_document_id':clientDocumentId,
    });
}

function disableClientDocument(companyDocumentId){
    return ajaxPost("ajax/disableClientDocument.php", {
        'company_document_id':companyDocumentId,
    });
}

function setClientDocument(id, name, description){
    return ajaxPost("ajax/setClientDocument.php", {
        'id':id,
        'name':name,
        'description':description,
    });
}

// CRUD contracts

function getContracts(){
    return ajaxGet("ajax/getContracts.php");
}

function getPendingContracts(){
    return ajaxGet("ajax/getPendingContracts.php");
}

function addContract(fileBaseName, fileExtension, content, serviceCompanyClientId){
    return ajaxPost("ajax/addContract.php", {
        'file_base_name':fileBaseName,
        'file_extension':fileExtension,
        'content':content,
        'service_company_client_id':serviceCompanyClientId,
    });
}

function setContract(id, name, content, registrationDate, preparatedDate, signedPart1Date, signedPart2Date, signedBothPartsDate, withdrawalDate, observations){
    return ajaxPost("ajax/setContract.php", {
        'id': id,
        'name': name,
        'content': content,
        'registration_date': registrationDate,
        'preparated_date': preparatedDate,
        'signed_part_1_date': signedPart1Date,
        'signed_part_2_date': signedPart2Date,
        'signed_both_parts_date': signedBothPartsDate,
        'withdrawal_date': withdrawalDate,
        'observations': observations,
    });
}

function prepareContractToSign(id){
    return ajaxPost("ajax/prepareContractToSign.php", {
        'id':id,
    });
}

function deleteContract(contractId){
    return ajaxPost("ajax/deleteContract.php", {
        'contract_id':contractId,
    });
}

function deleteContractByName(name){
    return ajaxPost("ajax/deleteContractByName.php", {
        'name':name,
    });
}

function disableContract(contractId){
    return ajaxPost("ajax/disableContract.php", {
        'contract_id':contractId,
    });
}

// CRUD Budgets

function getBudgets(){
    return ajaxGet("ajax/getBudgets.php");
}

function getPendingBudgets(){
    return ajaxGet("ajax/getPendingBudgets.php");
}

function addBudget(fileBaseName, fileExtension, content, serviceCompanyClientId){
    return ajaxPost("ajax/addBudget.php", {
        'file_base_name':fileBaseName,
        'file_extension':fileExtension,
        'content':content,
        'service_company_client_id':serviceCompanyClientId,
    });
}

function setBudget(id, name, content, registrationDate, preparatedDate, signedPart1Date, signedPart2Date, signedBothPartsDate, withdrawalDate, observations){
    return ajaxPost("ajax/setBudget.php", {
        'id': id,
        'name': name,
        'content': content,
        'registration_date': registrationDate,
        'preparated_date': preparatedDate,
        'signed_part_1_date': signedPart1Date,
        'signed_part_2_date': signedPart2Date,
        'signed_both_parts_date': signedBothPartsDate,
        'withdrawal_date': withdrawalDate,
        'observations': observations,
    });
}

function prepareBudgetToSign(id){
    return ajaxPost("ajax/prepareBudgetToSign.php", {
        'id':id,
    });
}

function deleteBudget(budgetId){
    return ajaxPost("ajax/deleteBudget.php", {
        'budget_id':budgetId,
    });
}

function deleteBudgetByName(name){
    return ajaxPost("ajax/deleteBudgetByName.php", {
        'name':name,
    });
}

function disableBudget(budgetId){
    return ajaxPost("ajax/disableBudget.php", {
        'budget_id':budgetId,
    });
}

// CRUD Bills

function getPendingBills(){
    return ajaxGet("ajax/getSentBills.php");
}
function getSentBills(){
    return ajaxGet("ajax/getPendingBills.php");
}

function getBillById(id) {
    return ajaxPost("ajax/getBillById.php", {
        "id": id,
    });
}

function getBillByBillId(billId) {
    return ajaxPost("ajax/getBillByBillId.php", {
        "bill_id": billId,
    });
}

function getPendingBillsByServiceCompanyClientId(serviceCompanyClientId){
    return ajaxPost("ajax/getPendingBillsByServiceCompanyClientId.php", {
        'service_company_client_id': serviceCompanyClientId,
    });
}

function addBill(name, fileExtension, billNumber, generationDate, preparatedDate, issueDate, expirationDate, sendingDate, collectingDate, observations, serviceCompanyClientId){
    return ajaxPost("ajax/addBill.php", {
        'name': name,
        'file_extension': fileExtension,
        'bill_number': billNumber,
        'generation_date': generationDate,
        'preparated_date': preparatedDate,
        'issue_date': issueDate,
        "expiration_date": expirationDate,
        'sending_date': sendingDate,
        'collecting_date': collectingDate,
        'observations': observations,
        'service_company_client_id': serviceCompanyClientId,
    });
}

function issueBill(id, name, fileExtension) {
    return ajaxPost("ajax/issueBill.php", {
        'id': id,
        'name': name,
        'file_extension': fileExtension,
    });
}

function billSubservicesOfBill(id) {
    return ajaxPost("ajax/billSubservicesOfBill.php", {
        'id': id,
    });
}

function setBill(id, name, fileExtension, billNumber, preparatedDate, issueDate, expirationDate, sendingDate, collectingDate, observations, serviceCompanyClientId) {
    return ajaxPost("ajax/setBill.php", {
        'id': id,
        'name': name,
        'file_extension': fileExtension,
        'bill_number': billNumber,
        'preparated_date': preparatedDate,
        'issue_date': issueDate,
        "expiration_date": expirationDate,
        'sending_date': sendingDate,
        'collecting_date': collectingDate,
        'observations': observations,
        'service_company_client_id': serviceCompanyClientId,
    });
}

function getLastOperationDateOfCorrelativeBill(billId) {
    return ajaxPost("ajax/getLastOperationDateOfCorrelativeBill.php", {
        "bill_id": billId,
    });
}

function nullifyBill(id, operationDate) {
    return ajaxPost("ajax/nullifyBill.php", {
        "id": id,
        "operation_date": operationDate,
    });
}


function refreshPendingBills(){
    return ajaxGet("ajax/refreshPendingBills.php");
}

function recordBills(){
    return ajaxGet("ajax/recordBills.php");
}

function recordBillsOfCompany(companyId){
    return ajaxPost("ajax/recordBillsOfCompany.php", {
        "company_id": companyId,
    });
}

function accountBills(){
    return ajaxGet("ajax/accountBills.php");
}

function collectBill(id, collectingDate, bankAccountingAccountId, bankAccountingAccountName, bankAccountingAccountNumber) {
    return ajaxPost("ajax/collectBill.php", {
        "id": id,
        "collecting_date": collectingDate,
        "bank-accounting-account_id": bankAccountingAccountId,
        "bank-accounting-account_name": bankAccountingAccountName,
        "bank-accounting-account_number": bankAccountingAccountNumber,
    });
}

// CRUD Periodicities
function getPeriodicities(){
    return ajaxGet("ajax/getPeriodicities.php");
}

//Send bill with Phpmailer
function sendBill(id, companyId, recipients, carbonCopyRecipients, blindCarbonCopyRecipients, senderEmail, senderPassword, subject, message, filePath) {
    return ajaxPost("ajax/sendBill.php", {
        "id": id,
        "company_id": companyId,
        "recipients": recipients,
        'carbon_copy_recipients': carbonCopyRecipients,
        'blind_carbon_copy_recipients': blindCarbonCopyRecipients,
        'sender_email': senderEmail,
        'sender_password': senderPassword,
        'subject': subject,
		'message': message,
        'file_path': filePath
    });
}

//Send email with Phpmailer
function sendEmail(recipients, carbonCopyRecipients, blindCarbonCopyRecipients, subject, message, attachments) {
    return ajaxPost("ajax/sendEmail.php", {
        "recipients": recipients,
        'carbon_copy_recipients': carbonCopyRecipients,
        'blind_carbon_copy_recipients': blindCarbonCopyRecipients,
        'sender_email': senderEmail,
        'sender_password': senderPassword,
        'subject': subject,
		'message': message,
        'attachments': attachments
    });
}

function getAccountingAccountByCompanyIdAndSubserviceId(companyId, subserviceId){
    return ajaxPost("ajax/getAccountingAccountByCompanyIdAndSubserviceId.php", {
        "company_id": companyId,
        'subservice_id': subserviceId,
    });
}

// CRUD ivaTypes
function getIvaTypes(){
    return ajaxGet("ajax/getIvaTypes.php");
}

function getIvaTypeById(id) {
    return ajaxPost("ajax/getIvaTypeById.php", {
        'id': id
    });
}

function getIvaExemptionTexts(){
    return ajaxGet("ajax/getIvaExemptionTexts.php");
}

// CRUD retentionTypes
function getRetentionTypes(){
    return ajaxGet("ajax/getRetentionTypes.php");
}

// CRUD accounting-accounts
function addAccountingAccount(companyId, subserviceId, accountingAccountNumber) {
    return ajaxPost("ajax/addAccountingAccount.php", {
        'company_id': companyId,
        'subservice_id': subserviceId,
        'accounting_account_number': accountingAccountNumber,
    });
}

function setAccountingAccount(id, accountingAccountNumber) {
    return ajaxPost("ajax/setAccountingAccount.php", {
        'id': id,
        'accounting_account_number': accountingAccountNumber,
    });
}
// CRUD bank-accounting-accounts
function addBankAccountingAccount(name, number, companyId) {
    return ajaxPost("ajax/addBankAccountingAccount.php", {
        'name': name,
        'number': number,
        'company_id': companyId,
    
    });
}

function setBankAccountingAccount(id, name, number) {
    return ajaxPost("ajax/setBankAccountingAccount.php", {
        'id': id,
        'name': name,
        'number': number,
    });
}

function getBankAccountingAccountsOfCompany(companyId) {
    return ajaxPost("ajax/getBankAccountingAccountsOfCompany.php", {
        "company_id": companyId,
    });
}

function getBankAccountingAccountById(id) {
    return ajaxPost("ajax/getBankAccountingAccountById.php", {
        "id": id,
    });
}

function setBankAccountingAccount(id, name, number) {
    return ajaxPost("ajax/setBankAccountingAccount.php", {
        'id': id,
        'name': name,
        'number': number,
    });
}

function disableBankAccountingAccount(bankAccountingAccountId){
    return ajaxPost("ajax/disableBankAccountingAccount.php", {
        'bank_accounting_account_id':bankAccountingAccountId,
    });
}
// CRUD Controls
function getControls(){
    return ajaxGet("ajax/getControls.php");
}

function getControlsOfServiceCompanyClientSubservice(serviceCompanyClientSubserviceId){
    return ajaxPost("ajax/getControlsOfServiceCompanyClientSubservice.php", {
        "service_company_client_subservice_id": serviceCompanyClientSubserviceId,
    });
}

function addControlsToServiceCompanyClient(serviceCompanyClientSubserviceId){
    return ajaxPost("ajax/addControlsToServiceCompanyClient.php", {
        "service_company_client_subservice_id": serviceCompanyClientSubserviceId,
    });
}

function checkControl(id){
    return ajaxPost("ajax/checkControl.php", {
        "id": id,
    });
}

// CRUD Billing
function getBillingOfBill(billId){
    return ajaxPost("ajax/getBillingOfBill.php", {
        "bill_id": billId,
    });
}

function getBillingToRecord(){
    return ajaxGet("ajax/getBillingToRecord.php");
}

function getBillingOfCompanyToRecord(companyId){
    return ajaxPost("ajax/getBillingOfCompanyToRecord.php", {
        "company_id": companyId,
    });
}

function getBillsToAccount(){
    return ajaxGet("ajax/getBillsToAccount.php");
}

// CRUD Permissions
function getPermissions(){
    return ajaxGet("ajax/getPermissions.php");
}


//Summernote
function initializeSummernote(id){
    var summernote;
    if ($(id).next('.note-editor').length === 0) {
        summernote = $(id).summernote({
            // onPaste: function (e) {
            //     var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
            //     e.preventDefault();
            //     document.execCommand('insertText', false, bufferText);
            // },
            // onpaste: function (e) {
            //     var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
            //     e.preventDefault();
            //     setTimeout(function () {
            //         document.execCommand('insertText', false, bufferText);
            //         $(this).parent().siblings('.summernote').destroy();                        
            //     }, 10);
            // },
            
            // placeholder: 'Ponme cosas aquí',
            tabsize: 2,
            height: 580,
          
            // cleanedText:'code',
           
            
            // maxHeight: "80%",

            // fontNames: ['Arial'],

            toolbar: [
                ['paperSize',['paperSize']],
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']],
                // ['paperSize',['paperSize']], // The Button
            ],
            // fontNames: ['Comic Sans MS'],
           
            // popover: {
            //     table: [
            //         ['merge', ['jMerge']],
            //         ['style', ['jBackcolor', 'jBorderColor', 'jAlign', 'jAddDeleteRowCol']],
            //         ['info', ['jTableInfo']],
            //         ['delete', ['jWidthHeightReset', 'deleteTable']],
            //     ]
            // },
           
            jTable : {
                /**
                 * drag || dialog
                 */
                mergeMode: 'drag'
            },
            callbacks: {
                
                // onPaste: function (e) {
                //     var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
        
                //     e.preventDefault();
        
                //     // Firefox fix
                //     setTimeout(function () {
                //         document.execCommand('insertText', false, bufferText);
                //     }, 10);
                // },
                // onpaste: function (e) {
                //     var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                
                //     e.preventDefault();
                
                //     setTimeout( function(){
                //         document.execCommand( 'insertText', false, bufferText );
                //     }, 10 );
                // },
                
                onFocus: function() {
                    console.log('Editable area is focused');
                },

                // onpaste: function (e) {
                //     var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                
                //     e.preventDefault();
                
                //     setTimeout( function(){
                //         document.execCommand( 'insertText', false, bufferText );
                //     }, 10 );
                // }
                // onpaste: function(e) {
                //     var thisNote = $(this);
                //     var updatePastedText = function(someNote){
                //         var original = someNote.code();
                //         var cleaned = CleanPastedHTML(original); //this is where to call whatever clean function you want. I have mine in a different file, called CleanPastedHTML.
                //         someNote.code('').html(cleaned); //this sets the displayed content editor to the cleaned pasted code.
                //     };
                //     setTimeout(function () {
                //         //this kinda sucks, but if you don't do a setTimeout, 
                //         //the function is called before the text is really pasted.
                //         updatePastedText(thisNote);
                //     }, 10);
        
        
                // }

                onpaste: function (e) {
                    var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
            
                    e.preventDefault();
            
                    document.execCommand('insertText', false, bufferText);
                }

                // onpaste: function(e) {
                //     var thisNote = $(this);
                //     var updatePastedText = function(someNote){
                //         var original = someNote.code();
                //         var cleaned = CleanPastedHTML(original); //this is where to call whatever clean function you want. I have mine in a different file, called CleanPastedHTML.
                //         someNote.code('').html(cleaned); //this sets the displayed content editor to the cleaned pasted code.
                //     };
                //     setTimeout(function () {
                //         //this kinda sucks, but if you don't do a setTimeout, 
                //         //the function is called before the text is really pasted.
                //         updatePastedText(thisNote);
                //     }, 10);
        
        
                // }
                
            }
            
              
        });
        // $('#summernote').summernote(
        //     {
        //         height: 200,
        //         focus: true
        //     }
        // );
        // $('div.note-editable').height($(window).height()* 0.7);
        // $(id).height($(window).height()* 0.8);
    } else {
        summernote = $(id);
    }

    return summernote;
    
}

//Summernote tiny
// function initializeSummernoteTiny(id){
//     var summernote;
//     if ($(id).next('.note-editor').length === 0) {
//         summernote = $(id).summernote({
//             // onPaste: function (e) {
//             //     var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
//             //     e.preventDefault();
//             //     document.execCommand('insertText', false, bufferText);
//             // },
//             // onpaste: function (e) {
//             //     var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
//             //     e.preventDefault();
//             //     setTimeout(function () {
//             //         document.execCommand('insertText', false, bufferText);
//             //         $(this).parent().siblings('.summernote').destroy();                        
//             //     }, 10);
//             // },
            
//             // placeholder: 'Ponme cosas aquí',
//             tabsize: 2,
//             height: 200,
//             // cleanedText:'code',
           
            
//             // maxHeight: "80%",

//             // fontNames: ['Arial'],

//             toolbar: [
                
//                 ['style', ['style']],
//                 ['font', ['bold', 'underline', 'clear']],
//                 ['color', ['color']],
//                 ['fontname', ['fontname']],
//                 ['undo', ['undo', 'redo']],
//                 ['fontsize', ['fontsize']],
//                 ['para', ['ul', 'ol', 'paragraph']],
//                 // ['insert', ['link', 'picture', 'video']],
//                 // ['view', ['fullscreen', 'codeview', 'help']],
//                 // ['paperSize',['paperSize']], // The Button
//             ],
//             // fontNames: ['Comic Sans MS'],
           
//             // popover: {
//             //     table: [
//             //         ['merge', ['jMerge']],
//             //         ['style', ['jBackcolor', 'jBorderColor', 'jAlign', 'jAddDeleteRowCol']],
//             //         ['info', ['jTableInfo']],
//             //         ['delete', ['jWidthHeightReset', 'deleteTable']],
//             //     ]
//             // },
           
//             jTable : {
//                 /**
//                  * drag || dialog
//                  */
//                 mergeMode: 'drag'
//             },
//             callbacks: {
                
//                 // onPaste: function (e) {
//                 //     var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
        
//                 //     e.preventDefault();
        
//                 //     // Firefox fix
//                 //     setTimeout(function () {
//                 //         document.execCommand('insertText', false, bufferText);
//                 //     }, 10);
//                 // },
//                 // onpaste: function (e) {
//                 //     var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                
//                 //     e.preventDefault();
                
//                 //     setTimeout( function(){
//                 //         document.execCommand( 'insertText', false, bufferText );
//                 //     }, 10 );
//                 // },
                
//                 onFocus: function() {
//                     console.log('Editable area is focused');
//                 },

//                 // onpaste: function (e) {
//                 //     var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                
//                 //     e.preventDefault();
                
//                 //     setTimeout( function(){
//                 //         document.execCommand( 'insertText', false, bufferText );
//                 //     }, 10 );
//                 // }
//                 // onpaste: function(e) {
//                 //     var thisNote = $(this);
//                 //     var updatePastedText = function(someNote){
//                 //         var original = someNote.code();
//                 //         var cleaned = CleanPastedHTML(original); //this is where to call whatever clean function you want. I have mine in a different file, called CleanPastedHTML.
//                 //         someNote.code('').html(cleaned); //this sets the displayed content editor to the cleaned pasted code.
//                 //     };
//                 //     setTimeout(function () {
//                 //         //this kinda sucks, but if you don't do a setTimeout, 
//                 //         //the function is called before the text is really pasted.
//                 //         updatePastedText(thisNote);
//                 //     }, 10);
        
        
//                 // }

//                 onpaste: function (e) {
//                     var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
            
//                     e.preventDefault();
            
//                     document.execCommand('insertText', false, bufferText);
//                 }

//                 // onpaste: function(e) {
//                 //     var thisNote = $(this);
//                 //     var updatePastedText = function(someNote){
//                 //         var original = someNote.code();
//                 //         var cleaned = CleanPastedHTML(original); //this is where to call whatever clean function you want. I have mine in a different file, called CleanPastedHTML.
//                 //         someNote.code('').html(cleaned); //this sets the displayed content editor to the cleaned pasted code.
//                 //     };
//                 //     setTimeout(function () {
//                 //         //this kinda sucks, but if you don't do a setTimeout, 
//                 //         //the function is called before the text is really pasted.
//                 //         updatePastedText(thisNote);
//                 //     }, 10);
        
        
//                 // }
                
//             }
            
              
//         });
//         // $('#summernote').summernote(
//         //     {
//         //         height: 200,
//         //         focus: true
//         //     }
//         // );
//         // $('div.note-editable').height($(window).height()* 0.7);
//         // $(id).height($(window).height()* 0.8);
//     } else {
//         summernote = $(id);
//     }

//     return summernote;
    
// }

function makePdf(content, path, name) {
    return ajaxPost("ajax/makePdf.php", {
        "content": content,
        "path": path,
        "name": name
    });
}

function makePdf(id, fileName){
    var content;
    if ($(id).next('.note-editor').length === 0) {
        // content = $(id).val();
    } else {
        summernoteCode = $(id).summernote('code');
        var div = document.createElement('div');
        div.innerHTML = summernoteCode.trim();
        content = div;
    }
    var opt = {
        pagebreak: {
          mode: 'avoid-all',
          before: '#page2el'
        },
        margin: 1,
        filename: fileName+'.pdf',
        image: {
          type: 'jpeg',
          quality: 0.80
        },
        html2canvas: {
          scale: 2
        },
        jsPDF: {
          unit: 'in',
          format: 'a4',
          orientation: 'p'
        }
    };
    
    return html2pdf().from(content).set(opt).toContainer().toPdf().output('blob');
    // return html2pdf().from(content).set(opt).toPdf().get('pdf').save();
}

function generatePdf(htmlElementId, path, fileBaseName){
    var htmlElement;
    if ($(htmlElementId).next('.note-editor').length === 0) {
        htmlElement = $(htmlElementId).val();
    } else {
        summernoteCode = $(htmlElementId).summernote('code');
        var div = document.createElement('div');
        div.innerHTML = summernoteCode.trim();
        htmlElement = div;
    }
    var options = {
        pagebreak: {
          mode: 'avoid-all',
          before: '#page2el'
        },
        margin: 1,
        filename: fileBaseName+'.pdf',
        image: {
          type: 'jpeg',
          quality: 0.90
        },
        html2canvas: {
          scale: 2
        },
        jsPDF: {
          unit: 'in',
          format: 'a4',
          orientation: 'p'
        }
    };

    html2pdf().from(htmlElement).set(options).toContainer().toPdf().output('blob').then(function (output) {
        file = new File([output], fileBaseName+".pdf");
        uploadedFile = uploadFile(file, path);
        if(uploadedFile){
            toastr.success("Pdf generado correctamente");
        } else {
            toastr.error("No se ha podido generar el pdf");
        }
    });

    // output = html2pdf().from(htmlElement).set(options).toContainer().toPdf().output('blob');
    // file = new File([output], fileBaseName+".pdf");
    // uploadedFile = uploadFile(file, path);
    // return uploadedFile;

}


function initializePdf(id){

    $("#download").click(function () {
        summernoteCode = $(id).summernote('code');
        //   console.log(summernoteCode);
    
        //   var invoice = document.getElementById("summer");
        //     console.log(invoice);
    
        var div = document.createElement('div');
        div.innerHTML = summernoteCode.trim();
        summer = div;


        
        // invoice = this.document.getElementById("invoiceuno");
        // console.log(summer);
        // console.log(window);
    
    
        var opt = {
          pagebreak: {
            mode: 'avoid-all',
            before: '#page2el'
          },
          margin: 1,
          filename: 'myfile.pdf',
          image: {
            type: 'jpeg',
            quality: 0.80
          },
          html2canvas: {
            scale: 2
          },
          jsPDF: {
            unit: 'in',
            format: 'a4',
            orientation: 'p'
          },
          // pdfHeader:pdfHeader,
    
        };
    
        html2pdf().from(summer).set(opt).toPdf().get('pdf').then(function (pdf) {
          var totalPages = pdf.internal.getNumberOfPages();
    
          //print current pdf width & height to console
          // console.log("getHeight:" + pdf.internal.pageSize.getHeight());
          // console.log("getWidth:" + pdf.internal.pageSize.getWidth());
    
    
          for (var i = 1; i <= totalPages; i++) {
    
            pdf.setPage(i);
            pdf.setFontSize(10);
            pdf.setTextColor(150);
    
            // var img = new image(100,200);
            // img.src = "jmsLogo.png";
            // const canvas = Buffer.alloc(width * height * channels, rgbaPixel);
    
    
            //divided by 2 to go center
            // pdf.text('Page ' + i + ' of ' + totalPages, pdf.internal.pageSize.getWidth, pdf.internal.pageSize.getHeight-3);
    
            // pdf.text(img + totalPages ,(pdf.internal.pageSize.getWidth()/2), (0.3));
            pdf.text('Página' + i + ' de ' + totalPages, (pdf.internal.pageSize.getWidth() / 2), (pdf.internal
              .pageSize.getHeight() - 0.3));
          }
    
        }).save();
    
      });
    
      // function pdfCallback(pdfObject) {
      //     var number_of_pages = pdfObject.internal.getNumberOfPages()
      //     var pdf_pages = pdfObject.internal.pages
      //     var myFooter = "Footer info"
      //     for (var i = 1; i < pdf_pages.length; i++) {
      //         // We are telling our pdfObject that we are now working on this page
      //         pdfObject.setPage(i)
      //         // The 10,200 value is only for A4 landscape. You need to define your own for other page sizes
      //         pdfObject.text(myFooter, 10, 200)
      //     }
      // }
    
      function pdfHeader(doc) {
        doc.text(150, 285, 'page ' + doc.page); //print number bottom right
    
      }
}

function trunc (x, posiciones = 0) {
    var s = x.toString()
    var l = s.length
    var decimalLength = s.indexOf('.') + 1
    var numStr = s.substr(0, decimalLength + posiciones)
    return Number(numStr)
}

function padLeadingZeros(num, size) {
    var s = num+"";
    while (s.length < size) s = "0" + s;
    return s;
}

function padLeadingSpaces(text, size) {
    var s = text+"";
    while (s.length < size) s = s +" " ;
    return s;
}

function financial(x) {
    return Number.parseFloat(x).toFixed(2);
}

function validateCif(cif){

    const c = /^([ABCDEFGHJKLMNPQRSUVW])(\d{7})([0-9A-J])$/;

    return  c.test(cif);

}

function validateDni(dni){

    const c = /^(\d{8})([A-Z])$/;
   
    return  c.test(dni);
   
}

function validateAccountingAccount(accountingAccount){

    const c = /^[0-9]{12}$/;

    return  c.test(accountingAccount);

}

function vadidateEmail(email){

    const e = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
    // const e = /^(?:[^<>()[\].,;:\s@"]+(\.[^<>()[\].,;:\s@"]+)*|"[^\n"]+")@(?:[^<>()[\].,;:\s@"]+\.)+[^<>()[\]\.,;:\s@"]{2,63}$/i
    
    return e.test(email);
}

function validateStartingDate(startingDate){
    const cantDays = 10;

    var fechaIngresada = new Date(startingDate[2],startingDate[1] - 1, startingDate[0],0,0,0,0);
    
    var fechaLimite = new Date();

    fechaLimite.setHours(0,0,0,0);
    fechaLimite.setDate(fechaLimite.getDate() + cantDays);

    //Validation

    if(fechaIngresada >= fechaLimite){
        return true;
    }else{
        return false;
    }

    // return fechaIngresada >= fechaLimite ? alert("fecha incorrecta") : alert("Fecha válida ");
}

function s2ab(s) {
    var buf = new ArrayBuffer(s.length);
    var view = new Uint8Array(buf);
    for (var i=0; i!=s.length; ++i) view[i] = s.charCodeAt(i) & 0xFF;
    return buf;
}

function excelBookToBlob(book){
    var out= XLSX.write(book, {bookType: 'xlsx', bookSST: true, type: 'binary'});
    let blob = new Blob([s2ab(out)],{type:"application/octet-stream"});
    return blob;
}

// Remember that the month is 0-based so February is actually 1...
function isValidDate(year, month, day) {
    var d = new Date(year, month, day);
    if (d.getFullYear() == year && d.getMonth() == month && d.getDate() == day) {
        return true;
    }
    return false;
}

//valida la fecha 
function isValidDateOneParam(d) {
    return d instanceof Date && !isNaN(d);
}

  //Enable you to scroll in navbar
function scrollableNavTab(navTab){
    const scrollContainer = document.querySelector(navTab);

    scrollContainer.addEventListener("wheel", (evt) => {
        evt.preventDefault();
        scrollContainer.scrollLeft += evt.deltaY;
    });
}

//add search engine in container

function searchInputContainer(container,searchInput){
    $(searchInput).keyup(function(){
    console.log($(this).val());
        var nombres = $(container).find('.card-title');
        var buscando = $(this).val().normalize('NFD')
        .replace(/([aeio])\u0301|(u)[\u0301\u0308]/gi,"$1$2")
        .normalize().toLowerCase();
        var item='';
        for( var i = 0; i < nombres.length; i++ ){
            item = $(nombres[i]).html().normalize('NFD')
            .replace(/([aeio])\u0301|(u)[\u0301\u0308]/gi,"$1$2")
            .normalize().toLowerCase();
            for(var x = 0; x < item.length; x++ ){
                if( buscando.length == 0 || item.indexOf( buscando ) > -1 ){
                    $(nombres[i]).parents('.mb-4').show(); 
                }else{
                    $(nombres[i]).parents('.mb-4').hide();
                }
            }
        }
        
    });
}
//Filter cards using buttons 
function cardFilterButton(myClas){
    $('button').on('click', function(){
        // alert("ey")
        const cards = document.querySelectorAll(myClas);
        for(card of cards){
        
            const cardCategory = card.getAttribute('category');
            const categoryOne = this.getAttribute('category-one');
            const categoryTwo = this.getAttribute('category-two');
            
            if(cardCategory ===  categoryOne || cardCategory ===  categoryTwo || categoryOne === 'all' ){
                card.style.display = 'block';
            
            }else{
                card.style.display = 'none'
                // card.hide()

            }
        }
    });
}


//Funcion para validar una fecha por
function validatedate(inputText) {
    var dateformat = /^(0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])[\/\-]\d{4}$/;
    // Match the date format through regular expression
    if (inputText.value.match(dateformat)) {
        document.form1.text1.focus();
        //Test which seperator is used '/' or '-'
        var opera1 = inputText.value.split('/');
        var opera2 = inputText.value.split('-');
        lopera1 = opera1.length;
        lopera2 = opera2.length;
        // Extract the string into month, date and year
        if (lopera1 > 1) {
            var pdate = inputText.value.split('/');
        } else if (lopera2 > 1) {
            var pdate = inputText.value.split('-');
        }
        var mm = parseInt(pdate[0]);
        var dd = parseInt(pdate[1]);
        var yy = parseInt(pdate[2]);
        // Create list of days of a month [assume there is no leap year by default]
        var ListofDays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        if (mm == 1 || mm > 2) {
            if (dd > ListofDays[mm - 1]) {
                alert('Invalid date format!');
                return false;
            }
        }
        if (mm == 2) {
            var lyear = false;
            if ((!(yy % 4) && yy % 100) || !(yy % 400)) {
                lyear = true;
            }
            if ((lyear == false) && (dd >= 29)) {
                alert('Invalid date format!');
                return false;
            }
            if ((lyear == true) && (dd > 29)) {
                alert('Invalid date format!');
                return false;
            }
        }
    } else {
        alert("Invalid date format!");
        document.form1.text1.focus();
        return false;
    }
}

//Redondea los numeros decimales de los numeros negativos hacia abajo 
function round(num) {
    var m = Number((Math.abs(num) * 100).toPrecision(15));
    return Math.round(m) / 100 * Math.sign(num);
}