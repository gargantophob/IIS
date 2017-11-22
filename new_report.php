<?php

/**
 * @file new_report.php
 * New report creation.
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
restrict_page_access();

function form_process() {
    global $alcoholic, $bac;
    global $error;

    // Collect data
    $alcoholic = sanitize(post_data("alcoholic"));
    $bac = sanitize(post_data("bac"));
    
    // Check alcoholic existence
    $person = Person::look_up($alcoholic);
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

// Extract source, target
$source = Person::look_up(session_data("user"));
$target = null;
if($_SERVER["REQUEST_METHOD"] == "GET") {
    $target = get_data("target");
    if($target != null) {
        $target = Person::look_up($target);
    }
}

// Extract alcoholic, expert
$alcoholic = $target == null ? "" : $target->email;
$expert = $source->role == "expert" ? $source->email : null;
$bac = "";
$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(form_process() === TRUE) {
        // Select alcohol
        $link = "alcohol_selector.php";
        $link .= "?bac=$bac";
        $link .="&target=$alcoholic";
        if($expert != null) {
            $link .="&expert=$expert";
        }
        redirect($link);
    }
}

// Initialize the page
$page = new Page();

// Form
$form = new Form();
$page->add($form);

// Alcoholic
$block = new Block();
$input = new Input("text", "alcoholic", "Alcoholic:");
$input->set("value", $alcoholic);
$block->add($input);
if($source->email == $alcoholic) {
    $block->set("hidden", "true");
}
$form->add($block);

// BAC
$input = new Input("text", "bac", "Blood content:");
$input->set("value", $bac);
$form->add($input);

// Submit
$input = new Input("submit", "submit");
$input->set("value", "Report");
$form->add($input);

// Error message
$form->add_error($error);

// Render the page
$page->render();

?>

