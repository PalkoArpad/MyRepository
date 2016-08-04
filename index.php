<?php
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
	//determine if an entry ID was passed in the URL
	///$id = (isset($_GET['id'])) ? (int) $_GET['id'] : NULL;
	//load entries
	$e = retrieveEntries($db,$page,$url);
	//get $fulldisp flag and remove it
	$fulldisp = array_pop($e);
	//sanitize
	$e = sanitizeData($e);
?>

<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
	<link rel="stylesheet" href="/css/stylesheet.css" type="text/css"/>
	<title> Simple Blog </title>
</head>

<body>
	<h1> Simple Blog Application </h1>
	<div id="entries">
<?php
	//format entries
	//if the full display flag is set show entry
	if($fulldisp == 1) {
        //get url
        $url = (isset($url)) ? $url : $e['url'];
?>
		<h2> <?php echo $e['title']?> </h2>
		<p> <?php echo $e['entry']?> </p>

        <?php if ($page == 'blog'): ?>
		    <p class="backlink">
		    	<a href="./">Back to the Latest Entries</a>
		    </p>
        <?php endif; ?>
<?php
	} else {
		foreach($e as $entry) {
			?>
			<p>
				<a href="/<?php echo $entry['page']?>/
						<?php echo $entry['url']?>">
						<?php echo $entry['title'] ?>
				</a>
			</p>
            <?php
		}
	}
?>
	<p class="backlink">
        <?php
        //if ($page=='blog') :
        ?>
		<a href="/admin/<?php echo $page ?>">Post a New Entry</a>
        <?php //endif;
        ?>
	</p>
	</div>
</body>
</html>

