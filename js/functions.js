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


// get films
function getFilms() {
    return ajaxGet("ajax/getFilms.php");
}


// CRUD films
function addFilm(name, categoryId, directorId) {
    return ajaxPost("ajax/addFilm.php", {
        "name": name,
        "category_id": categoryId,
        "director_id": directorId,
        // 'signup_date': signupDate,
    });
}

function disableFilm(id){
    return ajaxPost("ajax/disableFilm.php", {
        'id':id,
    });
}

// Directors
function getDirectors(){
    return ajaxGet("ajax/getDirectors.php");
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