<?php

/** @file alcohol.php
 * All alcohol page.
 * @author xandri03
 */

require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
restrict_page_access();

// Initialize the page
$page = new Page();


// Render the page
$page->render();

?>
