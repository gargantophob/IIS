<?php

/** @file date_selector.php
 * Date selector for meeting or session creation.
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
restrict_page_access();

// Initialize the page
$target = null;
$regime = "meeting";
$date = $error = "";

function form_process() {
	global $date, $error;
	$date = sanitize($_POST["date"]);
	
	// Check date format
	$d = DateTime::createFromFormat("Y-m-d", $date);
	if($d === FALSE) {
		$error = "Wrong date format.";
		return FALSE;
	}
	$date = $d->format("Y-m-d");
    if(!is_future($date)) {
        $error = "Please select a future date.";
        return FALSE;
    }
	
	// Success
	return TRUE;
}

// Extract regime and target identifier
if($_SERVER["REQUEST_METHOD"] == "GET") {
    if(isset($_GET["regime"])) {
        $regime = $_GET["regime"];
    }
	if(isset($_GET["target"])) {
        $id = $_GET["target"];
        if($regime == "meeting") {
            $target = Person::look_up($id);
        } else {
            $target = Place::look_up($id);
        }
	}
} elseif($_SERVER["REQUEST_METHOD"] == "POST") {
    $regime = $_POST["regime"];
    $id = $_POST["target"];
    if($regime == "meeting") {
        $target = Person::look_up($id);
    } else {
        $target = Place::look_up($id);
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process date
	if(form_process() === TRUE) {
        if($regime == "meeting") {
            // Meet
            $source = Person::look_up($_SESSION["user"]);
            $source->meet($target->email, $date);
            redirect("profile.php?target=$target->email");
        } else {
            // Get sessions keyset
            $sessions_old = Session::all();
            
            // Create new session
            $session = new Session(-1, $date, $target->id, $_SESSION["user"]);
            $session->insert();
            
            // Get new keyset
            $sessions_new = Session::all();
            
            // Extract newly created session
            $diff = array_diff($sessions_new, $sessions_old);
            list($key, $session) = each($diff);
            
            // Enroll leader to this session
            Person::look_up($_SESSION["user"])->enroll($session);
            
            // Redirect to session page
            redirect("session.php?session=$session");
        }
        
	}
}

$page = new Page();

// Prompt
if($regime == "meeting") {
    $text = "Person: $target->email";
} else {
    $text = "Place: $target->address";
}
$page->add(new Text($text));
$page->newline();

$page->add(new Text("Pick a date (yyyy-mm-dd):"));
$page->newline();

// Form
$form = new Form();

// Hidden regime
$input = new Input("text", "regime");
$input->set("value", $regime);
$input->set("hidden", "true");
$form->add($input);

// Hidden target email
$input = new Input("text", "target");
if($regime == "meeting") {
    $value = $target->email;
} else {
    $value = $target->id;
}
$input->set("value", $value);
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

$form->add_error($error);

// Form complete
$page->add($form);

// Render the page
$page->render();

?>

