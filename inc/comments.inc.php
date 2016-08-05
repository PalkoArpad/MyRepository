<?php
    include_once 'db.inc.php';

    class Comments
    {
        public $db;
        public $comments; //array for containing the entries
        //when instantiated, oped db connection
        public function __construct()
        {
            $this->db = new PDO(DB_INFO,DB_USER,DB_PASS);
        }

        public function showCommentForm($blod_id)
        {
            return <<<FORM
        <form action="/inc/update.inc.php" method="post" id="comment-form">
        <fieldset>
            <legend>Post a Comment</legend>
            <label>Name
                <input type="text" name="name" maxlength="75"/>
            </label>
            <label>Email
                <input type="text" name="email" maxlength="150"/>
            </label>
            <label>Comment
                <textarea rows="10" cols="45" name="comment"></textarea>
            </label>
            <input type="hidden" name="blog_id" value="$blod_id"/>
            <input type="submit" name="submit" value="Post comment"/>
            <input type="submit" name="submit" value="Cancel"/>
        </fieldset>
        </form>
FORM;
        }

        public function saveComment($p)
        {
            //sanitize data and store it in variables
            $blog_id = htmlentities(strip_tags($p['blog_id']),ENT_QUOTES);
            $name = htmlentities(strip_tags($p['name']),ENT_QUOTES);
            $email = htmlentities(strip_tags($p['email']),ENT_QUOTES);
            $comment = htmlentities(strip_tags($p['comment']),ENT_QUOTES);
            //keep formatting of comments and remove extra whitespace
            $comment = nl2br(trim($comment));
            //generate and prepare SQL command
            $sql = "INSERT INTO comments (blog_id, name, email, comment)
                    VALUES (?,?,?,?)";
            if($stmt = $this->db->prepare($sql)){
                //execute cmd, free used memory, return true
                $stmt->execute(array($blog_id,$name,$email,$comment));
                $stmt->closeCursor();
                return TRUE;
            } else {
                return FALSE;
            }
        }

        public function retrieveComments($blog_id)
        {
            //get all the comments for the entry
            $sql = "SELECT id, name, email, comment, date
                    FROM comments
                    WHERE blog_id = ?
                    ORDER BY date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($blog_id));
            //loop throught returned rows
            while($comment = $stmt->fetch()){
                //store in memory for later use
                $this->comments[] = $comment;
            }
            //set up default response if no comments exist
            if(empty($this->comments)){
                $this->comments[] = array(
                    'id' => NULL,
                    'name' => NULL,
                    'email' => NULL,
                    'comment' => "There are no comments on this entry.",
                    'date' => NULL
                );
            }
        }

        public function showComments($blog_id)
        {
            $display = NULL;
            //retrieve comments for the entry
            $this->retrieveComments($blog_id);
            //loop through the store comments
            foreach($this->comments as $c){
                //prevent empty fields if no comments exist
                if(!empty($c['date']) && !empty($c['name'])){
                    // July 8, 2009 at 4:39PM
                    $format = "F j, Y \a\\t g:iA";
                    $date = date($format, strtotime($c['date']));
                    $byline = "<span><strong>$c[name]</strong>
                        [Posted on $date]</span>";
                    if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1) {
                        //generate delete link for the comment display
                        $admin = "<a href=\"/inc/update.inc.php"
                            . "?action=comment_delete&id=$c[id]\""
                            . "class=\"admin\">delete</a>";
                    } else {
                        $admin = NULL;
                    }
                } else {
                    //if we get here no comments exist
                    $byline = NULL;
                    $admin = NULL;
                }
                //assemble the pieces into a formatted comment
                $display .= "
                <p class = \"comment\">$byline$c[comment]$admin</p>";
            }
            //return all formatted comments as a string
            return $display;
        }

        public function confirmDelete($id)
        {
            //store the entry url if available
            if(isset($_SERVER['HTTP_REFERER'])){
                $url = $_SERVER['HTTP_REFERER'];
            } else {
                $url = '../';
            }

            return <<<FORM
<html>
    <head>
        <title>Confirm delete?</title>
        <link rel="stylesheet" type="text/css" href="/css/stylesheet.css"/>
    </head>
    <body>
        <form action = "/inc/update.inc.php" method = "post">
        <fieldset>
            <legend>Are you sure?</legend>
            <p>
                Are you sure you want to delete this comment?
            </p>
        <input type="hidden" name="id" value="$id"/>
        <input type="hidden" name="action" value="comment_delete"/>
        <input type="hidden" name="url" value="$url"/>
        <input type="submit" name="confirm" value="Yes"/>
        <input type="submit" name="confirm" value="No"/>
        </fieldset>
        </form>
    </body>
</html>
FORM;

        }

        public function deleteComment($id)
        {
            $sql = "DELETE FROM comments
                    WHERE id=?
                    LIMIT 1";
            if($stmt = $this->db->prepare($sql)){
                //execute cmd, free used memory,return true
                $stmt->execute(array($id));
                $stmt->closeCursor();
                return TRUE;
            } else {
                return FALSE;
            }
        }


    }
?>

