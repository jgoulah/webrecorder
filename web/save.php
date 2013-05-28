<?

error_log("reqeust\n");
error_log(print_r($_REQUEST, true));
error_log("files\n");
error_log(print_r($_FILES, true));

foreach(array('video', 'audio') as $type) {

    if (isset($_FILES["${type}data"])) {
        $name = $_POST["${type}file"];
        $updir = getcwd() . "/uploads/$name";
        error_log("uploading to $updir");
        if (!move_uploaded_file($_FILES["${type}data"]["tmp_name"], $updir)) {
            error_log("problem moving uploaded file");
        }
    } 

}

//if (isset($_FILES['videodata'])) {
    //$name = $_POST['videofile'];
    //$updir = getcwd() . "/uploads/$name";
    //move_uploaded_file($_FILES["videodata"]["tmp_name"], "uploads/$name");
    //error_log("uploading to $updir");
    //if (!move_uploaded_file($_FILES["audiodata"]["tmp_name"], $updir)) {
        //error_log("problem moving uploaded file");
    //}
//} 

//if (isset($_FILES['audiodata'])) {
    //$name = $_POST['audiofile'];
    //$updir = getcwd() . "/uploads/$name";
    //error_log("uploading to $updir");
    //if (!move_uploaded_file($_FILES["audiodata"]["tmp_name"], $updir)) {
        //error_log("problem moving uploaded file");
    //}
//} 

