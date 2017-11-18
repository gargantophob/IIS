<?php

/**
 * @file index.php
 * Sign in page.
 * @author xandri03
 */

// TODO html.php: navigation bar for different roles
// TODO signup.php/html.php: required fields
// TODO signup.php: textarea for experts
// TODO signup.php: picture context save
// TODO signup.php: profile deletion
// TODO activity timer
// TODO how to display tables?
// TODO sessions/meetings sort

require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();

// Log out redirections
if($_SERVER["REQUEST_METHOD"] == "GET") {
    if(isset($_GET["logout"])) {
        session_unset();
        session_destroy();
        redirect("index.php");
    }
}

// Redirect to profile page if already signed in.
if(isset($_SESSION["user"])) {
    redirect("profile.php");
}

// Initialize the page
$page = new Page();
$user = $error = "";

/**
 * Form processor. Check username and his password.
 * @return  TRUE on success, FALSE otherwise
 */
function form_process() {
    global $user, $error;

    // Collect username and password
    $user = sanitize($_POST["user"]);
    $password = sanitize($_POST["password"]);

    // Check against database
    $person = Person::look_up($user);
    return $person != null && $person->password == $password;
}

// Form handler
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process the form
    if(form_process() === TRUE) {
        // Authentication success
        $_SESSION["user"] = $user;
        redirect("profile.php");
    }
    
    // Authentication failure
    $error = "Incorrect login/password.";
}

// Welcome text
$element = new Text("Welcome to Alcoholics Anonymous!");
$page->add($element);
$page->newline(); $page->newline();

// Sign in form
$form = new Form();
$page->add($form);

// Username
$input = new Input("text", "user", "Email:");
$input->set("maxlength", "64");
$input->set("value", $user);
$form->add($input);

// Password
$input = new Input("password", "password", "Password:");
$input->set("maxlength", "64");
$form->add($input);

// Submit button
$input = new Input("submit", "submit");
$input->set("value", "Log in");
$form->add($input);

// Error message
$form->add_error($error);

// Sign up link
$element = new Text("New here? ");
$page->add($element);
$element = new Link("signup.php", "Sign up!");
$page->add($element);

// Render the page
$page->render();

?>