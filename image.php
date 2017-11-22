<?php

/**
 * @file image.php
 * Image container.
 * @author xandri03
 */

/**
 * Interface:
 * [GET] user - TODO
 */
require_once "library.php";
require_once "entity.php";

// Load the image using GET
$user = get_data("user");
if($user == null) {
    recover();
}

$picture = Person::look_up($user)->picture;
if($picture == null) {
	// Load default image
	$picture = file_get_contents("./images/default.jpg");
}

// Print image
header("content-type:image/jpeg");
echo($picture);

?>
