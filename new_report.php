<?php

/** @file new_report.php
 * New report creation.
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
restrict_page_access();

// Initialize the page
$page = new Page();

// Extract source, target
$source = Person::look_up($_SESSION["user"]);
$target = null;
if($_SERVER["REQUEST_METHOD"] == "GET") {
    if(isset($_GET["target"])) {
        $target = Person::look_up($_GET["target"]);
    }
}

// Extract alcoholic, expert
$alcoholic = $target == null ? "" : $target->email;
$expert = $source->role == "expert" ? $source->email : null;
$bac = $alcohol = "";
$error = "";

function form_process() {
    global $alcoholic, $expert, $bac, $alcohol;
    global $error;

    // Collect data
    $alcoholic = sanitize($_POST["alcoholic"]);
    $bac = sanitize($_POST["bac"]);
    $alcohol = sanitize($_POST["alcohol"]);

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

    // Check alcohol identifier
    if(Alcohol::look_up($alcohol) == null) {
        $error = "Wrong alcohol identifier.";
        return FALSE;
    }

    // Success
    return TRUE;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(form_process() === TRUE) {
        // Create a report
        $report = new Report(-1, date("Y-m-d"), $bac, $alcoholic, $expert);
        $report->insert();
        redirect("profile.php?target=$alcoholic");
    }
}

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

// Alcohol id
$input = new Input("text", "alcohol", "Alcohol identifier:");
$input->set("value", $alcohol);
$form->add($input);

// Submit
$input = new Input("submit", "submit");
$input->set("value", "Report");
$form->add($input);

// Error message
$form->add_error($error);

// Alcohol cheat sheet
$page->add(new Text("Alcohol types:"));
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

