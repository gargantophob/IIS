<?php

/** @file profile.php
 * Profile page.
 * @author xandri03
 */

require_once "library.php";
require_once "person.php";
require_once "html.php";

session_start();
restrict_page_access();

// Initialize the page
$page = new Page();

// Read source (session) and target
$source = person($_SESSION["user"]);
$target = $source;	// default target is the source
if($_SERVER["REQUEST_METHOD"] == "GET") {
	if(isset($_GET["target"])) {
		$target = person($_GET["target"]);
	}
}

// Form handler
if($_SERVER["REQUEST_METHOD"] == "POST") {
	// Extract hidden target
	$target = person($_POST["target"]);
	
	if(isset($_POST["log_out"])) {
		// Log out
		session_unset();
		session_destroy();
		redirect("signin.php");
	}
	if(isset($_POST["edit"])) {
		// Edit profile
		redirect("signup.php");
	}
	if(isset($_POST["support_start"])) {
		// Start supporting
		$source->support($target->email);
		redirect("profile.php?target=$target->email");
	}
	if(isset($_POST["support_stop"])) {
		// Stop supporting
		$source->drop($target->email);
		redirect("profile.php?target=$target->email");
	}
}

// Preprocess some target data
$birthdate = $target->birthdate;
if($birthdate == null) {
	$birthdate = "?";
}
$gender = $target->gender;
if($gender == null) {
	$gender = "?";
} else {
	$gender = $gender == "M" ? "Male" : "Female";
}

// Print general data
$page->add(new Text("Name: " . $target->name));
$page->newline();
$page->add(new Text("Email: " . $target->email));
$page->newline();
$page->add(new Text("Birthdate: " . $birthdate));
$page->newline();
$page->add(new Text("Gender: " . $gender));
$page->newline();
$page->add(new Text("Role: " . $target->role()));
$page->newline();
$page->add(new Image("image.php?user=$target->email"));
$page->newline();

// Pick a correct possessive adjective
if($source == $target) {
	$pa = "Your";
} elseif($target->gender == "F") {
	$pa =  "Her";
} else {
	$pa = "His";
}

// Print specific data
if($target->role() == "alcoholic") {
	// Patrons
	$page->add(list_of_people("$pa patrons:", $target->list_patrons()));
	// Experts
	$page->add(list_of_people("$pa experts:", $target->list_experts()));
} else {
	// Alcoholics
	$page->add(list_of_people("$pa alcoholics:", $target->list_alcoholics()));
}

// List meetings
if($source->role() != "expert") {
	$meetings = $source->meetings();
	$table = new Table(array(new Text("My meetings:")));
	foreach($meetings as $meeting) {
		$meeting = Meeting::meeting($meeting);
		$person = $source->role() == "alcoholic" ? $meeting->patron : $meeting->alcoholic;
		$link = new Link("profile.php?target=$person", person($person)->name);
		$date = new Text($meeting->date);
		$table->add(array($link, $date));
	}
	$page->add($table);	
}
	
// A bunch of buttons
$form = new Form();

// Hidden target parameter for POST transitions
$input = new Input("text", "target");
$input->set("value", $target->email);
$input->set("hidden", "true");
$form->add($input);

// Log out button
$input = new Input("submit", "log_out");
$input->set("value", "Log out");
$form->add($input);

if($target == $source) {
	// Edit profile button
	$input = new Input("submit", "edit");
	$input->set("value", "Edit profile");
	$form->add($input);
} else {
	if($source->role() != "alcoholic" && $target->role() == "alcoholic") {
		if(array_search($target->email, $source->list_alcoholics()) !== FALSE) {
			// Support stop button
			$input = new Input("submit", "support_stop");
			$input->set("value", "Stop supporting");
			$form->add($input);
		} else {
			// Support start button
			$input = new Input("submit", "support_start");
			$input->set("value", "Start supporting");
			$form->add($input);
		}
	}
}
$page->add($form);
$page->newline();

// All members page
$page->add(new Link("members.php", "All members of Anonymous Alcoholics"));
$page->newline();

// Render the page
$page->render();

?>
