<?php

/**
 * @file index.php
 * Sign in page.
 * 
 * Protocol:
 * [G] logout   - logout flag
 * [S] user     - redirect to profile page
 * 
 * @author xandri03
 */

// additional TODOs:
// html.php: fancy forms
// html.php: skip hidden input forms
// signup.php/html.php: textarea for experts
// signup.php: picture context save

require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();

// Log out redirections
if($_SERVER["REQUEST_METHOD"] == "GET") {
    if(get_data("logout") != null) {
        logout();
    }
}

// Redirect to profile page if already signed in.
if(session_data("user") != null) {
    redirect("profile.php");
}

$email = $error = "";

/**
 * Form processor.
 */
function form_process() {
    global $email, $error;

    // Collect username and password
    $email = sanitize("email");
    $password = sanitize("password");
    
    // Check against database
    $person = Person::look_up($email);
    return $person != null && $person->password == $password;
}

// Form handler
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process the form
    if(form_process()) {
        // Authentication success
        $_SESSION["user"] = $email;
        redirect("profile.php");
    }
    
    // Authentication failure
    $error = "Incorrect login/password.";
}

// Initialize the page
$page = new Page();

// Welcome text
$page->add(new Text("Welcome to Alcoholics Anonymous!"));
$page->newline();
$page->newline();

// Sign in form
$form = new Form();
$page->add($form);

// Username
$input = new Input("text", "email", "Email:");
$input->set("maxlength", "64");
$input->set("value", $email);
$form->add($input);

// Password
$input = new Input("password", "password", "Password:");
$input->set("maxlength", "64");
$form->add($input);

// Submit button
$input = new Input("submit", "submit");
$input->set("value", "Log in");
$input->set("class", "button");
$form->add($input);

// Error message
$form->add_error($error);

// Sign up link
$page->add(new Text("New here? "));
$link=new Link("signup.php", "Sign up!");
$link->set("class","button");
$page->add($link);

// Render the page
$page->render();

?>
