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

/** Create a list of people.
 * @param header table header
 * @param people array of emails
 */
function list_of_people($header, $people) {
	$table = new Table(array(new Text($header)));
	foreach($people as $person) {
		// Create a link
		$link = new Link("profile.php?target=$person", Person::look_up($person)->name);
		// Add a row
		$table->add(array($link));
	}
	return $table;
}

// Initialize the page
$page = new Page();

// Read source (session) and target
$source = Person::look_up($_SESSION["user"]);
$target = $source;	// default target is the source
if($_SERVER["REQUEST_METHOD"] == "GET") {
	if(isset($_GET["target"])) {
		$target = Person::look_up($_GET["target"]);
	}
	if(isset($_GET["date"])) {
		// Meet  the target
		$source->meet($target->email, $_GET["date"]);
		redirect("profile.php?target=$target->email");
	}
}

// Form handler
if($_SERVER["REQUEST_METHOD"] == "POST") {
	// Extract hidden target
	$target = Person::look_up($_POST["target"]);
	
	// Differentiate buttons
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
	}
	if(isset($_POST["support_stop"])) {
		// Stop supporting
		$source->drop($target->email);
	}
}

// Log out button
$form = new Form();

// Log out button
$input = new Input("submit", "log_out");
$input->set("value", "Log out");
$form->add($input);

// Form complete
$page->add($form);
$page->newline();

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
$page->add(new Text("Role: " . $target->role));
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
if($target->role == "alcoholic") {
	// Patrons
	$page->add(list_of_people("$pa patrons:", $target->patrons()));
	// Experts
	$page->add(list_of_people("$pa experts:", $target->experts()));
} else {
	// Alcoholics
	$page->add(list_of_people("$pa alcoholics:", $target->alcoholics()));
}

// List meetings
if($source == $target && $source->role != "expert") {
	$meetings = $source->meetings();
	$table = new Table(array(new Text("Your meetings:")));
	foreach($meetings as $meeting) {
		$meeting = Meeting::look_up($meeting);
		$person = $source->role == "alcoholic" ? $meeting->patron : $meeting->alcoholic;
		$link = new Link(
			"profile.php?target=$person", Person::look_up($person)->name
		);
		$date = new Text($meeting->date);
		$table->add(array($link, $date));
	}
	$page->add($table);	
}

// A bunch of buttons
$form = new Form();

// Hidden target parameter for POST transitions
$input = new Input("text", "target");
$input->set("id", "target");
$input->set("value", $target->email);
$input->set("hidden", "true");
$form->add($input);

// Hidden name parameter for JS
$input = new Input("text", "name");
$input->set("id", "name");
$input->set("value", $target->name);
$input->set("hidden", "true");
$form->add($input);

// Buttons
if($target == $source) {
	// Edit profile button
	$input = new Input("submit", "edit");
	$input->set("value", "Edit profile");
	$form->add($input);
} else {
	// Support start/drop buttons
	if($source->role != "alcoholic" && $target->role == "alcoholic") {
		if(array_search($target->email, $source->alcoholics()) !== FALSE) {
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
	
	// Create an appointment button
	if(
		(
			$source->role == "alcoholic"
			&& array_search($target->email, $source->patrons()) !== FALSE
		) || (
			$source->role == "patron"
			&& array_search($target->email, $source->alcoholics()) !== FALSE
		)
	) {
		$input = new Input("button", "meet");
		$input->set("id", "meet");
		$input->set("value", "Meet");
		$form->add($input);
	}
}
$page->add($form);
$page->newline();

// All members page
$page->add(new Link("members.php", "All members of Anonymous Alcoholics"));
$page->newline();

// All sessions page
$page->add(new Link("sessions.php", "All sessions"));
$page->newline();

// Report page
$page->add(new Link("new_report.php?target=$target->email", "Report alcohol consumption"));
$page->newline();

// Render the page
$page->render();

?>

<script>
var meet = document.getElementById("meet");
meet.onclick = function() {
	var name = document.getElementById("name").value;
	var email = document.getElementById("target").value;
	var dateStr;
	while(true) {
		dateStr = prompt(
			"When would you like to meet " + name + "? (yyyy-mm-dd)", ""
		);
		if(dateStr == null) {
			// Cancel
			break;
		}
		var date = new Date(dateStr);
		var year = date.getFullYear();
		var month = eval(date.getMonth())+1;
		var day = date.getDate();
		if(!isNaN(year) && !isNaN(month) && !isNaN(day)) {
			// Success
			window.location.replace(
				"profile.php?target=" + email + "&date=" + dateStr
			);
		}
	}
}
</script>
