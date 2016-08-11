<?php
    include_once 'db.inc.php';

    class Comments
    {
        /**
         * @var $comments -> array containing the comment entries
         */
        public $db;
        public $comments;
        //when instantiated, open db connection
        public function __construct()
        {
            $this->db = new PDO(DB_INFO,DB_USER,DB_PASS);
        }

        public function showCommentForm($blog_id)
        {
            $errors =
                array(
                1 => '<p class="error">Something went wrong while saving '
                        . 'your comment. Please try again!</p>',
                2 => '<p class="error">Please provide a valid email address!</p>',
                3 => '<p class="error">Please answer the anti-spam question correctly!</p>',
                4 => '<p class="error">Please enter your name!</p>',
                5 => '<p class="error">Please fill out the comment field!</p>',
                12 => '<p class="error">Please provide a valid name, email and comment!</p>',
                13 => '<p class="error">Please provide a valid name and email!</p>',
                14 => '<p class="error">Please provide a valid email and comment!</p>',
                15 => '<p class="error">Please provide a valid name and comment!</p>'
                );
            if(isset($_SESSION['error'])) {
                $error = $errors[$_SESSION['error']];
            } else {
                $error = NULL;
            }
            //check if session variable exist
            if(isset($_SESSION['c_name'])) {
                $n = $_SESSION['c_name'];
            } else {
                $n = NULL;
            }
            if(isset($_SESSION['c_email'])) {
                $e = $_SESSION['c_email'];
            } else {
                $e = NULL;
            }
            if(isset($_SESSION['c_comment'])) {
                $c = $_SESSION['c_comment'];
            } else {
                $c = NULL;
            }
            //generate a challenge question
            $challenge = $this->generateChallenge();

            return <<<FORM
        <form action="/inc/update.inc.php" method="post" id="comment-form">
        <fieldset>
            <legend>Post a Comment</legend>$error
            <label>Name
                <input type="text" name="name" maxlength="75" value="$n"/>
            </label>
            <label>Email
                <input type="text" name="email" maxlength="150" value="$e"/>
            </label>
            <label>Comment
                <textarea rows="10" cols="45" name="comment">$c</textarea>
            </label>$challenge
            <input type="hidden" name="blog_id" value="$blog_id"/>
            <input type="submit" name="submit" value="Post comment"/>
            <input type="submit" name="submit" value="Cancel"/>
        </fieldset>
        </form>
FORM;
    }

        public function saveComment($p)
        {
            //save comment info in a session
            $_SESSION['c_name'] = htmlentities($p['name'], ENT_QUOTES);
            $_SESSION['c_email'] = htmlentities($p['email'], ENT_QUOTES);
            $_SESSION['c_comment'] = htmlentities($p['comment'], ENT_QUOTES);
            //make sure the email,name and comment are valid
            if(!$this->validateEmail($p['email']) && !$this->validateName($p['name']) && !$this->validateComment($p['comment'])) {
                $_SESSION['error'] = 12;
                return;
            } else if(!$this->validateEmail($p['email']) && !$this->validateName($p['name'])) {
                //check if email and name are valid
                $_SESSION['error'] = 13;
                return;
            } else if(!$this->validateEmail($p['email']) && !$this->validateComment($p['comment'])) {
                //check if email and comment are valid
                $_SESSION['error'] = 14;
                return;
            } else if(!$this->validateName($p['name']) && !$this->validateComment($p['comment'])) {
                //check if name and comment are valid
                $_SESSION['error'] = 15;
                return;
            } else if(!$this->validateEmail($p['email'])) {
                $_SESSION['error'] = 2;
                return;
            } else if(!$this->validateName($p['name'])) {
                $_SESSION['error'] = 4;
                return;
            } else if(!$this->validateComment($p['comment'])) {
                $_SESSION['error'] = 5;
                return;
            } else if(!$this->verifyResponse($p['s_q'],$p['s_1'],$p['s_2'])) {
                //make sure the challenge was answered properly
                $_SESSION['error'] = 3;
                return;
            }

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
                //destroy the comment information to empty the form
                unset($_SESSION['c_name'],$_SESSION['c_email'],
                      $_SESSION['c_comment'],$_SESSION['error']);
                return;
            } else {
                $_SESSION['error'] = 1;
                return;
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
            //loop through the returned rows
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
            foreach($this->comments as $c) {
                //prevent empty fields if no comments exist
                if(!empty($c['date']) && !empty($c['name'])) {
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
            if($stmt = $this->db->prepare($sql)) {
                //execute cmd, free used memory,return true
                $stmt->execute(array($id));
                $stmt->closeCursor();
                return TRUE;
            } else {
                return FALSE;
            }
        }

        private function validateEmail($email)
        {
            //matches valid email address
            $p = '/^[\w-]+(\.[\w-]+)*@[a-z0-9-]+'
                .'(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i';
            //if match found, return true, otherwise return false
            return(preg_match($p,$email)) ? TRUE : FALSE;
        }

        private function validateName($name)
        {
            if($name != NULL && $name != ""){
                return TRUE;
            } else {
                return FALSE;
            }
        }

        private function validateComment($comment)
        {
            if($comment != NULL && $comment != ""){
                return TRUE;
            } else {
                return FALSE;
            }
        }

        private function generateChallenge()
        {
            //store two random numbers in an array
            $numbers = array(mt_rand(1,4),mt_rand(1,4));
            //store the correct answer in a session
            $_SESSION['challenge'] = $numbers[0] + $numbers[1];
            //convert the numbers to their ASCII
            $converted = array_map('ord', $numbers);
            //generate a math question as HTML markup
            return "
            <label>&#87;&#104;&#97;&#116;&#32;&#105;&#115;&#32;
                    &#$converted[0];&#32;&#43;&#32;&#$converted[1];&#63;
                    <input type=\"text\" name=\"s_q\" />
            </label>";
        }

        private function verifyResponse($resp)
        {
            //grab session value and destroy it
            $val = $_SESSION['challenge'];
            unset($_SESSION['challenge']);
            return $resp == $val;
        }

    }
?>

