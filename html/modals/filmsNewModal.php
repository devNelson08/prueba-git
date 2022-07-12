
<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
  <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Nueva película</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
               
                <div class="container-md row">
                    <div class="row col-12 m-4">
                        <div class="col">
                            <div class="form_dato">
                                <div class="form-outline">
                                    <input type="text" id="filmNewInputName" class="form-control" value=""></input>
                                    <label class="form-label" for="filmNewInputName">Nombre </label></div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form_dato">
                                <div class="form-outline"><input type="text" id="filmNewInputCategory"
                                        class="form-control" value=""></input> <label class="form-label"
                                        for="filmNewInputCategory">Categoria *</label></div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form_dato">
                                <div class="form-outline"><input type="text" id="filmNewInputDirector"
                                        class="form-control" value=""></input> <label class="form-label"
                                        for="filmNewInputDirector">Director *</label></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <label class="mr-auto text-secondary">(*) Campos obligatorios</label>
                <button id="filmNewSave" class="btn btn-primary">Registrar película</button>
            </div>
        </div>
</div>