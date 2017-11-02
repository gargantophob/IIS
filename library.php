<?php

/** @file library.php
 * Some useful procedures, mainly SQL primitives.
 * @author xandri03
 */
 
/** Restrict page access to authorized users. */
function restrict_page_access() {
	if(empty($_SESSION["user"])) {
		exit("You do not have permission to access this page.");
	}
}

/** Redirect to URL. */
function redirect($url, $permanent = false) {
    if (!headers_sent()) {
        header("Location: " . $url, true, ($permanent === true) ? 301 : 302);
    }
    exit();
}

/** Sanitize form input.
 * @param input input string
 * @return sanitized string
 */
function sanitize($input) {
	return htmlspecialchars(stripslashes(trim($input)));
}

?>
