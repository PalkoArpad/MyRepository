<?php

    function retrieveEntries($db,$page, $url=NULL)
    {
        //get entries
        if(isset($url)) {             //if an entry url was given
            //load entry
            $sql = "SELECT id, page, title, longitude, latitude, image, entry, created
                    FROM entries
                    WHERE url=?
                    LIMIT 1";
            $stmt=$db->prepare($sql);
            $stmt->execute(array($url));
            //save returned entry array
            $e=$stmt->fetch();
            $fulldisp=1;
        }
        else {                        //if no entry url was given
            //load all entry
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

            if (!is_array($e)) {
                $fulldisp = 1;
                $e = array(
                    'title' => 'No entries Yet',
                    'entry' => '<a href="/admin/contact">Post an entry!</a>'
                );
            }
        }
        //add $fulldisp to $e array, because function can not return 2 values
        array_push($e,$fulldisp);
        //return loaded data
        return $e;
    }

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

    function inputError(){
        echo "error";
    }

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

    function deleteEntry($db,$url)
    {
        $sql = "DELETE FROM entries
                WHERE url=?
                LIMIT 1";
        $stmt = $db->prepare($sql);
        return $stmt->execute(array($url));
    }

    function makeURL($title)
    {
        $patterns = array(
            '/\s+/',
            '/(?!-)\W+/'
        );
        $replacements = array('-','');
        return preg_replace($patterns,$replacements,strtolower($title));
}

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

    function formatImage($img=NULL, $alt=NULL)
    {
        if($img!=NULL){
            return '<img src="'.$img.'"alt="'.$alt.'"/>';
        } else {
            return NULL;
        }
    }

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

    function postToTwitter($title)
    {
        $full = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $short = shortenURL($full);
        $status = $title. ' '.$short;
        return 'http://twitter.com/?status='.urlencode($status);
    }


?>