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


