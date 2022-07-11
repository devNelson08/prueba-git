

$("#nav-films-tab").click(function(){
    supplierDetailsBillsFillView();
    console.log(getFilms());
});

// $("#fileUploaderSave").one( "click", function(){
$("#fileUploaderSave").click(function(){
    // supplierDetailsFileUploaderSave();
    supplierDetailsBillsFillView();
});

function supplierDetailsBillsFillView(){
    if ( ! $.fn.DataTable.isDataTable( '#films' ) ) {
        table = $('#films').DataTable( {
            responsive:true,
            dom: 'Bfrtip',
            buttons: [

                {
                    extend: 'copyHtml5',
                    text: '<span ><i class="far fa-copy fa-2x fs-6"></i></span>',
                    // titleAttr: 'Copy'
                    className: 'btn btn-primary',
                    // exportOptions:{
                    //     columns:'visible'
                    // },
                    init: function (api, node, config) {
                        $(node).removeClass('dt-button')
                    }

                },
                {
                    extend: 'excelHtml5',
                    text: ' <span class=""><i class="far fa-file-excel fa-2x fs-6"></i></span>',
                    // className:      'btn btn-primary',
                    // titleAttr: 'Excel'
                    className: 'btn btn-primary',

                    init: function (api, node, config) {
                        $(node).removeClass('dt-button')
                    }
                },
                {
                    extend: 'csvHtml5',
                    charset: 'UTF-8', // arreglo ñ , acentos
                    bom: true, // arreglo ñ , acentos
                    text: ' <span class=""><i class="far fa-file-alt fa-2x fs-6"></i></span>',
                    // titleAttr: 'CSV'
                    className: 'btn btn-primary',

                    init: function (api, node, config) {
                        $(node).removeClass('dt-button')
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<span class=""><i class="far fa-file-pdf fa-2x fs-6"></i></span>',
                    // titleAttr: 'PDF',
                    className: 'btn btn-primary',

                    init: function (api, node, config) {
                        $(node).removeClass('dt-button')
                    }
                }
            ],

            "ajax": {
                "url": "ajax/getFilms.php",
                "type": "POST",
                dataSrc: '',
                // data: {"supplier_id": supplier["id"]},
                },

            columns: [
                { "data": "name"},
                { "data": "categories_name"},
                { "data": "directors_name"},
                { "data": "signup_date"},
                // { "defaultContent": "<button class='download btn btn-primary btn-sm mr-2'>Descargar</button><button class='edit btn btn-warning btn-sm mr-2'>Editar</button><button class='delete btn btn-danger btn-sm'>Eliminar</button>" }
                { "defaultContent": "<a href='#' class='preview mr-2'><i class='fas fa-eye' title='Previsualizar'></i></a>"
                    +"<a href='#'class='download  mr-2'><i class='fas fa-download' style='width: 20px; height: 20px;color:#2EF522'></i></a>"
                    +"<a href='#' class='edit mr-2' style='width: 20px; height: 20px;color:#F5F043s'><i class='fas fa-edit'></i></a>"
                    +"<a href='#' class='delete ' style='width: 20px; height: 20px;color:#F25C52'><i class='fas fa-trash-alt'></i></a>"
                }
            ]
        } );
        
        $('#films tbody').on( 'click', '.preview', function () {
            // var bill = billsTable.row( $(this).parents('tr') ).data();
            var supplierBill = table.row( $(this).parents('tr') ).data();
            alert(supplierBill['name'])
            // $('#selectionModalTitle').html("Visualización de factura");
            // $("#selectionModalBody").html('<div id="previewPdfSuppliersContainer" class="display"></div>');
            // path = "documents/supplier-bills/"+supplier["id"]+"/"+supplierBill[ "name" ]+"."+supplierBill["file_extension"];

            // $("#previewPdfSuppliersContainer").append(''
            // +'<div class="container"> '
            //     +'<div id="viewpdf" style=" height: 40em;" > '
            //         +'<iframe id="pdf_preview" src="'+path+'" class="embed-responsive-item h-100 w-100" ></iframe> '
            //     +'</div>'
            // +'</div>'
            // );
            // $('#selectionModalFooter').html(''
            // // // +'<input type="date" class="mr-auto" id="start" name="trip-start" value="2018-07-22" min="2021-01-01" max="2021-12-31">'
            // // // +'<input type="button" class="btn btn-info" id="billPreview" value="Generar factura">'
            // );
            
            // $("#selectionModal").modal("toggle");
        });
        // supplierBill = table.row( $(this).parents('tr') ).data();

        // $('#films tbody').on( 'click', '.download', function () {
        //     var supplierBill = table.row( $(this).parents('tr') ).data();
        //     path = "documents/supplier-bills/"+supplier["id"]+"/"+supplierBill[ "name" ]+"."+supplierBill["file_extension"];
        //     var a = document.createElement("a");
        //     a.href = path;
        //     a.setAttribute("download", supplierBill["name"]+"."+supplierBill["file_extension"]);
        //     // a.setAttribute("download", supplier["name"]+"_"+supplierBill["name"]+"."+supplierBill["file_extension"]);
        //     a.click();
        // } );

        // $('#films tbody').on( 'click', '.edit', function () {
        //     var supplierBill = table.row( $(this).parents('tr') ).data();
        //     // alert(supplierBill)
        //     $("#documentEditModalFileName").html('<input id="documentEditModalFileInputName" class="form-control text-center" value="'+ supplierBill["name"] +'"></input>');
        //     $("#documentEditModalDescription").html('<textarea id="documentEditModalInputDescription" rows="6" cols="40" class="form-control text-center" value=""> '+supplierBill["description"]+'</textarea>');
        //     $("#documentEditModal").modal("toggle");
        //     $("#documentEditModalAccept").off().click(function(){
        //         var id = supplierBill["id"];
        //         var name = $("#documentEditModalFileInputName").val();
        //         var description = $("#documentEditModalInputDescription").val();
        //         if(name){
        //             if (setSupplierDocument(id, name, description)){
        //                 toastr.success("Cambios en documento guardados");
        //                 table.ajax.reload();
        //                 $("#documentEditModal").modal("toggle");
        //             } else {
        //                 toastr.error("No se han podido guardar los cambios");
        //             }
        //         } else {
        //             toastr.error("El nombre del archivo no puede quedar vacío");
        //         }
        //     });
        // } );


        // $('#films tbody').on( 'click', '.delete', function () {
        //     var supplierBill = table.row( $(this).parents('tr') ).data();
        //     $("#documentDisableModalFileName").html("Suppliere: "+supplier["name"]+"<br>Archivo: "+supplierBill["name"]+"."+supplierBill["file_extension"]);
        //     $("#documentDisable").modal("toggle");
        //     $("#documentDisableAccept").off().click(function(){
        //         if($("#permanentlyDelete").prop('checked')){
        //             deleteFile("documents/supplier-bills/"+supplier["id"]+"/"+supplierBill["name"]+"."+supplierBill["file_extension"]);
        //             deleteSupplierDocument(supplierBill["id"]);
        //         } else {
        //             disableSupplierDocument(supplierBill["id"]);
        //         }
                
        //         table.ajax.reload();
        //     });

        //     // e.preventDefault();

        //     // $.ajax({
        //     //     url: 'ajax/deleteFileEmployees.php',
        //     //     type: 'post',
        //     //     dataType: 'json'
        //     // })
        //     // .done(function() {
        //     //     alert("Eliminado correctamente!");
        //     // })
        //     // .fail(function() {
        //     //     alert("Ha ocurrido un error");
        //     // })
        //     // .always(function() {
                
        //     // });

        // } );

        
        
    } else {
        table.ajax.reload();
    }
}

// function supplierDetailsFileUploaderSave(){
//     if($('#fileUploaderInputFile')[0].files[0]){
//         // var formData = new FormData();
//         // formData.append("file", $('#fileUploaderInputFile')[0].files[0]);
//         if (uploadedFile = uploadValidatedFile($('#fileUploaderInputFile')[0].files[0], "bills/suppliers/"+supplier["id"]+"/")){
//             // fileName = uploadedFile["fileName"].split(".");
//             // fileBaseName = fileName[0];
//             // fileExtension = fileName[1];
//             description = $("#fileUploaderInputDescription").val();
    
//             addSupplierDocument(uploadedFile['file_base_name'], uploadedFile['file_extension'], description, supplier["id"]);
    
//             $("#fileUploader").modal("toggle");
    
//             if(uploadedFile["fileExists"]){
//                 // toastr.success("Ya existía un archivo con el mismo nombre así que se ha renombrado: <br>" + uploadedFile["fileName"]);
//                 $("#informationModalTitle").html("¡Atención!");
//                 $("#informationModalBody").html("Ya existía un archivo con ese nombre así que se ha renombrado: <br>" + uploadedFile["fileName"]);
//                 $("#informationModalButton").html("Continuar");
//                 $("#informationModal").modal("toggle");
//                 toastr.success("Archivo subido correctamente");
    
//             } else {
//                 toastr.success("Archivo subido correctamente");
//             }
//         } else {
//             toastr.error("No se ha podido subir el archivo");
//         }
//     } else {
//         toastr.error("Todavía no se ha seleccionado ningún archivo.");
//     }
// }