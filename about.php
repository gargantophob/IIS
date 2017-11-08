<?php

require_once "html.php";

// not authorized -> false
$page = new Page(false);
$page->add(new Text('TODO'));

// Render the page
$page->render();

?>
