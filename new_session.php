<?php

/** @file new_session.php
 * New session creation.
 * @author xandri03
 */
 
require_once "library.php";
require_once "person.php";
require_once "html.php";

session_start();
restrict_page_access();

// Initialize the page
$page = new Page();

$date = $error = "";

function form_process() {
	global $place, $date, $error;
	$place = Place::look_up($_POST["place"]);
	$date = sanitize($_POST["date"]);
	
	// Check date format
	$d = DateTime::createFromFormat("Y-m-d", $date);
	if($d === FALSE) {
		$error = "Wrong date format.";
		return FALSE;
	}
	$date = $d->format("Y-m-d");
	
	// Success
	return TRUE;
}

// Extract place identifier
if($_SERVER["REQUEST_METHOD"] == "GET") {
	if(isset($_GET["place"])) {
		$place = Place::look_up($_GET["place"]);
	}
}
if($_SERVER["REQUEST_METHOD"] == "POST") {
	if(form_process() === TRUE) {
		// Create new session
		$session = new Session(-1, $date, $place->id, $_SESSION["user"]);
		$session->insert();
		// TODO redirect to session page
		redirect("sessions.php");
	}
}

// Prompt
$page->add(new Text("Step 2: pick a date (yyyy-mm-dd):"));
$page->newline();

$page->add(new Text("Place: $place->address"));
$page->newline();

// Form
$form = new Form();

// Hidden place id
$input = new Input("text", "place");
$input->set("value", $place->id);
$input->set("hidden", "true");
$form->add($input);

// Date input
$input = new Input("text", "date");
$input->set("value", $date);
$form->add($input);

// Submit button
$input = new Input("submit", "submit");
$input->set("value", "Submit");
$form->add($input);

$form->add_error($error);

// Form complete
$page->add($form);

// Render the page
$page->render();

?>

