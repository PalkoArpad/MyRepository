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
            //instantiate class and set a save path
            $img = new ImageHandler("/images/");
            //process the file and store the returned path
            $img_path = $img->processUploadedImage($_FILES['image']);
        }
        catch(Exception $e){
            $e->getMessage();
        }
    } else {
        //avoid a notice if no image was uploaded
        $img_path = NULL;
    }
    //output contents of $_FILES
    //include credentials
    include_once 'db.inc.php';
    $db = new PDO(DB_INFO, DB_USER, DB_PASS);
    //edit an existing entry
    if(!empty($_POST['id'])){
        $sql = "UPDATE entries
                SET title=?, longitude=?, latitude=?, image=?, entry=?, url=?
                WHERE id=?
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
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
    } else {
        //save entry to database
        $sql = "INSERT INTO entries (page, title, longitude, latitude, image, entry, url) 
                VALUES (?,?,?,?,?,?,?)";
        $stmt = $db->prepare($sql);
        $stmt->execute(array($_POST['page'],
            $_POST['title'],
            $_POST['long'],
            $_POST['lat'],
            $img_path,
            $_POST['entry'],
            $url));
        $stmt->closeCursor();
        $id_obj = $db->query("SELECT LAST_INSERT_ID()");
        $id = $id_obj->fetch(PDO::FETCH_ASSOC);
        $id_obj->closeCursor();
        $url = $url.$id['LAST_INSERT_ID()'];
        $last = $id['LAST_INSERT_ID()'];
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

//if a comment is being posted, handle it here
else if($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['submit'] == 'Post comment')
    {
        //include and instantiate Comments class
        include_once 'comments.inc.php';
        $comments = new Comments();
        //save the comment
        $comments->saveComment($_POST);
        //if available, store the entry the use came from
        if(isset($_SERVER['HTTP_REFERER'])){
            $loc = $_SERVER['HTTP_REFERER'];
        } else {
            $loc = '../';
        }
            //send the user back to the entry
        header('Location:'.$loc);
        exit;
    }
    else if($_GET['action'] == 'comment_delete'){
        //include and instantiate the Comments class
        include_once 'comments.inc.php';
        $comments = new Comments();
        echo $comments->confirmDelete($_GET['id']);
        exit;
    }
    else if(($_SERVER['REQUEST_METHOD'] == 'POST')
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
    } //if a user is trying to log in, check
    else if($_SERVER['REQUEST_METHOD'] == 'POST'
            && $_POST['action'] =='login'
            && !empty($_POST['username'])
            && !empty($_POST['password'])){
        //include db credentials and connect to db
        include_once 'db.inc.php';
        $db = new PDO(DB_INFO,DB_USER,DB_PASS);
        $sql = "SELECT COUNT(*) AS num_users
                FROM admin
                WHERE username=?
                AND password=SHA1(?)";
        $stmt = $db->prepare($sql);
        $stmt->execute(array($_POST['username'],$_POST['password']));
        $response = $stmt->fetch();
        if($response['num_users'] > 0){
            $_SESSION['loggedin'] = 1;
        } else {
            $_SESSION['loggedin'] = NULL;
        }
        header('Location: /');
        exit;
    }
    //if an admin is being created, save it here
    else if( $_SERVER['REQUEST_METHOD'] == 'POST'
             && $_POST['action'] == 'createuser'
             && !empty($_POST['username'])
             && !empty($_POST['password'])) {
        //include db credentials and connect to db
        include_once 'db.inc.php';
        $db = new PDO(DB_INFO,DB_USER,DB_PASS);
        $sql = "INSERT INTO admin (username, password)
                VALUES (?, SHA1(?))";
        $stmt = $db->prepare($sql);
        $stmt->execute(array($_POST['username'], $_POST['password']));
        header('Location: /');
        exit;
        }
    else if($_GET['action'] == 'logout')
    {
        session_destroy();
        header('Location: ../');
        exit;
    }
    else {
        unset($_SESSION['c_name'],$_SESSION['c_email'],
              $_SESSION['c_comment'],$_SESSION['error']);
        header('Location:../');
        exit;
}
?>