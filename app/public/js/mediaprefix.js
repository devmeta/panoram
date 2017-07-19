var getUserMediaPrefixed,
    videoStream,
    videoTag;

setGumPrefix();

if (!getUserMediaPrefixed) {
    logMessage('Sorry, your browser doesn\'t support getUserMedia interface');
}
else {
    runCamera();
}

function dealWithStream(stream) {
    videoStream = stream;

    if (!videoTag) {
        videoTag = document.createElement('video');
        videoTag.addEventListener('resize', videoEventListener);
    }

    videoTag.src = window.URL.createObjectURL(stream);
    videoTag.play();
}

function handleError(e) {
    if (e.name == 'PermissionDeniedError') {
        logMessage('It looks like you\'ve denied access to the camera.');
    }
    else if (e.name == 'SourceUnavailableError') {
        logMessage('It looks like your camera is <b>used</b> by another application.');
    }
    else {
        logMessage('The camera is unavailable. The error message is: ' +e.message);
    }
}

function logMessage(msg) {
    var p = document.createElement('p');

    p.innerHTML = msg;

    document.getElementById('output').appendChild(p);
}

function runCamera() {
    var constraints = {
        audio: false,
        video: {
            optional: [
                {minWidth: 320},
                {minWidth: 640},
                {minWidth: 800},
                {minWidth: 900},
                {minWidth: 1024},
                {minWidth: 1280},
                {minWidth: 1920},
                {minWidth: 2560}
            ]
        }
    };

    navigator[getUserMediaPrefixed](constraints, dealWithStream, handleError);
}

function setGumPrefix() {
    if (navigator.getUserMedia) {
        getUserMediaPrefixed = 'getUserMedia';
    }
    else if (navigator.webkitGetUserMedia) {
        getUserMediaPrefixed = 'webkitGetUserMedia';
    }
    else if (navigator.mozGetUserMedia) {
        getUserMediaPrefixed = 'mozGetUserMedia';
    }
    else if (navigator.msGetUserMedia) {
        getUserMediaPrefixed = 'msGetUserMedia';
    }
}

function videoEventListener() {
    if (videoTag.videoWidth) {
        logMessage('Best captured video quality in your browser is ' +videoTag.videoWidth+ '×' +videoTag.videoHeight);

        // stop stream
        videoStream.stop();
        videoTag.src = '';
    }
}