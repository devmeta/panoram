'use strict';

// Elements for taking the snapshot
var canvas = document.getElementById('canvas')
, context = canvas.getContext('2d')
, videoElement = document.querySelector('video')
, videoSelect = document.querySelector('select#videoSource')
, videoWidth
, videoHeight
, pause = 0 
, pos = []
, map
, marker
, markers = []
, snapInterval = 0
, snapIndex = 0
, snapPeriodicity = 15
, posIndex = 0
, upload_in_progress = 0 
, transmitir_clock = function(){
    if(snapInterval) clearInterval(snapInterval)
    snapInterval = setInterval(function(){ 
        snapIndex++
        snapshot()
    },snapPeriodicity * 1000)
}
, transmitir_start = function(){

    transmitir_clock()

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
}
, transmitir_ask = function(){

    $.server({
        url: location.pathname, 
        success: function(response){
            var pan = response.vehicle.data

            // condition
            if(pan.condition == 1){
                $('.toggleused').hide()
                $('.togglenew').css({display:'flex'})
                $('.togglenew div:first, .togglenew div:first div').removeClass('active')
                $('.togglenew div:eq(2), .togglenew div:eq(2) div').addClass('active')
            }

            $('.snapshot_count').text(pan.files.length)
            $('#title').val(pan.title)
            $('#extrainfo').val(pan.extrainfo)
            $('#extrainfo').val(pan.extrainfo)
            $('.toolbar-container').fadeIn()
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
, snapshot = function(force){
    if( !force && pause) return 
    $('canvas').show()
    context.drawImage(videoElement, 0, 0)
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

                        // console.log("Subiendo foto: " + percentage + "%")

                        if(percentage >= 99){
                            // console.log("Subido!")
                            var count = $('.snapshot_count').text();
                            $('canvas').fadeOut(1000)
                            $('#snap').removeClass('shake').addClass('shake')
                            $('.snapshot_count').text(parseInt(count)+1)
                            showTick()  
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
, gotDevices = function (deviceInfos) {
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
, getStream = function () {
  if (window.stream) {
    window.stream.getTracks().forEach(function(track) {
      track.stop();
    });
  }

  var constraints = {
    video: {
      optional: [{
        sourceId: videoSelect.value
      }]
    }
  };

  navigator.mediaDevices.getUserMedia(constraints).
      then(gotStream).catch(handleError);
}
, gotStream = function (stream) {
  window.stream = stream; // make stream available to console
  videoElement.srcObject = stream;
}
, handleError = function (error) {
  console.log('Error: ', error);
}
, show_toolbox = function(){
    pause = 1
    $('.toolbar-container').fadeIn('slow', function(){
        map.invalidateSize()
    })
}
, hide_toolbox = function(){
    $('.toolbar-container').fadeOut(1000, function(){
        pause = 0
    })    
}

videoElement.addEventListener('playing', getVideoSize, false);
window.addEventListener('resize', getVideoSize, false);

// map
L.mapbox.accessToken = geo.mapbox.accessToken
map = L.mapbox.map('map', 'mapbox.streets');
map.setView([0,0], 8);
marker = L.marker([0,0], {icon:geo.icon({displayName:"",className:'me',colorId:1})}).addTo(map);

document.getElementById("snap").addEventListener("click", function(e) {
    snapshot(1)
})

document.getElementById("pause").addEventListener("click", function() {
    if($(this).hasClass('paused')){
        $(this).removeClass('paused').attr("title","Transmitiendo EN VIVO")
        pause = 0
    } else {
        $(this).addClass('paused').attr("title","Transmisión EN PAUSA")
        pause = 1
    }
})

navigator.mediaDevices.enumerateDevices()
    .then(gotDevices).then(getStream).catch(handleError);

videoSelect.onchange = getStream;

$(function(){

    $('#interval').change(function(){
        var int = parseInt($(this).val())
        snapPeriodicity = int
        transmitir_updateField('interval',snapPeriodicity)
        transmitir_clock()
    })

    $('.toogle-toolbox').click(function(){
        if($('.toolbar-container').is(':visible')){
            hide_toolbox()
        } else {
            show_toolbox()
        }
    })

    $('.toolbar-container').click(function(e){
        if($(e.target).hasClass('toolbar-container')||$(e.target).hasClass('toolbar')){
            hide_toolbox()
        }
    })

    $('.start').click(function(){
        transmitir_updateField('title',$('#title').val())
        transmitir_updateField('extrainfo',$('#extrainfo').val())
        transmitir_updateField('agent',navigator.userAgent)
        transmitir_updateField('interval',$('#interval').val())
        transmitir_updateField('camera',$('#videoSource').val())
        hide_toolbox()
        transmitir_start()
    })

    $('.publish__form--newornot div').click(function(){
        var condition = $(this).first().data('ix')
        if(condition != undefined){
            transmitir_updateField('condition',(condition=='new'?2:1))
        }
    })
        
    transmitir_ask()
})