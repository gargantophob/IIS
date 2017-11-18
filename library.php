<?php

/**
 * @file library.php
 * Some useful ancillary procedures.
 * @author xandri03
 */
 
/**
 * Restrict page access to authorized users.
 */
function restrict_page_access() {
	if(empty($_SESSION["user"])) {
		exit("You do not have permission to access this page.");
	}
}

/**
 * Redirect to URL.
 */
function redirect($url, $permanent = FALSE) {
    if (!headers_sent()) {
        header("Location: " . $url, TRUE, ($permanent === TRUE) ? 301 : 302);
    }
    exit("Internal error.");
}

/**
 * Sanitize form input.
 */
function sanitize($input) {
	return htmlspecialchars(stripslashes(trim($input)));
}

?>
