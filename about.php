<?php

require_once "html.php";

$page = new Page(2);
$page->add(new Text('TODO'));

// Render the page
$page->render();

?>
