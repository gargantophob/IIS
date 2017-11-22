<?php

/**
 * @file alcohol_selector.php
 * Alcohol selector for reports.
 * @author xandri03
 */
 
require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
restrict_page_access();

if($_SERVER["REQUEST_METHOD"] == "GET") {
    $bac = get_data("bac");
    $target = get_data("target");
    $expert = get_data("expert");
    $id = get_data("id");
    if($id != null) {
        // Create a report
        $report = new Report(-1, today(), $bac, $target, $expert);
        $report->insert();
        redirect("profile.php?target=$target");
    }
}

// Initialize the page
$page = new Page();

// Prompt
$page->add(new Text("Select consumed alcohol:"));

// List alcohol
$table = new Table();
$table->add(array(new Text("Type"), new Text("Origin"), new Text("")));
foreach(Alcohol::all() as $id) {
    $record = Alcohol::look_up($id);
    $type = $record->type;
    $origin = $record->origin;
    $link = "alcohol_selector.php";
    $link .= "?bac=$bac";
    $link .="&target=$target";
    $link .="&expert=$expert";
    $link .= "&id=$record->id";
    $table->add(array(new Text($type), new Text($origin), new Link($link, "select")));
}
$page->add($table);

// Render the page
$page->render();

?>

