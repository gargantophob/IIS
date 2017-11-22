<?php

/**
 * @file image.php
 * Image container.
 * 
 * Protocol:
 * [G] target - target profile email
 * Authorized access.
 * 
 * @author xandri03
 */

require_once "library.php";
require_once "entity.php";

session_start();
authorized_access();

$picture = null;

// Fetch user email
$target = get_data("target");
if($target != null) {
    // Look user up
    $target = Person::look_up($target);
    if($target != null) {
        // Extract image
        $picture = $target->picture;
    }
}

if($picture == null) {
	// Load default image
	$picture = file_get_contents("./images/default.jpg");
}

// Print image
header("content-type:image/jpeg");
echo($picture);

?>
