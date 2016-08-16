<?php
    /**Function to retrieve entries
    *
    * @param $db
    * @param $page
    * @param null $url
    * @return array|null
    */
    function retrieveEntries($db,$page, $url=NULL)
    {
        if(isset($url)) {             //If an entry url was given
            //Load a specific entry
            $sql = "SELECT id, page, title, longitude, latitude, image, entry, created
                    FROM entries
                    WHERE url=?
                    LIMIT 1";
            $stmt=$db->prepare($sql);
            $stmt->execute(array($url));
            //Save returned entry array
            $e=$stmt->fetch();
            $fulldisp=1;
        } else {                        //If no entry url was given
            //Load all entries
            $sql = "SELECT id, page, title, longitude, latitude, image, entry, url, created 
                    FROM entries
                    WHERE page=?
                    ORDER BY created DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute(array($page));
            $e = NULL;
            while($row = $stmt->fetch()) {
                if($page == 'blog') {
                    $e[] = $row;
                    $fulldisp = 0;
                } else {
                    $e = $row;
                    $fulldisp = 1;
                }
            }
            //If there are no entries
            if (!is_array($e)) {
                $fulldisp = 1;
                $e = array('title' => 'No entries Yet',
                           'entry' => '<a href="/admin/contact">Post an entry!</a>'
                    );
            }
        }
        //Add $fulldisp to $e array, because function can not return two values
        array_push($e,$fulldisp);
        //return loaded data
        return $e;
    }

    /**
     * Sanitizes data
     *
     * @param $data
     * @return array|string|sanitizeData
     */
    function sanitizeData($data)
    {
        //if data is not an array -> strip_tags()
        if(!is_array($data))
        {
            return strip_tags($data,"<a>");//remove all tags except <a>
        }
        //if data is array, process each element
        else
        {
            //call sanitize recursively
            return array_map('sanitizeData',$data);
        }
    }

    /**Build links for admin
    * @param $page
    * @param $url
    * @return $admin array
    */
    function adminLinks($page, $url)
    {
        //format link for each option
        $editURL = "/admin/$page/$url";
        $deleteURL = "/admin/delete/$url";

        //make hyperlink and add to array
        $admin['edit'] = "<a href=\"$editURL\">Edit</a>";
        $admin['delete'] = "<a href=\"$deleteURL\">Delete</a>";
        return $admin;
    }

    /**
    * Generates a form to confirm you decision on deleting entry
    *
    * @param $db
    * @param $url
    * @return string
    */
    function confirmDelete($db, $url)
    {
        $e = retrieveEntries($db,'',$url);
        return <<<FORM
    <form action="/admin.php" method="post">
        <fieldset>
          <legend>Are you sure?</legend>
            <p> Are you sure you want to delete the entry $e[title]"?</p>
            <input type="submit" name="submit" value="Yes" />
            <input type="submit" name="submit" value="No" />
            <input type="hidden" name="action" value="delete"/>
            <input type="hidden" name="url" value="$url"/>
        </fieldset>
    </form>
FORM;
    }

    /**
    *  Deletes an entry
    *
    * @param $db
    * @param $url
    * @return mixed
    */
    function deleteEntry($db,$url)
    {
        $sql = "DELETE FROM entries
                WHERE url=?
                LIMIT 1";
        $stmt = $db->prepare($sql);
        return $stmt->execute(array($url));
    }

    /**
     * Generates friendly url
     *
     * @param $title
     * @return mixed
     */
    function makeURL($title)
    {
        $patterns = array(
            '/\s+/',
            '/(?!-)\W+/'
        );
        $replacements = array('-','');
        return preg_replace($patterns,$replacements,strtolower($title));
    }

    /**
     * Generates the folder where the image is stored, and the image name
     *
     * @param $db
     * @param $url
     * @return mixed
     */
    function getImagePath($db,$url)
    {
        $sql = "SELECT image FROM entries
                WHERE url=?
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(array($url));
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        return $p['image'];
    }

    /**
     * Formats image
     *
     * @param null $img
     * @param null $alt
     * @return null|string
     */
    function formatImage($img=NULL, $alt=NULL)
    {
        if($img!=NULL){
            return '<img src="'.$img.'"alt="'.$alt.'"/>';
        } else {
            return NULL;
        }
    }

    //Form for creating a new admin user
    function createUserForm()
    {
        return <<<FORM
    <form action="/inc/update.inc.php" method = "post">
        <fieldset>
            <legend>Create a New Administrator</legend>
                <label>Username
            <input type="text" name="username" maxlength="75"/>
                </label>
                <label>Password
            <input type="password" name="password"/>
                </label>
            <input type="submit" name="submit" value="Create"/>
            <input type="submit" name="submit" value="Cancel"/>
            <input type="hidden" name="action" value="createuser"/>
        </fieldset>
    </form>
FORM;
    }

     /**Shortens url using bit.ly
      *
      * @param $url
      * @return mixed
      */
    function shortenURL($url)
    {
        //format a all to the bit.ly API
        $api = 'http://api.bit.ly/shorten';
        $param = 'version=2.0.1&longUrl='.urlencode($url).'&login=phpfab'.
            '&apiKey=R_7473a7c43c68a73ae08b68ef8e16388e&format=xml';
        //open connection and load response
        $uri = $api . "?" . $param;
        $response = file_get_contents($uri);
        //parse the output and return shortened URL
        $bitly = simplexml_load_string($response);
        return $bitly->results->nodeKeyVal->shortUrl;
    }

    /**
     * Generates a link to post on twitter
     *
     * @param $title
     * @return string
     */
    function postToTwitter($title)
    {
        $full = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $short = shortenURL($full);
        $status = $title. ' '.$short;
        return 'http://twitter.com/?status='.urlencode($status);
    }

    function postToFacebook()
    {
        $full = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        return "http://www.facebook.com/share.php?u=".$full;
    }


?>