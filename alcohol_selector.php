<?php

/**
 * @file alcohol_selector.php
 * Alcohol selector for reports.
 * 
 * Protocol:
 * [G] bac      - BAC value
 * [G] target   - alcoholic email
 * [G] expert   - reporter email (optional)
 * [G] id       - selected alcohol (optional)
 * Authorized access.
 * 
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
authorized_access();

if($_SERVER["REQUEST_METHOD"] == "GET") {
    $bac = get_data("bac");
    $target = get_data("target");
    $expert = get_data("expert");
    if($bac == null || $target == null) {
        recover();
    }
    
    $id = get_data("id");
    if($id != null) {
        // Create a report
        $report = new Report(-1, today(), $bac, $target, $expert);
        $report->insert();
        redirect(plink("profile.php", array("target" => $target)));
    }
}

// Initialize the page
$page = new Page();

// Prompt
$page->add(new Text("Select consumed alcohol:"));

// List alcohol
$table = new Table(array(new Text("Type"), new Text("Origin"), new Text("")));
foreach(Alcohol::all() as $id) {
    $record = Alcohol::look_up($id);
    $type = new Text($record->type);
    $origin = new Text($record->origin);
    $par = array(
        "bac" => $bac, "target" => $target, "expert" => $expert,
        "id" => $record->id
    );
    $link = new Link(plink("alcohol_selector.php", $par), "select");
    $link->set("class", "button");
    $table->add(array($type, $origin, $link));
}
$page->add($table);

// Render the page
$page->render();

?>

