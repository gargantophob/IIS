<?php

/**
 * @file profile.php
 * Profile page.
 * @author xandri03
 */

require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
restrict_page_access();

// Read source (session) and target
$source = Person::look_up($_SESSION["user"]);
$target = $source;  // default target is the source

// Manual redirections
if($_SERVER["REQUEST_METHOD"] == "GET") {
    if(isset($_GET["target"])) {
        $target = Person::look_up($_GET["target"]);
    }
    if(isset($_GET["date"])) {
        // Meet the target
        
    }
}

// Form handler
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Extract hidden target
    $target = Person::look_up($_POST["target"]);

    // Differentiate buttons
    if(isset($_POST["support_start"])) {
        $source->support($target->email);
    }
    if(isset($_POST["support_stop"])) {
        $source->drop($target->email);
    }
    if(isset($_POST["meet"])) {
        redirect("date_selector.php?regime=meeting&target=$target->email");
    }
}

// Initialize the page
$page = new Page();

// Preprocess some target data
$birthdate = $target->birthdate;
if($birthdate == null) {
    $birthdate = "?";
}
$gender = $target->gender;
if($gender == null) {
    $gender = "?";
} else {
    $gender = $gender == "M" ? "Male" : "Female";
}

// Print general data
$page->add(new Text("Name: " . $target->name));
$page->newline();
$page->add(new Text("Email: " . $target->email));
$page->newline();
$page->add(new Text("Birthdate: " . $birthdate));
$page->newline();
$page->add(new Text("Gender: " . $gender));
$page->newline();
$page->add(new Text("Role: " . $target->role));
$page->newline();
$page->add(new Image("image.php?user=$target->email"));
$page->newline();
$page->newline();

// Pick a correct possessive adjective
if($source == $target) {
    $pa = "Your";
} elseif($target->gender == "F") {
    $pa =  "Her";
} else {
    $pa = "His";
}

if($source == $target) {
    // List future sessions
    $sessions = $source->future_sessions();
    if(count($sessions) == 0) {
        $page->add(new Text("No upcoming sessions: "));
        $page->add(new Link("sessions.php", "find one."));
    } else {
        $page->add(new Text("Upcoming sessions:"));
        $table = new Table(
            array(new Text("Date"), new Text("Where"), new Text(""))
        );
        foreach($sessions as $session) {
            $session = Session::look_up($session);
            $date = new Text($session->date);
            $place = new Text(Place::look_up($session->place)->address);
            $link = new Link(
                "session.php?session=$session->id", "more info..."
            );
            $table->add(array($date, $place, $link));
        }
        $page->add($table);
    }
    $page->newline();
    $page->newline();
        
    // List future meetings
    if($source->role != "expert") {
        $meetings = $source->meetings();
        if(count($meetings) == 0) {
            $page->add(new Text("No upcoming meetings: "));
            $page->add(new Link("members.php?type=patrons", "suggest one."));
        } else {
            $page->add(new Text("Upcoming meetings:"));
            $table = new Table(array(new Text("Patron"), new Text("Date")));
            foreach($meetings as $meeting) {
                $meeting = Meeting::look_up($meeting);
                if(!is_future($meeting->date)) {
                    continue;
                }
                if($source->role == "alcoholic") {
                    $person = $meeting->patron;
                } else {
                    $person = $meeting->alcoholic;
                }
                $link = new Link(
                    "profile.php?target=$person", Person::look_up($person)->name
                );
                $date = new Text($meeting->date);
                $table->add(array($link, $date));
            }
            $page->add($table);
        }
        $page->newline();
        $page->newline();
    }
}

// List reports and statistics
if($target->role == "alcoholic") {
    $reports = $target->reports();
    $page->add(new Text("$pa reports and statistics:"));
    $table = new Table(array(
        new Text("Date"), new Text("BAC"), new Text("Reported by")
    ));
    foreach($reports as $report) {
        $report = Report::look_up($report);
        $date = new Text($report->date);
        $bac = new Text($report->bac);
        $reporter = $report->expert;
        if($reporter == null) {
            $reporter = new Text("Self-reported");
        } else {
            $reporter = Person::look_up($reporter);
            $reporter = new Link(
                "profile.php?target=$reporter->email", $reporter->name
            );
        }
        $table->add(array(
            $date, $bac, $reporter
        ));
    }
    $page->add($table);	
    $page->newline();
}
    
// A bunch of buttons
$form = new Form();

// Hidden target email parameter for POST transitions
$input = new Input("text", "target");
$input->set("id", "target");
$input->set("value", $target->email);
$input->set("hidden", "true");
$form->add($input);

// Hidden target name parameter for JS
$input = new Input("text", "name");
$input->set("id", "name");
$input->set("value", $target->name);
$input->set("hidden", "true");
$form->add($input);

// Buttons
if($target != $source) {
    // Support start/drop buttons
    if($source->role != "alcoholic" && $target->role == "alcoholic") {
        if(array_search($target->email, $source->alcoholics()) !== FALSE) {
            // Support stop button
            $input = new Input("submit", "support_stop");
            $input->set("value", "Stop supporting");
            $form->add($input);
        } else {
            // Support start button
            $input = new Input("submit", "support_start");
            $input->set("value", "Start supporting");
            $form->add($input);
        }
    }

    // Create an appointment button
    if(
        (
            $source->role == "alcoholic"
            && array_search($target->email, $source->patrons()) !== FALSE
        ) || (
            $source->role == "patron"
            && array_search($target->email, $source->alcoholics()) !== FALSE
        )
    ) {
        $input = new Input("submit", "meet");
        $input->set("value", "Meet");
        $form->add($input);
    }
}
$page->add($form);

// Report button
if(
    ($source == $target && $source->role == "alcoholic")
    || ($source->role == "expert" && $target->role == "alcoholic")
) {
    $page->add(
        new Link(
            "new_report.php?target=$target->email", "Report alcohol consumption"
        )
    );
    $page->newline();
}

// XXX
$element = new Text("");
$element->set("id", "timer");
$page->add($element);

// Render the page
$page->render();

?>

<script>

// Automatic logout
var seconds = 10;
var prompt = "automatic logout after: ";
document.getElementById("timer").textContent = prompt + seconds;
setInterval(
    function() {
        seconds--;
        if(seconds == 0) {
            window.location.replace("index.php?logout=yes");
        }
        document.getElementById("timer").textContent = prompt + seconds;
    },
    1000
);
</script>
