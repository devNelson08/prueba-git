<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

       <!-- Configuraciones generales -->
       <?php require "config/startup.php"; ?>
    <title>Prueba git hub</title>
  </head>
  <body>

   
    <main class="h-100 " style="padding-top: 58px">
    
    <div class="container-md">
        <!-- Content here -->

        <!-- Modal -->
        <?php //require "html/filmsNewModal.php"; ?>
  
  
        <!-- Navbar -->
        <?php require "html/navbar.php"; ?>

        </div>            
        
    </main>
    <?php require "html/modals/previewFilmModal.php"; ?>
    <?php require "html/modals/disableFilm.php"; ?>

    <?php require "html/modals/filmsNewModal.php"; ?>
    
    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    
  
    
    <!-- Jquery y bootstrap -->
    <?php require "config/endup.php"; ?>
    
    <script src="js/index.js"></script>
    
  </body>
</html>