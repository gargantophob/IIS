<?php

/** @file session.php
 * Session info.
 * @author xandri03
 */
 
require_once "library.php";
require_once "person.php";
require_once "html.php";

session_start();
restrict_page_access();

$session = null;
$source = Person::look_up($_SESSION["user"]);
if($_SERVER["REQUEST_METHOD"] == "GET") {
	if(isset($_GET["session"])) {
		$session = Session::look_up($_GET["session"]);
		if($session == null) {
			// Invalid session identifier
			// TODO redirect to all sessions
			redirect("sessions.php");
		}
	} else {
		// TODO redirect to all sessions
		redirect("sessions.php");
	}
}

// Form handler
if($_SERVER["REQUEST_METHOD"] == "POST") {
	// Extract session
	$session = Session::look_up($_POST["session"]);
	// Differentiate buttons
	if(isset($_POST["enroll"])) {
		$source->enroll($session->id);
	}
	if(isset($_POST["unenroll"])) {
		$source->unenroll($session->id);
	}
}

// Initialize the page
$page = new Page();

// Print session info
$page->add(new Text("Place: " . Place::look_up($session->place)->address));
$page->newline();

$page->add(new Text("Date: " . $session->date));
$page->newline();

$page->add(new Text("Leader: "));
$leader = Person::look_up($session->leader);
$page->add(new Link("profile.php?target=$leader->email", $leader->name));
$page->newline();

// Print members
$table = new Table(array(new Text("members")));
$members = $session->members();
foreach($members as $member) {
	$person = Person::look_up($member);
	$link = new Link(
		"profile.php?target=$person->email",
		$person->name
	);
	$table->add(array($link));
}
$page->add($table);


// Enroll/unenroll buttons
$form = new Form();

$input = new Input("text", "session");
$input->set("value", "$session->id");
$input->set("hidden", "true");
$form->add($input);

if(array_search($source->email, $members) !== FALSE) {
	$name = "unenroll";
	$value = "Unenroll";
} else {
	$name = "enroll";
	$value = "Enroll";
}
$input = new Input("submit", $name);
$input->set("value", $value);
$form->add($input);

$page->add($form);
$page->newline();

// Render the page
$page->render();

?>

<script>
	var meet = document.getElementById("new_place");
	meet.onclick = function() {
		var success = false;
		var address;
		while(true) {
			address = prompt(
				"Enter address:", ""
			);
			if(address == null) {
				// Cancel
				break;
			}
			if(address != "") {
				// Success
				window.location.replace("places.php?address=" + address);
			}
		}
	}
</script>


