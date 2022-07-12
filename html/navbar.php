<div class="card shadow-0">
  <div class="card-body">
    <ol class="breadcrumb mb-4">
      <li class="breadcrumb-item"><a href="">Peliculas</a></li>
      <li id="filmDetailsLinkName" class="breadcrumb-item active"></li>
    </ol>
    <!-- Tabs navs -->
    <nav class="mb-1">
      <div class="nav nav-tabs" id="myTab" role="tablist">
        <!-- <a class="nav-item nav-link letter_spacing_15 active" id="nav-info-tab" data-bs-toggle="tab" href="#nav-info"
          role="tab" aria-controls="nav-info letter_spacing_15" aria-selected="true">Información</a> -->
        <a class="nav-item nav-link" id="nav-films-tab" data-bs-toggle="tab" href="#nav-films" role="tab"
          aria-controls="nav-films" aria-selected="false">Peliculas Registradas</a>
      </div>
    </nav>
    <div class="tab-content" id="nav-tabContent">
      <div class="card shadow-0 tab-pane fade" id="nav-films" role="tabpanel" aria-labelledby="nav-films-tab">

        <div class="card-body main-card-body overflow-auto">
          <form class="form-inline m-1">
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
              Registrar película
            </button>
          </form>
          <table id="films" class="table table-hover w-100">
            <thead class="thead-dark">
              <tr>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Director</th>
                <th>Fecha de subida</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tfoot class="thead-dark">
              <tr>
                <th>Nombre</th>
                <th>Tipo de archivo</th>
                <th>Descripción</th>
                <th>Fecha de subida</th>
                <th>Acciones</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
    <!-- Tabs content -->
  </div>
</div>

<?php require "html/modals/filmsNewModal.php"; ?>