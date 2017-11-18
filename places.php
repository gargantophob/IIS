<?php

/** @file places.php
 * List of places.
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
restrict_page_access();

if($_SERVER["REQUEST_METHOD"] == "GET") {
	if(isset($_GET["address"])) {
		// New address
		$place = new Place(-1, $_GET["address"]);
		$place->insert();
		redirect("places.php");
	}
}

// Initialize the page
$page = new Page();

$page->add(new Text("Step 1: pick a place"));
$page->newline();

// Construct a table
$places = Place::all();
$table = new Table();
$table->add(array(new Text("Address")));
foreach($places as $place) {
	$place = Place::look_up($place);
	$address = new Link("new_session.php?place=$place->id", $place->address);
	$table->add(array($address));
}
$page->add($table);

$form = new Form();
$input = new Input("button", "new_place");
$input->set("id", "new_place");
$input->set("value", "Add new place");
$form->add($input);

$page->add($form);
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

