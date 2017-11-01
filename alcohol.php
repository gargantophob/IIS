<?php

/** @file alcohol.php
 * All alcohol page.
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
$ids = Alcohol::all();
$table = new Table();
$table->add(array(new Text("Identifier"), new Text("Type"), new Text("Origin")));
foreach($ids as $id) {
	$record = Alcohol::look_up($id);
	$table->add(array(new Text($id), new Text($record->type), new Text($record->origin)));
}
$page->add($table);

// Render the page
$page->render();

?>
