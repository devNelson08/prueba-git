


<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
  <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filmNewOpenCreator">Nueva película</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

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
                                <div class="form-outline">
                                    <select name="Category" class="form-select filmCategory" id="filmNewInputCategory">
                                    <option value="0" class="text-center">Seleccione Director</option>
                                    <option value="1" class="text-center">Steven Spielberg</option>
                                    <option value="2" class="text-center">Martin Scorsese</option>
                                    <option value="3" class="text-center">Tim Burton</option>
                                    <option value="4" class="text-center">Christopher Nolan</option>
                                    <?php
                                        
                                        // foreach (getCategories() as $categories)
                                        // echo('<option value="" class="text-center">'.$categories["name"].'</option>');
                                    ?>
                                            

                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form_dato">
                                <div class="form-outline">
                                    <select name="Category" class="form-select filmCategory" id="filmNewInputCategory">

                                        <option value="0" class="text-center">Seleccione categoria</option>
                                        <option value="1" class="text-center">Accion</option>
                                        <option value="2" class="text-center">Aventuras</option>
                                        <option value="3" class="text-center">Belico</option>
                                        <option value="4" class="text-center">Ciencia Ficcion</option>
                                    </select>
                                </div>
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