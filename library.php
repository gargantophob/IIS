<?php

/**
 * @file library.php
 * Some useful ancillary procedures.
 * @author xandri03
 */

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
 * Restrict page access to authorized users.
 */
function restrict_page_access() {
    if(empty($_SESSION["user"])) {
        session_unset();
        session_destroy();
        redirect("index.php");
        //exit("You do not have permission to access this page.");
    }
}

/**
 * Sanitize form input.
 */
function sanitize($input) {
    return htmlspecialchars(stripslashes(trim($input)));
}

/**
 * Today's date
 */
function today() {
    return date("Y-m-d");
}

/**
 * Date comparator.
 * @return  TRUE if the date is in future.
 */
function is_future($date) {
    return strtotime(today()) <= strtotime($date);
}

?>
