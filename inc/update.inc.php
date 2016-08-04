<?php
include_once 'functions.inc.php';

if(($_SERVER['REQUEST_METHOD']=="POST")
    && $_POST['submit']=="Save Entry"
    && !empty($_POST['page'])
    && !empty($_POST['title'])
    && !empty($_POST['entry']))
{
    $url = makeURL($_POST['title']);
    //include credentials
    include_once 'db.inc.php';
    $db = new PDO(DB_INFO, DB_USER, DB_PASS);
    //edit an existing entry
    if(!empty($_POST['id'])){
        $sql = "UPDATE entries
                SET title=?, entry=?, url=?
                WHERE id=?
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            $_POST['title'],
            $_POST['entry'],
            $url,
            $_POST['id']
            )
        );
        $stmt->closeCursor();
    } else {
        //save entry to database
        $sql = "INSERT INTO entries (page, title, entry, url) VALUES (?,?,?,?)";
        $stmt = $db->prepare($sql);
        $stmt->execute(array($_POST['page'], $_POST['title'], $_POST['entry'], $url));
        $stmt->closeCursor();
        }
    //sanitize information
        $page = htmlentities(strip_tags($_POST['page']));
    /*//get id of the last inserted entry
        $id_obj = $db->query("SELECT LAST_INSERT_ID()");
        $id = $id_obj->fetch();
        $id_obj->closeCursor();*/
    //send the user to the new entry
        header('Location: /'.$page.'/'.$url);
        exit;
}
else {
    header('Location:../');
    exit;
}
?>