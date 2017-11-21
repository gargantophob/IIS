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
 * Log out; @c session_start() must have been called before.
 */
function logout() {
    session_unset();
    session_destroy();
    redirect("index.php");
}

/**
 * Restrict page access to authorized users.
 */
function restrict_page_access() {
    if(empty($_SESSION["user"])) {
        logout();
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
