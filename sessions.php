<?php

/** @file sessions.php
 * List of sessions.
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
restrict_page_access();

// Initialize the page
$page = new Page();

// Construct a table
$sessions = Session::all();
$table = new Table();
$table->add(array(
	new Text("id"), new Text("Address"), new Text("Date"), new Text("Leader")
));
foreach($sessions as $session) {
	$session = Session::look_up($session);
	$id_link = new Link("session.php?session=$session->id", $session->id);
	$address = new Text(Place::look_up($session->place)->address);
	$date = new Text($session->date);
	$leader = Person::look_up($session->leader);
	$leader_link = new Link("profile.php?target=$leader->email", $leader->name);
	$table->add(array($id_link, $address, $date, $leader_link));
}
$page->add($table);

// New session link
$page->add(new Link("places.php", "New session"));
$page->newline();

// Render the page
$page->render();

?>
