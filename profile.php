<?php

/**
 * @file profile.php
 * Profile page.
 * 
 * Protocol:
 * [G] target   - target page email
 * Authorized access.
 * 
 * @author xandri03
 */

require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();
authorized_access();

// Read source (session) and target
$source = Person::look_up(session_data("user"));

// Manual redirections
if($_SERVER["REQUEST_METHOD"] == "GET") {
    $target = get_data("target");
    if($target != null) {
        $target = Person::look_up($target);
    }
    
    if($target == null) {
        $target = $source;
    }
}

// Form handler
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hidden target
    $target = Person::look_up(post_data("target"));

    // Differentiate buttons
    if(post_data("support_start") != null) {
        $source->support($target->email);
    }
    if(post_data("support_stop") != null) {
        $source->drop($target->email);
    }
    if(post_data("meet") != null) {
        // Select the date
        $par = array("regime" => "meeting", "target" => $target->email);
        redirect(plink("date_selector.php", $par));
    }
}

// Preprocess target data
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

// Initialize the page
$page = new Page();

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
$page->add(new Image(plink("image.php", array("target" => $target->email))));
$page->newline();
$page->newline();

// Pick correct possessive pronoun
if($source == $target) {
    $pa = "Your";
} elseif($target->gender == "F") {
    $pa =  "Her";
} else {
    $pa = "His";
}

if($source == $target) {
    // List future sessions
    $sessions = $source->sessions();
    if(count($sessions) == 0) {
        $page->add(new Text("No upcoming sessions: enroll "));
        $page->add(new Link("sessions.php", "here."));
    } else {
        $page->add(new Text("Upcoming sessions:"));
        $table = new Table(
            array(new Text("Date"), new Text("Where"), new Text(""))
        );
        foreach($sessions as $session) {
            $session = Session::look_up($session);
            $date = new Text($session->date);
            $place = new Text(Place::look_up($session->place)->address);
            $link = plink("session.php", array("session" => $session->id));
            $link = new Link($link, "more info...");
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
            $type = $source->role == "alcoholic" ? "patrons" : "alcoholics";
            $link = plink("members.php", array("type" => $type));
            $page->add(new Link($link, "arrange one."));
        } else {
            $page->add(new Text("Upcoming meetings:"));
            $table = new Table(array(new Text("Name"), new Text("Date")));
            foreach($meetings as $meeting) {
                $meeting = Meeting::look_up($meeting);
                if($source->role == "alcoholic") {
                    $person = $meeting->patron;
                } else {
                    $person = $meeting->alcoholic;
                }
                $link = plink("profile.php", array("target" => $person));
                $link = new Link($link, Person::look_up($person)->name);
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
    // Get reports
    $reports = $target->reports();
    
    // Get last report date
    $last = end($reports);
    if($last === FALSE) {
        $without_drink = "?";
    } else {
        $today = new DateTime(today());
        $date = new DateTime(Report::look_up($last)->date);
        $without_drink = date_diff($today, $date)->format('%a');
        if($without_drink == 0) {
            $without_drink = "today.";
        } else {
            $without_drink .= " days ago.";
        }
    }
    $page->add(new Text("Last consumption: $without_drink"));
    $page->newline();
    $page->add(new Text("$pa consumption reports:"));
    $table = new Table(array(
        new Text("Date"), new Text("BAC"), new Text("Reported by")
    ));
    foreach($reports as $report) {
        $report = Report::look_up($report);
        $date = new Text($report->date);
        $bac = new Text($report->bac);
        $reporter = $report->expert;
        if($reporter == null) {
            $reporter = new Text("self-reported");
        } else {
            $reporter = Person::look_up($reporter);
            $link = plink("profile.php", array("target" => $reporter->email));
            $reporter = new Link($link, $reporter->name);
        }
        $table->add(array($date, $bac, $reporter));
    }
    $page->add($table);	
    $page->newline();
}
    
// A bunch of buttons
$form = new Form();

// Hidden target
$input = new Input("text", "target");
$input->set("id", "target");
$input->set("value", $target->email);
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
        } else {
            // Support start button
            $input = new Input("submit", "support_start");
            $input->set("value", "Start supporting");
        }
        $form->add($input);
    }

    // Arrange an appointment between alcoholic and patron button
    $condition = FALSE;
    if($source->role == "alcoholic") {
        $condition = array_search($target->email, $source->patrons()) !== FALSE;
    } elseif($source->role == "patron") {
        $condition = array_search($target->email, $source->alcoholics()) !== FALSE;
    }
    if($condition) {
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
    $link = plink("new_report.php", array("target" => $target->email));
    $page->add(new Link($link, "Report alcohol consumption"));
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
