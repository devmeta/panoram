'use strict';

// Elements for taking the snapshot
var canvas = document.getElementById('canvas')
, video = document.getElementById('video')
, context = canvas.getContext('2d')
, videoElement = document.querySelector('video')
, audioSelect = document.querySelector('select#audioSource')
, videoSelect = document.querySelector('select#videoSource')
, videoWidth
, videoHeight
, upload_in_progress = 1 
, pos = []
, map
, marker
, markers = []
, snapInterval = 0
, snapIndex = 0
, snapPeriodicity = 15
, posIndex = 0
, transmitir_clock = function(){
    if(snapInterval) clearInterval(snapInterval)
    snapInterval = setInterval(function(){ 
        snapIndex++
        snapshot()
    },snapPeriodicity * 1000)
}
, transmitir_start = function(){

    transmitir_clock()
    snapshot()

    $('.publish__container').fadeIn(2000)

    geo.track(function(position) {
        posIndex++
        var latitude  = position.coords.latitude
        , longitude = position.coords.longitude

        marker.setLatLng([latitude, longitude]).update()
        map.setView([latitude,longitude], 15)

        transmitir_updateField('lat',latitude)
        transmitir_updateField('lng',longitude)

        pos = [latitude,longitude]
    })    

    map.invalidateSize()    
}
, transmitir_ask = function(){

    $.server({
        url: location.pathname, 
        success: function(response){
            var pan = response.vehicle.data

            swal({
              title: "Título de la transmisión",
              text: "Elige un título para tu transmisión",
              inputValue: pan.title,
              type: "input",
              showCancelButton: true,
              closeOnConfirm: false,
              inputPlaceholder: "La montaña desde la ventana"
            },
            function(inputValue){
              //if (inputValue === false) return false;
              transmitir_updateField('title',inputValue, function(){
                swal.close()
                transmitir_start()  
              })
            })
        }
    })    
}
, transmitir_updateField = function (name,value,complete){
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
, transmitir_updateCheck = function (type,id,value){
    if(!value) return
    $.server({ 
        url: '/update-prop/' + code,
        data: {type:type,id:id,value:value}, 
        success: showTick
    })
}
, getVideoSize = function() {
    var videoWidth = videoElement.videoWidth
    , videoHeight = videoElement.videoHeight
    $('#canvas').attr("width",videoWidth)
    $('#canvas').attr("height",videoHeight)
    videoElement.removeEventListener('playing', getVideoSize, false);
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
        videoElement.src = window.URL.createObjectURL(stream);
        videoElement.play();
    });
}

videoElement.addEventListener('playing', getVideoSize, false);
window.addEventListener('resize', getVideoSize, false);

// map

L.mapbox.accessToken = geo.mapbox.accessToken

//Load the map and set it to a given lat-lng
map = L.mapbox.map('map', 'mapbox.streets');
map.setView([0,0], 8);

//Display a default marker
marker = L.marker([0,0], {icon:geo.icon({displayName:"",className:'me',colorId:1})}).addTo(map);

/*document.getElementById("snap").addEventListener("click", function() {
    snapshot()      
})*/


navigator.mediaDevices.enumerateDevices()
    .then(gotDevices).then(getStream).catch(handleError);

videoSelect.onchange = getStream;

function gotDevices(deviceInfos) {
  for (var i = 0; i !== deviceInfos.length; ++i) {
    var deviceInfo = deviceInfos[i];
    var option = document.createElement('option');
    option.value = deviceInfo.deviceId;
    if (deviceInfo.kind === 'videoinput') {
      option.text = deviceInfo.label || 'camera ' +
        (videoSelect.length + 1);
      videoSelect.appendChild(option);
    } else {
      console.log('Found ome other kind of source/device: ', deviceInfo);
    }
  }
}

function getStream() {
  if (window.stream) {
    window.stream.getTracks().forEach(function(track) {
      track.stop();
    });
  }

  var constraints = {
    audio: {
      optional: [{
        sourceId: audioSelect.value
      }]
    },
    video: {
      optional: [{
        sourceId: videoSelect.value
      }]
    }
  };

  navigator.mediaDevices.getUserMedia(constraints).
      then(gotStream).catch(handleError);
}

function gotStream(stream) {
  window.stream = stream; // make stream available to console
  videoElement.srcObject = stream;
}

function handleError(error) {
  console.log('Error: ', error);
}

$(function(){

    $('#periodicity').change(function(){
        if(name=="periodicity"){
            var int = parseInt($(this).val())
            snapPeriodicity = int
            transmitir_clock()
        }         
    })

    $('.toogle-toolbox').click(function(){
        if($('.toolbar').is(':visible')){
            $('.toolbar').fadeOut()
        } else {
            $('.toolbar').fadeIn()
        }
    })

    $('.publish__form--newornot div').click(function(){
        var condition = $(this).first().data('ix')
        if(condition != undefined){
            transmitir_updateField('condition',(condition=='new'?2:1))
        }
    })
        
    transmitir_ask()
})