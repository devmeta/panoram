// Elements for taking the snapshot
var canvas = document.getElementById('canvas')
, context = canvas.getContext('2d')
, videoWidth
, videoHeight
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
    //var data = context.getImageData( 0, 0, 640, 480)
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

// Grab elements, create settings, etc.
var video = document.getElementById('video');

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

// Trigger photo take
document.getElementById("snap").addEventListener("click", function() {
    snapshot()      
})