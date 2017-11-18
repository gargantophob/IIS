<?php

/**
 * @file members.php
 * All members page.
 * @author xandri03
 */

require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
restrict_page_access();

// Retrieve context
$source = Person::look_up($_SESSION["user"]);

// Handle manual redirections
$type = "all";
if($_SERVER["REQUEST_METHOD"] == "GET") {
    if(isset($_GET["type"])) {
        $type = $_GET["type"];
    }
}

// Initialize the page
$page = new Page();

// Construct a table
if($type == "alcoholics") {
    $emails = $source->alcoholics();
} else if($type == "patrons") {
    $emails = $source->patrons();
} else if($type == "experts") {
    $emails = $source->experts();
} else {
    $emails = Person::all();
}

$table = new Table();
$table->add(array(new Text("Person"), new Text("Role")));
foreach($emails as $email) {
    $person = Person::look_up($email);
	$name = new Link("profile.php?target=$email", $person->name);
	$role = new Text($person->role);
    //$report = new Link("new_report.php?target=$email", "Report");
	$table->add(array($name, $role));
}
$page->add($table);

// Render the page
$page->render();

?>
