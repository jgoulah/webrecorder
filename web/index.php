
<!DOCTYPE html>
<!--
Copyright 2012 Eric Bidelman

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

Author: Eric Bidelman (ebidel@)
-->
<html>
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="chrome=1" />
<!--link href="../common.css" rel="stylesheet" type="text/css" media="all"-->
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
  <button id="camera-me">1. Turn on camera</button>
  <h4><code>getUserMedia()</code> feed</h4>
  <video autoplay></video>
</div>
<div id="video-preview">
  <button id="record-me" disabled>2. Record<!--⚫--></button>
  <button id="stop-me" disabled>◼</button>
  <!--<button id="play-me" disabled>►</button>-->
  <span id="elasped-time"></span>
  <h4>.webm recording (no audio)</h4>
</div>
</section>

<!--<script src="libwebp-0.1.3.min.js"></script>-->
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

var ORIGINAL_DOC_TITLE = document.title;
var video = $('video');
var canvas = document.createElement('canvas'); // offscreen canvas.
var rafId = null;
var startTime = null;
var endTime = null;
var frames = [];
var audio_context;
var recorder;
var tagTime = Date.now();

function $(selector) {
  return document.querySelector(selector) || null;
}

function toggleActivateRecordButton() {
  var b = $('#record-me');
  b.textContent = b.disabled ? 'Record' : 'Recording...';
  b.classList.toggle('recording');
  b.disabled = !b.disabled;
}

function turnOnCamera(e) {
  e.target.disabled = true;
  $('#record-me').disabled = false;

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

  navigator.getUserMedia({video: true, audio: true}, function(stream) {
    console.log('getUserMedia called');
    video.src = window.URL.createObjectURL(stream);

    var input = audio_context.createMediaStreamSource(stream);
    //input.connect(audio_context.destination);
    // connect to zero gained node that connects to destination
    var zeroGain = audio_context.createGain();
    zeroGain.gain.value = 0;
    input.connect(zeroGain);
    zeroGain.connect(audio_context.destination);

console.log('sample rate is '+ audio_context.sampleRate);
    recorder = new Recorder(input);

    finishVideoSetup_();
  }, function(e) {
    alert('Fine, you get a movie instead of your beautiful face ;)');

    video.src = 'Chrome_ImF.mp4';
    finishVideoSetup_();
  });
};

function record() {
  var elapsedTime = $('#elasped-time');
  var ctx = canvas.getContext('2d');
  var CANVAS_HEIGHT = canvas.height;
  var CANVAS_WIDTH = canvas.width;

  frames = []; // clear existing frames;
  startTime = Date.now();

  toggleActivateRecordButton();
  $('#stop-me').disabled = false;

  recorder.record(); 

  function drawVideoFrame_(time) {
    rafId = requestAnimationFrame(drawVideoFrame_);

    ctx.drawImage(video, 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

    document.title = 'Recording...' + Math.round((Date.now() - startTime) / 1000) + 's';

    // Read back canvas as webp.
    //console.time('canvas.dataURL() took');
    var url = canvas.toDataURL('image/webp', 1); // image/jpeg is way faster :(
    //console.timeEnd('canvas.dataURL() took');
    frames.push(url);
 
    // UInt8ClampedArray (for Worker).
    //frames.push(ctx.getImageData(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT).data);

    // ImageData
    //frames.push(ctx.getImageData(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT));
  };

  rafId = requestAnimationFrame(drawVideoFrame_);
};

function stop() {
  cancelAnimationFrame(rafId);
  recorder.stop();
  endTime = Date.now();
  $('#stop-me').disabled = true;
  document.title = ORIGINAL_DOC_TITLE;

  toggleActivateRecordButton();

  console.log('frames captured: ' + frames.length + ' => ' +
              ((endTime - startTime) / 1000) + 's video');

  embedVideoPreview();
};

function embedVideoPreview(opt_url) {
  var url = opt_url || null;
  var video = $('#video-preview video') || null;
  var downloadLink = $('#video-preview a[download]') || null;

  if (!video) {
    video = document.createElement('video');
    video.autoplay = true;
    video.controls = true;
    video.loop = true;
    //video.style.position = 'absolute';
    //video.style.top = '70px';
    //video.style.left = '10px';
    video.style.width = canvas.width + 'px';
    video.style.height = canvas.height + 'px';
    $('#video-preview').appendChild(video);
    
    downloadLink = document.createElement('a');
    downloadLink.download = 'capture.webm';
    downloadLink.textContent = '[ download video ]';
    downloadLink.title = 'Download your .webm video';
    var p = document.createElement('p');
    p.appendChild(downloadLink);

    recorder.exportWAV(function(blob) {
console.log('in exportWAV');
      var url = URL.createObjectURL(blob);
      var li = document.createElement('li');
      var au = document.createElement('audio');
      var hf = document.createElement('a');
      
      au.controls = true;
      au.src = url;
      hf.href = url;
      hf.download = new Date().toISOString() + '.wav';
      hf.innerHTML = hf.download;
      li.appendChild(au);
      li.appendChild(hf);
      var ul = document.createElement('ul');
      ul.appendChild(li);
      $('#video-preview').appendChild(ul);

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

    //saveLink = document.createElement('a');
    ////saveLink.download = 'capture.webm';
    //saveLink.textContent = '[ save video ]';
    //saveLink.title = 'Save your .webm video';
    //var p1 = document.createElement('p');
    //p1.appendChild(saveLink);

    $('#video-preview').appendChild(p);
    //$('#video-preview').appendChild(p1);

  } else {
    window.URL.revokeObjectURL(video.src);
  }

  // https://github.com/antimatter15/whammy
  // var encoder = new Whammy.Video(1000/60);
  // frames.forEach(function(dataURL, i) {
  //   encoder.add(dataURL);
  // });
  // var webmBlob = encoder.compile();

  //if (!url) {
    var webmBlob = Whammy.fromImageArray(frames, 1000 / 60);
    url = window.URL.createObjectURL(webmBlob);
  //}

  video.src = url;
  downloadLink.href = url;
  //saveLink.href = 'save.php?video='+ url + ' sound='+ soundUrl; 

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
  $('#camera-me').addEventListener('click', turnOnCamera);
  $('#record-me').addEventListener('click', record);
  $('#stop-me').addEventListener('click', stop);
}

initEvents();

exports.$ = $;

})(window);

</script>
</body>
</html>
