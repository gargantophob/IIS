<?php

/** @file new_report.php
 * New report creation.
 * @author xandri03
 */
 
require_once "library.php";
require_once "person.php";
require_once "html.php";

session_start();
restrict_page_access();

// Initialize the page
$page = new Page();

// Extract source, target
$source = Person::look_up($_SESSION["user"]);
$target = null;
if($_SERVER["REQUEST_METHOD"] == "GET") {
	if(isset($_GET["target"])) {
		$target = Person::look_up($_GET["target"]);
	}
}

// Extract alcoholic, expert
$alcoholic = $target == null ? "" : $target->email;
$expert = $source->role == "expert" ? $source->email : null;
$date = $bac = $alcohol = "";
$error = "";

function form_process() {
	global $alcoholic, $expert, $date, $bac, $alcohol;
	global $error;
	
	// Collect data
	$alcoholic = sanitize($_POST["alcoholic"]);
	$date = sanitize($_POST["date"]);
	$bac = sanitize($_POST["bac"]);
	$alcohol = sanitize($_POST["alcohol"]);
	
	// Check alcoholic existence
	$person = Person::look_up($alcoholic);
	if($person == null || $person->role != "alcoholic") {
		$error = "Such alcoholic does not exist.";
		return FALSE;
	}
	
	// Check date format
	$date = DateTime::createFromFormat("Y-m-d", $date);
	if($date === FALSE) {
		$error = "Wrong date format.";
		return FALSE;
	}
	$date = $date->format("Y-m-d");
	
	// Check blood content
	$value = floatval($bac);
	if($value <= 0 || $value > 1) {
		$error = "Wrong BAC value.";
		return FALSE;
	}
	$bac = $value;
	
	// Check alcohol identifier
	if(Alcohol::look_up($alcohol) == null) {
		$error = "Wrong alcohol identifier.";
		return FALSE;
	}
	
	// Success
	return TRUE;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
	if(form_process() === TRUE) {
		// Create a report
		$report = new Report(-1, $date, $bac, $alcoholic, $expert);
		$report->insert();
		redirect("profile.php");
	}
}

// Form
$form = new Form();

// Alcoholic
$block = new Block();
$input = new Input("text", "alcoholic", "Alcoholic:");
$input->set("value", $alcoholic);
$block->add($input);
if($source->email == $alcoholic) {
	$block->set("hidden", "true");
}
$form->add($block);

// Date
$input = new Input("text", "date", "Date:");
$input->set("value", $date);
$form->add($input);

// BAC
$input = new Input("text", "bac", "Blood content:");
$input->set("value", $bac);
$form->add($input);

// Alcohol id
$input = new Input("text", "alcohol", "Alcohol identifier:");
$input->set("value", $alcohol);
$form->add($input);

// Submit
$input = new Input("submit", "submit");
$input->set("value", "Report");
$form->add($input);

// Error message
$form->add_error($error);

// Form complete
$page->add($form);

// Alcohol cheat sheet
$page->add(new Link("alcohol.php", "Alcohol cheat sheet"));

// Render the page
$page->render();

?>

