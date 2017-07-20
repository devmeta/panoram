// Elements for taking the snapshot
var canvas = document.getElementById('canvas')
, video = document.getElementById('video')
, context = canvas.getContext('2d')
, videoWidth
, videoHeight
, pos = []
, marker
, markers = []
, snapInterval = 0
, snapIndex = 0
, snapPeriodicity = 60
, posIndex = 0
, transmitir_start = function(){

    snapshot()

    $('.publish__container').fadeIn(2000)
    snapInterval = setInterval(function(){ 
        snapIndex++
        snapshot()
    },snapPeriodicity * 1000)

    geo.track(function(position) {
        posIndex++
        var latitude  = position.coords.latitude
        , longitude = position.coords.longitude

        marker.setLatLng([latitude, longitude]).update()
        map.setView([latitude,longitude], 15)

        vender_updateField('lat',latitude)
        vender_updateField('lng',longitude)

        pos = [latitude,longitude]
    })    
}
, transmitir_ask = function(){
    swal({
      title: "Título de la transmisión",
      text: "Elige un título para tu transmisión",
      type: "input",
      showCancelButton: true,
      closeOnConfirm: false,
      inputPlaceholder: "La montaña desde la ventana"
    },
    function(inputValue){
      //if (inputValue === false) return false;
      vender_updateField('title',inputValue, function(){
        swal.close()
        transmitir_start()  
      })
    })     
}
, vender_updateField = function (name,value,complete){
    if(!value) return
    $.server({ 
        url: '/update/' + code,
        data: name+'='+value, 
        success: function(){
            showTick()  
            if(typeof complete == "function"){
                complete.call(this)
            }
        }
    })
}
, vender_updateCheck = function (type,id,value){
    if(!value) return
    $.server({ 
        url: '/update-prop/' + code,
        data: {type:type,id:id,value:value}, 
        success: showTick
    })
}
, getVideoSize = function() {
    var videoWidth = video.videoWidth
    , videoHeight = video.videoHeight
    $('#canvas').attr("width",videoWidth)
    $('#canvas').attr("height",videoHeight)
    video.removeEventListener('playing', getVideoSize, false);
}
, snapshot = function(){
    $('canvas').show()
    context.drawImage(video, 0, 0)
    var data = canvas.toDataURL()
    $.ajax({
        type:'post',
        url: endpoint + '/upload/' + code,
        data:data,
        beforeSend: function (xhr) { 
            xhr.setRequestHeader('Authorization', 'Bearer ' + get_jwt()) 
        },          
        xhr: function() {
            var myXhr = $.ajaxSettings.xhr()
            if(myXhr.upload){
                myXhr.upload.addEventListener('progress',function(e){
                    if(e.lengthComputable){

                        var max = e.total
                        , current = e.loaded
                        , percentage = parseInt(current * 99/max)

                        console.log("Subiendo foto: " + percentage + "%")

                        if(percentage >= 99){
                            console.log("Subido!")
                            $('canvas').fadeOut(1000)
                        }
                    }
                }, false)
            }
            return myXhr;
        },
        cache:false,
        contentType: false,
        processData: false,  
        success:function(res){
            upload_in_progress = 0
        },
        error: function(jqxhr,textStatus,errorThrown){
            swal("Error","Hubo un error al subir el archivo " + errorThrown,"error")
        }
    }).then(function(){
        upload_in_progress = 0
    })    
}

// Get access to the camera!
if(navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    // Not adding `{ audio: true }` since we only want video now
    navigator.mediaDevices.getUserMedia({ video: true }).then(function(stream) {
        video.src = window.URL.createObjectURL(stream);
        video.play();
    });
}

video.addEventListener('playing', getVideoSize, false);
window.addEventListener('resize', getVideoSize, false);

// map

L.mapbox.accessToken = geo.mapbox.accessToken

//Load the map and set it to a given lat-lng
map = L.mapbox.map('map', 'mapbox.streets');
map.setView([-34.608724, -58.376867], 15);

//Display a default marker
marker = L.marker([-34.608724, -58.376867], {icon:geo.icon({displayName:"Yo",className:'me',colorId:2})}).addTo(map);

/*document.getElementById("snap").addEventListener("click", function() {
    snapshot()      
})*/

$(function(){

    $('.publish__form--newornot div').click(function(){
        var condition = $(this).first().data('ix')
        if(condition != undefined){
            vender_updateField('condition',(condition=='new'?2:1))
        }
    })
        
    transmitir_ask()
})