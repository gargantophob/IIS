<?php

/** @file members.php
 * All members page.
 * @author xandri03
 */

require_once "library.php";
require_once "person.php";
require_once "html.php";

session_start();
restrict_page_access();

// Initialize the page
$page = new Page();

// Construct a table
$emails = Person::all();
$table = new Table();
$table->add(array(new Text("Person"), new Text("Type")));
foreach($emails as $email) {
	$person = person($email);
	$name = new Link("profile.php?target=$email", $person->name);
	$role = new Text($person->role());
	$table->add(array($name, $role));
}
$page->add($table);

// Render the page
$page->render();

?>
