<?php
session_start();
include_once 'functions.inc.php';
include_once 'images.inc.php';

if(($_SERVER['REQUEST_METHOD']=="POST")
    && $_POST['submit']=="Save Entry"
    && !empty($_POST['page'])
    && !empty($_POST['title'])
    && !empty($_POST['entry']))
{
    $url = makeURL($_POST['title']);
    if(isset($_FILES['image']['tmp_name'])){
        try{
            //instantiate image class and set a save path
            $img = new ImageHandler("/images/");
            //process the file and store the returned path
            $img_path = $img->processUploadedImage($_FILES['image']);
        } catch(Exception $e) {
            $e->getMessage();
        }
    } else {
        //avoid a notice if no image was uploaded
        $img_path = NULL;
    }

    //Include credentials
    include_once 'db.inc.php';
    $db = new PDO(DB_INFO, DB_USER, DB_PASS);
    //If an entry already exists
    if(!empty($_POST['id'])) {
  /* Verify if an image was uploaded in that entry
     If yes, delete the old image from folder
     If not, go to the next step -> updating
   */
        $path = getImagePath($db, $url);
        if($path != NULL) {
            $absolute = $_SERVER['DOCUMENT_ROOT'] . $path;
            unlink($absolute);
        }
        //Update an existing entry
        $sql = "UPDATE entries
                SET title=?, longitude=?, latitude=?, image=?, entry=?, url=?
                WHERE id=?
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(
            array(
                $_POST['title'],
                $_POST['long'],
                $_POST['lat'],
                $img_path,
                $_POST['entry'],
                $url,
                $_POST['id']
                )
        );
        $stmt->closeCursor();
        $page = htmlentities(strip_tags($_POST['page']));
        header("Location: /$page");
    } else {
        //Save a new entry in database
        $sql = "INSERT INTO entries (page, title, longitude, latitude, image, entry, url) 
                VALUES (?,?,?,?,?,?,?)";
        $stmt = $db->prepare($sql);
        $stmt->execute(
                array(
                    $_POST['page'],
                    $_POST['title'],
                    $_POST['long'],
                    $_POST['lat'],
                    $img_path,
                    $_POST['entry'],
                    $url
                )
        );
        $stmt->closeCursor();
        //Check if an entry with the same url as the new one exists already
        $sql = "SELECT COUNT(url) AS nr FROM entries
                WHERE url='$url'
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $value = $stmt->fetch();
        //If it exists, replace the new url, with url concatenated with id
        if($value['nr'] > 1){
        $id_obj = $db->query("SELECT LAST_INSERT_ID()");
        $id = $id_obj->fetch(PDO::FETCH_ASSOC);
        $id_obj->closeCursor();
        //Retrieve the last inserted id, and concatenate it with url
        $url = $url.$id['LAST_INSERT_ID()'];
        $last = $id['LAST_INSERT_ID()'];
        //Update the new entry's url in database
        $sql = "UPDATE entries
                SET url='$url'
                WHERE id='$last'
                ";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
        }
        //sanitize information
        $page = htmlentities(strip_tags($_POST['page']));
        //send the user to the new entry
        header('Location: /'.$page.'/'.$url);
        exit;
    }
    //Next, we check if a required entry field was not filled out
} else if($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['submit'] == "Save Entry"
        && ((empty($_POST['page'])) || empty($_POST['title']) || empty($_POST['entry']))) {
        //If both title and entry are empty
            if(empty($_POST['title']) && empty($_POST['entry'])) {
                $_SESSION['error'] = 8;
        //If entry is empty
            } else if(empty($_POST['entry'])) {
                $_SESSION['error'] = 7;
        //If title is empty
            } else if(empty($_POST['title'])) {
                $_SESSION['error'] = 6;
            }
//        $page = htmlentities(strip_tags($_POST['page']));
//        header("Location:/admin/$page/'#comment-form'");

} else if($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['submit'] == 'Post comment') {      //if a comment is being posted, handle it here
        //include and instantiate Comments class
        include_once 'comments.inc.php';
        $comments = new Comments();
        //save the comment
        $comments->saveComment($_POST);
        //if available, store the entry the user came from
        if(isset($_SERVER['HTTP_REFERER'])){
            $loc = $_SERVER['HTTP_REFERER'];
        } else {
            $loc = '../';
        }
            //send the user back to the entry
        header('Location:'.$loc);
        exit;

} else if($_GET['action'] == 'comment_delete') {
        //include and instantiate the Comments class
        include_once 'comments.inc.php';
        $comments = new Comments();
        echo $comments->confirmDelete($_GET['id']);
        exit;

} else if(($_SERVER['REQUEST_METHOD'] == 'POST')
        && $_POST['action'] == 'comment_delete') {
        //if set, store the entry from which we came
        $loc = isset($_POST['url']) ? $_POST['url'] : '../';
        //if Yes was clicked, continue with deletion
        if($_POST['confirm'] == "Yes") {
            //include and instantiate Comment class
            include_once 'comments.inc.php';
            $comments = new Comments();
            //delete the comment and return to entry
            if($comments->deleteComment($_POST['id'])){
                header('Location:'.$loc);
            } else {  //if deleting fails
                exit('Could not delete the comment.');
            }
        } else {   //if the user clicked no
            header('Location:'.$loc);
            exit;
        }

} else if($_SERVER['REQUEST_METHOD'] == 'POST'
            && $_POST['action'] =='login'
            && !empty($_POST['username'])
            && !empty($_POST['password'])) {          //if a user is trying to log in, check
        //include db credentials and connect to db
        include_once 'db.inc.php';
        $db = new PDO(DB_INFO,DB_USER,DB_PASS);
        //check if entered username and password is already in database
        $sql = "SELECT COUNT(*) AS num_users
                FROM admin
                WHERE username=?
                AND password=SHA1(?)";
        $stmt = $db->prepare($sql);
        $stmt->execute(array($_POST['username'],$_POST['password']));
        $response = $stmt->fetch();
        //if the username and password matches, log in
        if($response['num_users'] > 0){
            $_SESSION['loggedin'] = 1;
        } else {
            $_SESSION['loggedin'] = NULL;
        }

        header('Location: /');
        exit;

} else if($_SERVER['REQUEST_METHOD'] == 'POST'                      //Verify if both completion fields are completed
        && $_POST['action'] == 'login'
        && (empty($_POST['username']) || empty($_POST['password']))) {
    //if both username and password field is empty
         if(empty($_POST['username']) && empty($_POST['password'])) {
            $_SESSION['error'] = 11;
    //if username is empty
         } else if(empty($_POST['username'])){
            $_SESSION['error'] = 9;
    //if password is empty
         } else if(empty($_POST['password'])){
            $_SESSION['error'] = 10;
         }

         header("Location:/admin/$page");

} else if($_SERVER['REQUEST_METHOD'] == 'POST'                       //If an admin is being created, save it here
             && $_POST['action'] == 'createuser'
             && !empty($_POST['username'])
             && !empty($_POST['password'])) {
        //include db credentials and connect to db
        include_once 'db.inc.php';
        $db = new PDO(DB_INFO, DB_USER, DB_PASS);
        //insert new admin into database
        $sql = "INSERT INTO admin (username, password)
                VALUES (?, SHA1(?))";
        $stmt = $db->prepare($sql);
        $stmt->execute(array($_POST['username'], $_POST['password']));

        header('Location: /');
        exit;
} else if($_GET['action'] == 'logout') {                          //if admin logs out, destroy session
        session_destroy();
        header('Location: ../');
        exit;
    } else {
        unset($_SESSION['c_name'],$_SESSION['c_email'],
              $_SESSION['c_comment'],$_SESSION['error']);
        header('Location:../');
        exit;
    }
?>