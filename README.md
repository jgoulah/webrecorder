# webrecorder 

A way to get A/V recorded from the browser.

## Usage 

Point your doc root to the web directory. chmod 777 web/uploads
Load up the page, record something, once you press stop, it will post the A/V files to save.php which moves them into uploads.

Then use the scripts/convert to merge the files, pass either the audio or video file as the arg and it will merge the other in.

