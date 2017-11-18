<?php

/** @file signin.php
 * Sign in page.
 * @author xandri03
 */

require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();

/**/
/**/

// Redirect to profile page if already signed in.
if(!empty($_SESSION["user"])) {
	$_SESSION["target"] = $_SESSION["user"];
	redirect("profile.php");
}

// Initialize the page, not authorized -> false
$page = new Page(false);
$user = $error = "";

/** Form processor. Check username and his password.
 * @return TRUE on success, FALSE otherwise
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
		$_SESSION["user"] = $_SESSION["target"] = $user;
		redirect("profile.php?target=$user");
	}
	// Authentication failure
	$error = "Incorrect login/password.";
}

// Sign in form
$form = new Form();

// Username
$input = new Input("text", "user", "Email:");
$input->set("maxlength", "128");
$input->set("value", $user);
$form->add($input);

// Password
$input = new Input("password", "password", "Password:");
$input->set("maxlength", "128");
$form->add($input);

// Submit button
$input = new Input("submit", "submit");
$input->set("value", "Log in");
$form->add($input);

// Error message
$form->add_error($error);

// From created
$page->add($form);

// Render the page
$page->render();
?>
