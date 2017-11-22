<?php

/**
 * @file session.php
 * Session info.
 * 
 * Protocol:
 * [G] session  - session identifier
 * Authorized access.
 * 
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
authorized_access();

// Retrieve context
$source = Person::look_up($_SESSION["user"]);
$session = null;
if($_SERVER["REQUEST_METHOD"] == "GET") {
    $session = get_data("session");
    $session = Session::look_up($_GET["session"]);
}
if($session == null) {
    // Invalid session identifier
    redirect("sessions.php");
}

// Form handler
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Extract session
    $session = post_data("session");
    
    // Differentiate buttons
    if(post_data("enroll") != null) {
        $source->enroll($session);
    }
    if(post_data("unenroll") != null) {
        $source->unenroll($session);
    }
}

// Initialize the page
$page = new Page();

// Print session info
$page->add(new Text("Where: " . Place::look_up($session->place)->address));
$page->newline();

$page->add(new Text("Date: " . $session->date));
$page->newline();

$page->add(new Text("Leader: "));
$leader = Person::look_up($session->leader);
$link = plink("profile.php", array("target", $leader->email));
$page->add(new Link($link, $leader->name));
$page->newline();

// Print members
$members = $session->members();
if(count($members) == 0) {
    $page->add(new Text("No members yet."));
} else {
    $table = new Table(array(new Text("Members")));
    foreach($members as $member) {
        $person = Person::look_up($member);
        $link = plink("profile.php", array("target", $person->email));
        $link = new Link($link, $person->name);
        $table->add(array($link));
    }
    $page->add($table);
}

// Enroll/unenroll buttons
$form = new Form();
$page->add($form);

// Hidden session id
$input = new Input("text", "session");
$input->set("value", "$session->id");
$input->set("hidden", "true");
$form->add($input);

// Check membership
if(array_search($source->email, $members) !== FALSE) {
    $name = "unenroll";
    $value = "Unenroll";
} else {
    $name = "enroll";
    $value = "Enroll";
}
$input = new Input("submit", $name);
$input->set("value", $value);
$form->add($input);

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


