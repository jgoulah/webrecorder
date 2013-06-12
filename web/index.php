<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="chrome=1" />
<title>Record a Video Session</title>
<style>
a[download] {
  text-transform: uppercase;
  font-size: 11px;
  font-weight: bold;
}
h4 {
  padding: 15px;
  background: black;
  color: white;
  margin: 10px 0 10px 0;
  border-radius: 100px 0 100px 0;
  letter-spacing: 1px;
  font-weight: 300;
}
section > div {
  text-align: center;
  display: inline-block;
  margin: 0 15px;
  min-width: 400px;
}
#video-preview {
  height: 300px;
}
button.recording {
  color: darkred;
  border-color: red;
}
section {
  margin-top: 2em;
}
h2 {
  text-align: center;
}
</style>
</head>
<body>


<section>
<div style="float:left;">
  <button id="record-me">Record<!--⚫--></button>
  <button id="stop-me" disabled>◼</button>
  <h4>video feed</h4>
  <video autoplay></video>
</div>
</section>

<script src="js/whammy.min.js"></script>
<script src="js/recorder.js"></script>
<script src="js/jquery.min.js"></script>
<script>
(function(exports) {

exports.URL = exports.URL || exports.webkitURL;

exports.requestAnimationFrame = exports.requestAnimationFrame ||
    exports.webkitRequestAnimationFrame || exports.mozRequestAnimationFrame ||
    exports.msRequestAnimationFrame || exports.oRequestAnimationFrame;

exports.cancelAnimationFrame = exports.cancelAnimationFrame ||
    exports.webkitCancelAnimationFrame || exports.mozCancelAnimationFrame ||
    exports.msCancelAnimationFrame || exports.oCancelAnimationFrame;

navigator.getUserMedia = navigator.getUserMedia ||
    navigator.webkitGetUserMedia || navigator.mozGetUserMedia ||
    navigator.msGetUserMedia;

window.AudioContext = window.AudioContext || window.webkitAudioContext;
      navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia;
      window.URL = window.URL || window.webkitURL;

var CANVAS_WIDTH = 320;
var CANVAS_HEIGHT = 240;
var ORIGINAL_DOC_TITLE = document.title;
var video = $('video')[0];
video.width = CANVAS_WIDTH;
video.height = CANVAS_HEIGHT;
var canvas = document.createElement('canvas'); // offscreen canvas.
canvas.width = CANVAS_WIDTH;
canvas.height = CANVAS_HEIGHT;
var rafId = null;
var startTime = null;
var endTime = null;
var audio_context;
var recorder;
var theStream;
var tagTime = Date.now();
var whammy = new Whammy.Video(10, 0.6);


function createNoiseGate( connectTo ) {
    var inputNode = audio_context.createGain();
    var rectifier = audio_context.createWaveShaper();
    var ngFollower = audio_context.createBiquadFilter();
    ngFollower.type = ngFollower.LOWPASS;
    ngFollower.frequency.value = 10.0;

    inputNode.connect(ngFollower);
    return inputNode;
}

function generateNoiseFloorCurve( floor ) {

    var curve = new Float32Array(65536);
    var mappedFloor = floor * 32768;

    for (var i=0; i<32768; i++) {
        var value = (i<mappedFloor) ? 0 : 1;

        curve[32768-i] = -value;
        curve[32768+i] = value;
    }
    curve[0] = curve[1]; // fixing up the end.

    return curve;
}

function toggleActivateRecordButton() {
  var b = $('#record-me')[0];
  b.textContent = b.disabled ? 'Record' : 'Recording...';
  b.classList.toggle('recording');
  b.disabled = !b.disabled;
}

function turnOnCamera() {

  video.controls = false;

    // audio setup
    audio_context = new AudioContext;
    try {
      console.log('Audio context set up.');
      console.log('navigator.getUserMedia ' + (navigator.getUserMedia ? 'available.' : 'not present!'));
    } catch (e) {
      console.log('No web audio support in this browser!');
    }

  var finishVideoSetup_ = function() {
    // Note: video.onloadedmetadata doesn't fire in Chrome when using getUserMedia so
    // we have to use setTimeout. See crbug.com/110938.
    setTimeout(function() {
      video.width = 320;//video.clientWidth;
      video.height = 240;// video.clientHeight;
      // Canvas is 1/2 for performance. Otherwise, getImageData() readback is
      // awful 100ms+ as 640x480.
      canvas.width = video.width;
      canvas.height = video.height;
    }, 1000);
  };

  navigator.webkitGetUserMedia({"video": {
    "mandatory": {
     "minWidth": "320",
     "minHeight": "240",
     "minFrameRate": "10",
     "maxWidth": "320",
     "maxHeight": "240",
     "maxFrameRate": "10"
    }
  }, audio: true}, function(stream) {
    theStream = stream;
    video.src = window.URL.createObjectURL(stream);

    var input = audio_context.createMediaStreamSource(stream);

    //console.log('sample rate is '+ audio_context.sampleRate);
    modulatorInput = audio_context.createGainNode();

    modulatorGain = audio_context.createGainNode();
    modulatorGain.gain.value = 4.0;
    modulatorGain.connect( modulatorInput );

    input.connect(modulatorGain);
    recorder = new Recorder(input);

    finishVideoSetup_();
  }, function(e) {
    alert('something went wrong');
  });
};

function record() {
  var elapsedTime = $('#elasped-time')[0];
  var ctx = canvas.getContext('2d');
  var CANVAS_HEIGHT = canvas.height;
  var CANVAS_WIDTH = canvas.width;

  startTime = Date.now();

  toggleActivateRecordButton();
  $('#stop-me')[0].disabled = false;

  recorder.record(); 
  var lastFrameTime;

  function drawVideoFrame_(time) {
    rafId = requestAnimationFrame(drawVideoFrame_);

    if (typeof lastFrameTime === undefined) { lastFrameTime = time; }

    if (time - lastFrameTime < 90) { return; } // ~10 fps

    ctx.drawImage(video, 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

    // document.title = 'Recording...' + Math.round((Date.now() - startTime) / 1000) + 's';

    //console.log("adding frame to whammy");
    try {
      // debugger;
      // whammy.add(canvas, time - lastFrameTime);
      whammy.add(canvas);
    } catch (e) {
      console.log("error: ", e);
    }

    //console.log("fps: ", 1000 / (time - lastFrameTime));
    lastFrameTime = time;
  };

  rafId = requestAnimationFrame(drawVideoFrame_);
};

function stop() {
  theStream.stop();
  recorder.stop();
  endTime = Date.now();
  $('#stop-me')[0].disabled = true;
  document.title = ORIGINAL_DOC_TITLE;

  toggleActivateRecordButton();

  console.log('frames captured: ' + whammy.frames.length + ' => ' +
              ((endTime - startTime) / 1000) + 's video');

  embedVideoPreview();
};

function embedVideoPreview(opt_url) {

  recorder.exportWAV(function(blob) {
    var fd = new FormData();
    fd.append('audiofile', tagTime + '.wav');
    fd.append('audiodata', blob);
    jQuery.ajax({
      type: 'POST',
      url: '/save.php',
      data: fd,
      processData: false,
      contentType: false
    }).done(function(data) {
      console.log(data);
    });
  });

  var webmBlob = whammy.compile();

  var fd = new FormData();
  fd.append('videofile', tagTime + '.webm');
  fd.append('videodata', webmBlob);
  jQuery.ajax({
    type: 'POST',
    url: '/save.php',
    data: fd,
    processData: false,
    contentType: false
  }).done(function(data) {
       console.log(data);
  });

}

function initEvents() {
  $('#record-me')[0].addEventListener('click', record);
  $('#stop-me')[0].addEventListener('click', stop);
}

setTimeout(function () {
  initEvents();
}, 500)
initEvents();
turnOnCamera();

})(window);

</script>
</body>
</html>
