"use strict";

$(document).ready(function(){  
    console.log('config.js has been loaded.');
}); 

//global variable for all page  
var api = ''; 
var routes = { 
};
let main_cart;
var main_cart_other;

//
// Requests GET | POST
//
function post(url, request, callback) {
    $.ajax({
        url: api + url,
        type: "POST",
        dataType: "json",
        data: request,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) { 

            if(data.status == 500){
                //showWarning('',data.message, function(){});
                return;
            } 

            callback(data);
        },
        error: function (data) {
            console.log(data);
            // showError('Server error' + data, 'Please ask the system administrator about this problem!', function () {

            // });
        }
    });
}

function postWithHeader(url, request, callback) { 
    $.ajax({
        url: api + url,
        type: "POST",
        dataType: "json",
        data: request,
        headers: {
            //'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 
            'Authorization':    'Bearer '+getStorage('api_token'),
            'Accept':           'application/json'
        },
        beforeSend: function (xhr) {
            //xhr.setRequestHeader('Authorization', 'Bearer '+getStorage('api_token') );
        },
        success: function (data) {

            if(data.status == 401){
                clearStorage();
                redirectTo('/login');
                return;
            }

            if(data.status == 500){
                showWarning('',data.message, function(){});
                return;
            }

            callback(data);
        },
        error: function (data) {
            console.log(data, data.status);
            showError('Server error', 'Please ask the system administrator about this problem!', function () {

            });
        }
    });
}

function get(url, request, callback) {
    $.ajax({
        url: api + url,
        type: "GET",
        dataType: "json",
        data: request,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {  

            callback(data);
        },
        error: function (data) {
            showError('Server error', 'Please ask the system administrator about this problem!', function () {

            });
        }
    });
}

function getWithHeader(url, request, callback) {
    $.ajax({
        url: api + url,
        type: "GET",
        dataType: "json",
        data: request,
        headers: {
            //'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Authorization':    'Bearer '+getStorage('api_token'),
            'Accept':           'application/json'
        },
        success: function (data) {  
            
            if(data.status == 401){
                clearStorage();
                redirectTo('/login');
                return;
            }

            if(data.status == 500){
                showWarning('',data.message, function(){});
                return;
            }

            callback(data);
        },
        error: function (data) {
            showError('Server error', 'Please ask the system administrator about this problem!', function () {

            });
        }
    });
}

function customPost(url, request, callback) {
    $.ajax({
        url: url,
        type: "POST",
        dataType: "json",
        data: request,
        headers: {
            //'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
            callback(data);
        },
        error: function (data) {
            console.log(data);
            showError('Server error', 'Please ask the system administrator about this problem!', function () {

            });
        }
    });
}

function redirectTo(link) {
    window.location.href = link;
}

function showAlert(title,message,_class,container){
    var con = $('#'+container);
    con.empty();
    con.append(
        '<div class="alert alert-dismissible '+_class+'">'+
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                '<span aria-hidden="true">×</span>'+
            '</button>'+
            '<strong>'+title+'</strong> '+ message +
        '</div>'
    ); 
}



function showSuccess(title, message, callback) { 

    iziToast.success({
        title: title,
        message: message,
        position: 'bottomLeft',
        onClosed: function () {
            callback();
        },
        displayMode : 'replace'
    });

}

function showWarning(title, message, callback) {
    iziToast.warning({
        title: title,
        message: message,
        position: 'bottomLeft',
        onClosed: function () {
            callback();
        },
        displayMode : 'replace'
    });
}

function showError(title, message, callback) {
    iziToast.error({
        title: title,
        message: message,
        position: 'bottomLeft',
        onClosed: function () {
            callback();
        },
        displayMode : 'replace'
    });
}

function getParams(id) {
    var urlParams = new URLSearchParams(window.location.search);
    var x = urlParams.get(id); //getting the value from url parameter
    return x;
}



function cl(arr = arr() ){
    arr.forEach(element => {
        console.log(element);
    });
}

function text_truncate(str, length, ending) {
    if (length == null) {
      length = 100;
    }
    if (ending == null) {
      ending = '...';
    }
    if (str.length > length) {
      return str.substring(0, length - ending.length) + ending;
    } else {
      return str;
    }
};

function FormatNumberLength(num, length) {
    var r = "" + num;
    while (r.length < length) {
        r = "0" + r;
    }
    return r;
}

function getBase64Image(img) {
    // Create an empty canvas element
    var canvas = document.createElement("canvas");
    canvas.width = img.width;
    canvas.height = img.height;

    // Copy the image contents to the canvas
    var ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);

    // Get the data-URL formatted image
    // Firefox supports PNG and JPEG. You could check img.src to
    // guess the original format, but be aware the using "image/jpg"
    // will re-encode the image.
    var dataURL = canvas.toDataURL("image/png");

    return dataURL.replace(/^data:image\/(png|jpg);base64,/, "");
}

// function numberWithCommas(number) {
//     number = number.toFixed(2);
//     var parts = number.toString().split(".");
//     parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
//     return parts.join(".");
// } 

//=======================================================================
