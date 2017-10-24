<?php

/** @file image.php
 * Image container.
 * @author xandri03
 */
require_once "person.php";

// Load the image using GET
$picture = person($_GET["user"])->picture;
if($picture == null) {
	// Load default image
	$picture = file_get_contents("./images/default.jpg");
}

// Print image
header("content-type:image/jpeg");
echo($picture);
?>
