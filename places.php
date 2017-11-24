<?php

/**
 * @file places.php
 * List of places.
 * 
 * Protocol:
 * [G] address  - new place address
 * Authorized access.
 * 
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
authorized_access();

if($_SERVER["REQUEST_METHOD"] == "GET") {
    $address = get_data("address");
    if($address != null) {
		// New address
		$place = new Place(-1, $address);
		$place->insert();
		redirect("places.php");
	}
}

// Initialize the page
$page = new Page();

// Prompt
$page->add(new Text("Pick where to hold session:"));
$page->newline();

// Construct a table
$places = Place::all();
$table = new Table(array(new Text("Address")));
foreach($places as $place) {
	$place = Place::look_up($place);
    $par = array("regime" => "session", "target" => $place->id);
    $link = new Link(plink("date_selector.php", $par), $place->address);
	$table->add(array($link));
}
$page->add($table);

// New place button
$form = new Form();
$page->add($form);

$input = new Input("button", "new_place");
$input->set("id", "new_place");
$input->set("value", "Add new place");
$form->add($input);

// Render the page
$page->render();

?>

<script>
    // New place addition
	var meet = document.getElementById("new_place");
	meet.onclick = function() {
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
				break;
			}
		}
	}
</script>

