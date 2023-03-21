$("#filmNewOpenCreator").on("click", function(){
    filmNewFillCreator();
});
$("#filmNewSave").click(function(){
    filmNewSave();
    
});

// Function that fills department details with inputs to edit
function filmNewFillCreator(){
    $("#filmNewName").html('<input id="filmNewInputName" class="form-control text-center" value=""></label>');
    $("#filmNewCategory").html('<input id="filmNewInputCategory" class="form-control text-center" value=""></label>');
    $("#filmNewDirector").html('<input id="filmNewInputDirector" class="form-control text-center" value=""></label>');
}

// Function that set a department with the information in the formulary
function filmNewSave(){
    var name = $("#filmNewInputName").val();
    var categoryId = $("#filmNewInputCategory").val();
    var directorId = $("#filmNewInputDirector").val();

    if(name){
        if (addFilm(name, categoryId, directorId)){
            $('#exampleModal').modal('toggle');
            toastr.success("Película  registrada correctamente");
        } else {
            toastr.error("No se ha podido dar de alta el departamento");
        }
    } else {
        toastr.error("El nombre del departamento es obligatorio");
    }
}