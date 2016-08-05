<?php
    include_once '../inc/functions.inc.php';
    include_once '../inc/db.inc.php';
    //open db connection
    $db = new PDO(DB_INFO,DB_USER,DB_PASS);
    //load all entries
    $e = retrieveEntries($db,'blog');
    array_pop($e);
    $e = sanitizeData($e);
    //add a content type header to ensure proper execution
    header('Content-Type: application/rss+xml');
    //output xml declaration
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<rss version="2.0">
    <channel>

        <title>My Simple Blog </title>
            <link>http://www.test.local/Blog/</link>
            <description>This blog is awesome.</description>
            <language>en-us</language>
        <?php
        //loop through entries and generate rss items
        foreach($e as $e):
            //escape html to avoid errors
            $entry = htmlentities($e['entry']);
            //build full url to the entry
            $url = 'http://www.test.local/blog/'.$e['url'];
            //format date correctly
            $date = date(DATE_RSS, strtotime($e['created']));
        ?>
        <item>
                <title><?php echo $e['title'];?></title>
                <description><?php echo $entry;?></description>
                <link><?php echo $url; ?></link>
                <guid><?php echo $url;?></guid>
                <pubDate><?php echo $date;?></pubDate>
        </item>
        <?php endforeach;?>

    </channel>
</rss>
