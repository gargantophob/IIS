<?php

/**
 * @file library.php
 * Some useful ancillary procedures.
 * @author xandri03
 */

/**
 * Retrieve SESSION argument.
 * @return  argument value, null if not set
 */
function session_data($arg) {
    if(isset($_SESSION[$arg])) {
        return $_SESSION[$arg];
    }
    return null;
}

/**
 * Retrieve GET argument.
 * @return  argument value, null if not set
 */
function get_data($arg) {
    if(isset($_GET[$arg])) {
        return $_GET[$arg];
    }
    return null;
}

/**
 * Retrieve POST argument.
 * @return  argument value, null if not set
 */
function post_data($arg) {
    if(isset($_POST[$arg])) {
        return $_POST[$arg];
    }
    return null;
}

/**
 * Construct a link with GET parameters.
 * @param target        target script
 * @param parameters    associative array of parameters
 * @return              string representation of a link
 */
function plink($target, $parameters) {
    $link = $target . "?";
    $first = TRUE;
    foreach($parameters as $key => $value) {
        if($first === TRUE) {
            $first = FALSE;
        } else {
            $link .= "&";
        }
        $link .= $key . "=" . $value;
    }
    return $link;
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
 * Redirect to main page.
 */
function recover() {
    redirect("index.php");
}

/**
 * Log out; @c session_start() must have been called before.
 */
function logout() {
    session_unset();
    session_destroy();
    recover();
}

/**
 * Restrict page access to authorized users.
 */
function authorized_access() {
    if(session_data("user") == null) {
        logout();
    }
}

/**
 * Sanitize form input.
 */
function sanitize($input) {
    return htmlspecialchars(stripslashes(trim(post_data($input))));
}

/**
 * Today's date
 */
function today() {
    return date("Y-m-d");
}

/**
 * Parse date string.
 * @return  null on failure, valid date string on success
 */
function parse_date($date) {
    $date = DateTime::createFromFormat("Y-m-d", $date);
    if($date === FALSE) {
        return null;
    }
    return $date->format("Y-m-d");
}

/**
 * Date comparator.
 * @return  TRUE if the date is in future.
 */
function is_future($date) {
    return strtotime(today()) <= strtotime($date);
}

?>
