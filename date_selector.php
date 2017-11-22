<?php

/**
 * @file date_selector.php
 * Date selector for meeting or session creation.
 * 
 * Protocol:
 * [G] regime   - one of {meeting, session}
 * [G] target   - either person to meet or a place to held session
 * Authorized access.
 * 
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
authorized_access();


// Extract regime and target identifier
$target = $regime = null;
if($_SERVER["REQUEST_METHOD"] == "GET") {
    $regime = get_data("regime");
    $target = get_data("target");
    
} elseif($_SERVER["REQUEST_METHOD"] == "POST") {
    $regime = post_data("regime");
    $target = post_data("target");
}

// Check
if($regime == null || $target == null) {
    recover();
}

// Selected date
$date = $error = "";

/*
 * Form processor.
 */
function form_process() {
	global $date, $error;
	// Check date format
    $date = parse_date(sanitize("date"));
    if($date == null) {
		$error = "Wrong date format.";
		return FALSE;
	}
	
    // Check date
    if(!is_future($date)) {
        $error = "Please select a future date.";
        return FALSE;
    }
	
	// Success
	return TRUE;
}

// Form handler
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process date
	if(form_process() === TRUE) {
        if($regime == "meeting") {
            // Meet
            Person::look_up(session_data("user"))->meet($target, $date);
            
            // Return to home page
            redirect("profile.php");
        } else {
            // Get sessions keyset
            $sessions_old = Session::all();
            
            // Create new session
            $session = new Session(-1, $date, $target, session_data("user"));
            $session->insert();
            
            // Get new keyset
            $sessions_new = Session::all();
            
            // Extract newly created session
            $diff = array_diff($sessions_new, $sessions_old);
            list($key, $session) = each($diff);
            
            // Enroll leader to this session
            Person::look_up($_SESSION["user"])->enroll($session);
            
            // Redirect to session page
            redirect(plink("session.php", array("session" => $session)));
        }
        
	}
}

$page = new Page();

// Prompt
$page->add(new Text("Pick a date (yyyy-mm-dd):"));
$page->newline();

// Form
$form = new Form();
$page->add($form);

// Hidden regime
$input = new Input("text", "regime");
$input->set("value", $regime);
$input->set("hidden", "true");
$form->add($input);

// Hidden target identifier
$input = new Input("text", "target");
$input->set("value", $target);
$input->set("hidden", "true");
$form->add($input);

// Date input
$input = new Input("text", "date");
$input->set("value", $date);
$form->add($input);

// Submit button
$input = new Input("submit", "submit");
$input->set("value", "Submit");
$form->add($input);

// Error message
$form->add_error($error);

// Render the page
$page->render();

?>

