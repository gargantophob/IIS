<?php

/**
 * @file members.php
 * All members page.
 * 
 * Protocol:
 * [G] type - one of {all, alcoholics, patrons, experts}
 * Authorized access.
 * 
 * @author xsemri00
 * @author xandri03
 */

require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
authorized_access();

// Retrieve context
$source = Person::look_up(session_data("user"));
$type = null;
if($_SERVER["REQUEST_METHOD"] == "GET") {
    $type = get_data("type");
}
if($type == null) {
    $type = "all";
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

$table = new Table(array(new Text("Name"), new Text("Role")));
foreach($emails as $email) {
    $person = Person::look_up($email);
    $link = plink("profile.php", array("target" => $email));
    $name = new Link($link, $person->name);
    $role = new Text($person->role);
    $table->add(array($name, $role));
}
$page->add($table);

// Render the page
$page->render();

?>
