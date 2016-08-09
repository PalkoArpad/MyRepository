<?php
    session_start();
	include_once 'inc/functions.inc.php';
	include_once 'inc/db.inc.php';
	//open db connection
	$db = new PDO(DB_INFO,DB_USER,DB_PASS);
    //what page is requested
    if(isset($_GET['page'])) {
        $page=htmlentities(strip_tags($_GET['page']));
    } else {
        $page='blog';
    }
    $url = (isset($_GET['url'])) ? $_GET['url'] : NULL;
	//load entries
	$e = retrieveEntries($db,$page,$url);
	//get $fulldisp flag and remove it
	$fulldisp = array_pop($e);
	//sanitize
	$e = sanitizeData($e);
//    echo "<pre>";
//    print_r($e);
//    echo "</pre>";
//    die;
?>

<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
	<link rel="stylesheet" href="/css/stylesheet.css" type="text/css"/>
    <link rel="alternate" type="application/rss+xml" title="My Simple Blog - RSS 2.0"
          href="/feeds/rss.php"/>
	<title> Simple Blog </title>
</head>

<body>
	<h1> Simple Blog Application </h1>
    <ul id="menu">
        <li><a href="/blog/">Blog</a></li>
        <li><a href="/about/">About the Author</a></li>
		<li><a href="/contact/">Contact</a></li>
    </ul>
    <?php
        if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1):?>
        <p id="control_panel">
            You are logged in!
            <a href="/inc/update.inc.php?action=logout">Log out</a>.
        </p>
        <?php endif; ?>
	<div id="entries">
<?php
	//format entries
	//if the full display flag is set show entry
	if($fulldisp == 1) {
        //get url
        $url = (isset($url)) ? $url : $e['url'];
        if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1) {
            //build admin links
            $admin = adminLinks($page, $url);
        } else {
            $admin = array('edit' => NULL, 'delete' => NULL);
        }
		//format the image if it exists
		$img = formatImage($e['image'],$e['title']);
        if($page == 'blog'){
            //load comment object
            include_once 'inc/comments.inc.php';
            $comments = new Comments();
            $comments_disp = $comments->showComments($e['id']);
            $comment_form = $comments->showCommentForm($e['id']);
			//generate post to Twitter link
			$twitter = postToTwitter($e['title']);
        } else {
            $comment_form = NULL;
			$twitter = NULL;
        }

?>
		<h2> <?php echo $e['title']?></h2>
		<p> <?php echo $img,"<br/>",$e['entry']?></p>
<!--         harta -->
        <?php if($e['longitude'] != NULL && $e['latitude'] != NULL){?>
        <div id="map"></div>
        <script>
            function initMap()
            {
                var mapDiv = document.getElementById('map');
                var map = new google.maps.Map(mapDiv, {
                        center : {lat: <?php echo $e['latitude']?>, lng: <?php echo $e['longitude']?>},
                        zoom : 8
                    }
                )
            }
        </script>
        <script async defer src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyCBHS5iSlZuLpuSZmIXVLuwsqnieIPKNyE&callback=initMap"></script>
		<p> <?php }?></p>
        <p>
            <?php echo $admin['edit']?>
            <?php if($page == 'blog') echo $admin['delete']?>
        </p>
        <?php if ($page == 'blog'): ?>
		    <p class="backlink">
                <a href="<?php echo $twitter?>">Post to Twitter</a><br/>
		    	<a href="./">Back to the Latest Entries</a>
		    </p>
        <h3>Comments for this entry</h3>
        <?php echo $comments_disp, $comment_form; endif; ?>
<?php
	} else {
		foreach($e as $entry) {
			?>
			<p>
				<a href="/<?php echo $entry['page']?>/
						<?php echo $entry['url']?>">
						<?php echo $entry['title']?>
				</a>
			</p>
            <?php
		}
	}
?>
	<p class="backlink">
        <?php
        if ($page=='blog' && isset($_SESSION['loggedin'])
                            && $_SESSION['loggedin'] == 1) :
        ?>
		<a href="/admin/<?php echo $page?>">Post a New Entry</a>
        <?php endif; ?>
	</p>
    <p>
        <a href="/feeds/rss.php">Subscribe via RSS!</a>
    </p>
	</div>
</body>
</html>

