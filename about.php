<?php

require_once "html.php";

$page = new Page(2, false);
$page->add(new Text('TODO'));

// Render the page
$page->render();

?>
