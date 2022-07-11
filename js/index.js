

$("#nav-films-tab").click(function(){
    filmsDetailsFillView();
    console.log(getFilms());
});

// $("#fileUploaderSave").one( "click", function(){
$("#fileUploaderSave").click(function(){
    // supplierDetailsFileUploaderSave();
    filmsDetailsFillView();
});

function filmsDetailsFillView(){
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
                    // +"<a href='#'class='download  mr-2'><i class='fas fa-download' style='width: 20px; height: 20px;color:#2EF522'></i></a>"
                    // +"<a href='#' class='edit mr-2' style='width: 20px; height: 20px;color:#F5F043s'><i class='fas fa-edit'></i></a>"
                    // +"<a href='#' class='delete ' style='width: 20px; height: 20px;color:#F25C52'><i class='fas fa-trash-alt'></i></a>"
                }
            ]
        } );
        
        $('#films tbody').on( 'click', '.preview', function () {
            // var bill = billsTable.row( $(this).parents('tr') ).data();
            var film = table.row( $(this).parents('tr') ).data();
            alert(film['name'])
           
        });
        // film = table.row( $(this).parents('tr') ).data();
        
        
    } else {
        table.ajax.reload();
    }
}

