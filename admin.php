<?php
    session_start();
    $messageEntry = false;
    $messageAdmin = false;
    //Switch case for handling errors from entry submission or admin login
    if(isset($_SESSION['error'])) {
        switch ($_SESSION['error']) {
            case 6:
                $messageEntry = "Title can not be empty!";
                break;
            case 7:
                $messageEntry = "Entry can not be empty!";
                break;
            case 8:
                $messageEntry = "Title and Entry must not be empty!";
                break;
            case 9:
                $messageAdmin = "Please enter a username!";
                break;
            case 10:
                $messageAdmin = "Please enter a password!";
                break;
            case 11:
                $messageAdmin = "Username and password must be filled!";
                break;
            default:
                $messageEntry = false;
                $messageAdmin = false;
        }
        unset($_SESSION['error']);
    }

    //if the user is logged in, continue
    if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1):
        include_once 'inc/functions.inc.php';
        include_once 'inc/db.inc.php';

        $db = new PDO(DB_INFO,DB_USER,DB_PASS);
        if(isset($_GET['page'])) {
            $page = htmlentities(strip_tags($_GET['page']));
        } else {
            $page='blog';
        }
        //Delete entry and delete image from folder
        if(isset($_POST['action']) && $_POST['action'] == 'delete'){
            if($_POST['submit'] == 'Yes'){
                $url = htmlentities(strip_tags($_POST['url']));
                $path = getImagePath($db, $url);
                if(deleteEntry($db,$url)){
                    $absolute = $_SERVER['DOCUMENT_ROOT'].$path;
                    unlink($absolute);
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
            $long = $e['longitude'];
            $lat = $e['latitude'];
        } else {
            if($page == 'createuser'){
                $create = createUserForm();
            }
            $legend = "New entry submission";
        //if not editing, set variables to null
            $id = NULL;
            $title = NULL;
            $entry = NULL;
            $long = NULL;
            $lat = NULL;
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
        }
        elseif($page == 'createuser'):{
            echo $create;
        }
        else :
    ?>
        <form method="post" action="/inc/update.inc.php" enctype="multipart/form-data">
            <fieldset>
                <p class="error"><?php echo $messageEntry;?></p>
                <legend><?php echo $legend?></legend>
                <label>Title
                    <input <?php if($messageEntry == "Title and Entry must not be empty!" || $messageEntry == "Title can not be empty!")
                            {?>class="errorborder"<?php }?> type="text" name="title" maxlength="150"
                            value="<?php echo htmlentities($title)?>"/>
                </label>
                <label>Image
                    <input type="file" name="image"/>
                </label>
                <label>Entry
                <textarea <?php if($messageEntry == "Title and Entry must not be empty!" || $messageEntry == "Entry can not be empty!")
                          {?>class="errorborder"<?php }?> name="entry" cols="45" rows="10"><?php echo sanitizeData($entry)?></textarea>
                </label>
                <label>Longitude
                <input type="number" name="long" value="<?php echo sanitizeData($long)?>"/>
                </label>
                <label>Latitude
                <input type="number" name="lat" value="<?php echo sanitizeData($lat)?>"/>
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
    <?php
    //the user is not logged in
    else:
        ?>
    <!DOCTYPE html
        PUBLIC "-//W3C//DTD XHTML 1.0 STRICT//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
        <head>
            <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
            <link rel="stylesheet" href="/css/stylesheet.css" type="text/css"/>
            <title>Please Log In</title>
        </head>
        <body>
            <form method="post" action="/inc/update.inc.php"
                  enctype="multipart/form-data">
                <fieldset>
                    <legend>Please log in to continue</legend>
                    <p class="error"><?php echo $messageAdmin;?></p>
                    <label>Username
                        <input type="text" name="username" maxlength="75"/>
                    </label>
                    <label>Password
                        <input type="password" name="password" maxlength="150"/>
                    </label>
                    <input type="hidden" name="action" value="login"/>
                    <input type="submit" name="submit" value="Log In"/>
                </fieldset>
            </form>
        </body>
    </html>
<?php endif; ?>

