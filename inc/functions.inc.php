<?php

    function retrieveEntries($db, $id=NULL)
    {
        //get entries
        if(isset($id))                  //if an entry ID was given, load it
        {
            //load entry
            $sql = "SELECT title, entry
                    FROM entries
                    WHERE id=?
                    LIMIT 1";
            $stmt=$db->prepare($sql);
            $stmt->execute(array($_GET['id']));
            //save returned entry array
            $e=$stmt->fetch();
            $fulldisp=1;
        }
        else                           //if no entry ID was given, load all
        {
            //load all entry
            $sql = "SELECT id, title 
                    FROM entries
                    ORDER BY created DESC";
            foreach($db->query($sql) as $row) {
                $e[] = array(
                    'id' => $row['id'],
                    'title' => $row['title']
                );
            }
            $fulldisp = 0;

            if (!is_array($e)) {
                $fulldisp = 1;
                $e = array(
                    'title' => 'No entries Yet',
                    'entry' => '<a href="/index.php">Post an entry!</a>'
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
?>