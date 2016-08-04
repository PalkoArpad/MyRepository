<?php
    include_once 'inc/functions.inc.php';
    include_once 'inc/db.inc.php';

    $db = new PDO(DB_INFO,DB_USER,DB_PASS);
    if(isset($_GET['page'])) {
        $page = htmlentities(strip_tags($_GET['page']));
    } else {
        $page='blog';
    }
    if(isset($_POST['action']) && $_POST['action'] == 'delete'){
        if($_POST['submit'] == 'Yes'){
            $url = htmlentities(strip_tags($_POST['url']));
            if(deleteEntry($db,$url)){
                header("Location: /");
                exit;
            } else {
                exit("Error deleting the entry!");
            }
        } else {
            header("Location: /blog/$url");
            exit;
        }
    }
    if(isset($_GET['url'])){
        $url = htmlentities(strip_tags($_GET['url']));
        if($page == 'delete') {
            $confirm = confirmDelete($db,$url);
        }
        $legend = "Edit this entry";
        $e = retrieveEntries($db, $page, $url);
        //save each entry as individual variables
        $id = $e['id'];
        $title = $e['title'];
        $entry = $e['entry'];
    } else {
        $legend = "New entry submission";
        //if not editing, set variables to null
        $id = NULL;
        $title = NULL;
        $entry = NULL;
    }
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
        <link rel="stylesheet" href="/css/stylesheet.css" type="text/css"/>
        <title> Simple Blog </title>
    </head>

    <body>
        <h1> Simple Blog Application</h1>
    <?php
        if($page == 'delete'):
        {
        echo $confirm;
        } else :
    ?>
        <form method="post" action="/inc/update.inc.php">
            <fieldset>
                <legend><?php echo $legend?></legend>
                <label>Title
                    <input type="text" name="title" maxlength="150"
                            value="<?php echo htmlentities($title)?>"/>
                </label>
                <label>Entry
                <textarea name="entry" cols="45" rows="10">
                    <?php echo sanitizeData($entry)?>
                </textarea>
                </label>
                <input type="hidden" name="id" value="<?php echo $id?>"/>
                <input type="hidden" name="page" value="<?php echo $page?>"/>
                <input type="submit" name="submit" value="Save Entry"/>
                <input type="submit" name="submit" value="Cancel"/>
            </fieldset>
        </form>
    <?php endif; ?>
    </body>
</html>

