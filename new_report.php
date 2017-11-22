<?php

/**
 * @file new_report.php
 * New report creation.
 * 
 * Protocol:
 * [G] target   - alcoholic email
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
$target = null;
if($_SERVER["REQUEST_METHOD"] == "GET") {
    $target = get_data("target");
}
$bac = $error = "";

/*
 * Form processor.
 */
function form_process() {
    global $target, $bac;
    global $error;

    // Collect data
    $target = sanitize("target");
    $bac = sanitize("bac");
    
    // Check alcoholic existence
    $person = Person::look_up($target);
    if($person == null || $person->role != "alcoholic") {
        $error = "Such alcoholic does not exist.";
        return FALSE;
    }

    // Check blood content
    $value = floatval($bac);
    if($value <= 0 || $value > 1) {
        $error = "Wrong BAC value.";
        return FALSE;
    }
    $bac = $value;

    // Success
    return TRUE;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(form_process() === TRUE) {
        // Identify reporter
        $expert = $source->role == "expert" ? $source->email : null;

        // Select alcohol
        $par = array(
            "bac" => $bac, "target" => $target, "expert" => $expert
        );
        redirect(plink("alcohol_selector.php", $par));
    }
}

// Initialize the page
$page = new Page();

// Form
$form = new Form();
$page->add($form);

// Alcoholic
$block = new Block();
$input = new Input("text", "target", "Alcoholic:");
$input->set("value", $target);
$block->add($input);
if($source->role == "alcoholic") {
    $block->set("hidden", "true");
}
$form->add($block);

// BAC
$input = new Input("text", "bac", "Blood content:");
$input->set("value", $bac);
$form->add($input);

// Submit
$input = new Input("submit", "submit");
$input->set("value", "Continue");
$form->add($input);

// Error message
$form->add_error($error);

// Render the page
$page->render();

?>

