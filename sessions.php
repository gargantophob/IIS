<?php

/**
 * @file sessions.php
 * List of sessions.
 *
 * Protocol:
 * Authorized access.
 * 
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
authorized_access();

// Extract context
$source = Person::look_up(session_data("user"));

/*
 * Construct sessions table.
 */
function sessions_table($sessions) {
    // Construct table
    $table = new Table(
        array(
            new Text("Date"), new Text("Where"),
            new Text("Leader"), new Text("")
        )
    );
    
    // Fill table
    foreach($sessions as $session) {
        $session = Session::look_up($session);
        $date = new Text($session->date);
        $address = new Text(Place::look_up($session->place)->address);
        $leader = Person::look_up($session->leader);
        
        $link = plink("profile.php", array("target" => $leader->email));
        $leader_link = new Link($link, $leader->name);
        
        $link = plink("session.php", array("session" => $session->id));
        $more_link = new Link($link, "more info...");
        
        $table->add(array($date, $address, $leader_link, $more_link));
    }
    
    // Success
    return $table;
}

// Initialize the page
$page = new Page();

// New session link
$page->add(new Link("places.php", "New session"));
$page->newline();
$page->newline();

// List user's upcoming sessions
$my_sessions = $source->sessions();
if(count($my_sessions) == 0) {
    $page->add(new Text("You are not enrolled to any upcoming session yet."));
} else {
    $page->add(new Text("Your upcoming sessions:"));
    $page->add(sessions_table($my_sessions));
}
$page->newline();
$page->newline();

// List other upcoming sessions
$other_sessions = array_diff(Session::all_future(), $my_sessions);
if(count($other_sessions) == 0) {
    $page->add(new Text("There are no other upcoming sessions."));
} else {
    $page->add(new Text("Other upcoming sessions:"));
    $page->add(sessions_table($other_sessions));
}
$page->newline();
$page->newline();

// Render the page
$page->render();

?>
