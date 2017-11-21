<?php

/**
 * @file sessions.php
 * List of sessions.
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
restrict_page_access();

// Extract context
$source = Person::look_up($_SESSION["user"]);

// Initialize the page
$page = new Page();

// New session link
$page->add(new Link("places.php", "New session"));
$page->newline();
$page->newline();

// List upcoming sessions
$my_sessions = $source->future_sessions();
if(count($my_sessions) != 0) {
    $page->add(new Text("Upcoming sessions:"));
    $table = new Table(
        array(
            new Text("Date"), new Text("Where"),
            new Text("Leader"), new Text("")
        )
    );
    foreach($my_sessions as $session) {
        $session = Session::look_up($session);
        $date = new Text($session->date);
        $address = new Text(Place::look_up($session->place)->address);
        $leader = Person::look_up($session->leader);
        $leader_link = new Link("profile.php?target=$leader->email", $leader->name);
        $id_link = new Link("session.php?session=$session->id", "more info...");
        $table->add(array($date, $address, $leader_link, $id_link));
    }
    $page->add($table);
}
$page->newline();

// List other upcoming sessions
$page->add(new Text("Other sessions:"));
$sessions = array_diff(Session::all(), $my_sessions);
$table = new Table(
    array(
        new Text("Date"), new Text("Where"), new Text("Leader"), new Text("")
    )
);
foreach($sessions as $session) {
    $session = Session::look_up($session);
    $date = new Text($session->date);
    $address = new Text(Place::look_up($session->place)->address);
    $leader = Person::look_up($session->leader);
    $leader_link = new Link("profile.php?target=$leader->email", $leader->name);
    $id_link = new Link("session.php?session=$session->id", "more info...");
    $table->add(array($date, $address, $leader_link, $id_link));
}
$page->add($table);
$page->newline();

// Render the page
$page->render();

?>
