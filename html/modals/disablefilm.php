<div class="modal fade" id="documentDisable" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">¿Estás seguro?</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <h5>El siguiente archivo pasará a estar inactivo: </h5>
        <p id="documentDisableModalFileName"></p>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="permanentlyDelete">
          <label class="form-check-label text-danger" for="permanentlyDelete">Borrar archivo permanentemente</label>
        </div>
      </div>
      <div class="modal-footer">
        <button id="documentDisableAccept" type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>