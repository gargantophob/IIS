<?php

/**
 * @file signup.php
 * Sign up (profile edit) page.
 * 
 * Protocol:
 * [S] user     - edit user profile
 * 
 * @author xandri03
 */

require_once "library.php";
require_once "entity.php";
require_once "html.php";

session_start();

$authorized = FALSE; // if true, user have been authenticated
$email = $password = $name = $birthdate = $gender = $picture = $role = "";
$education = $practice = "";
$error = "";

$email = session_data("user");
if($email != null) {
    // Preset user data
    $authorized = TRUE;
    $person = Person::look_up($email);
    $name = $person->name;
    $birthdate = $person->birthdate;
    $gender = $person->gender;
    $role = $person->role;
    if($role == "expert") {
        $education = $person->education;
        $practice = $person->practice;
    }
}

/**
 * Form processor.
 */
function form_process() {
    global $authorized;
    global $email, $password, $name, $birthdate, $gender, $picture, $role;
    global $education, $practice;
    global $error;

    // Collect data
    if(!$authorized) {
        $email = sanitize("email");
        $role = post_data("role");
    }
    $name = sanitize("name");
    $password1 = sanitize("password1");
    $password2 = sanitize("password2");
    $birthdate = sanitize("birthdate");
    $gender = sanitize("gender");
    $picture = basename($_FILES["file"]["name"]);
    $education = sanitize("education");
    $practice = sanitize("practice");

    // Check name
    if($name == "") {
        $error = "Name is required.";
        return FALSE;
    }

    // Check email format
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
        return FALSE;
    }

    if(!$authorized) {
        // Check email uniqueness
        if(Person::look_up($email) != null) {
            $error = "User with this email already exists.";
            return FALSE;
        }
    }

    // Check password length
    if(strlen($password1) < 8) {
        $error = "Password should be at least 8 characters long.";
        return FALSE;
    }

    // Check password match
    if($password1 != $password2) {
        $error =  "Passwords do not match.";
        return FALSE;
    }

    // Store password
    $password = $password1;

    // Check birthdate
    if($birthdate != "") {
        $birthdate = parse_date($birthdate);
        if($birthdate == null) {
            $error = "Wrong date format.";
            return FALSE;
        }
    }

    // Check filename
    if($picture != "") {
        // Check if image file is an actual image
        if(!getimagesize($_FILES["file"]["tmp_name"])) {
            $error = "File is not an image.";
            return FALSE;
        }

        // Check file size
        if ($_FILES["file"]["size"] > 10000000) {
            $error = "File size exceeds allowed limit.";
            return FALSE;
        }

        // Check file extension
        $ext = pathinfo($picture, PATHINFO_EXTENSION);
        if($ext != "jpg" && $ext != "png" && $ext != "jpeg" && $ext != "gif") {
            $error = "Only JPG, JPEG, PNG and GIF files are allowed.";
            return FALSE;
        }

        // Read file contents
        $picture = file_get_contents($_FILES["file"]["tmp_name"]);
        $picture = "'" . addslashes($picture) . "'";
    }

    // Success
    return TRUE;
}

// Form handler
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Differentiate buttons
    if(post_data("delete") != null) {
        // Delete account
        Person::look_up($email)->delete();
        logout();
    }
    
    // Process formt
    if(form_process()) {
        // Preprocess input data
        if($gender == "") {
            $gender = null;
        }
        if($birthdate == "") {
            $birthdate = null;
        }
        if($picture == "") {
            $picture = null;
        }

        if($authorized) {
            // Look up existing record
            $person = Person::look_up($email);
        } else {
            // Construct new record
            if($role == "alcoholic") {
                $person = new Alcoholic();
            } else if($role == "patron") {
                $person = new Patron();
            } else {
                $person = new Expert();
            }
            $person->email = $email;
        }
        
        // Fill data
        $person->password = $password;
        $person->name = $name;
        $person->birthdate = $birthdate;
        $person->gender = $gender;
        $person->picture = $picture;
        if($person->role == "expert") {
            $person->education = $education;
            $person->practice = $practice;
        }
        
        // Update table
        if($authorized) {
            $person->update();
        } else {
            $person->insert();
        }
        
        // Redirect to profile page
        $_SESSION["user"] = $email;
        redirect("profile.php");
    }
}

// Initialize the page
$page = new Page();

// Sign up form
$form = new Form();
$form->set("enctype", "multipart/form-data");
$page->add($form);

// Name
$input = new Input("text", "name", "Name:");
$input->set("maxlength", "64");
$input->set("value", $name);
$input->required();
$form->add($input);

// Email
$input = new Input("text", "email", "Email:");
$input->set("maxlength", "64");
$input->set("value", $email);
$input->required();
if($authorized) {
    $input->set("disabled", "true");
}
$form->add($input);

// Password
$input = new Input("password", "password1", "Password:");
$input->set("maxlength", "64");
$input->required();
$form->add($input);

// Password again
$input = new Input("password", "password2", "Password again:");
$input->set("maxlength", "64");
$input->required();
$form->add($input);

// Birthdate
$input = new Input("text", "birthdate", "Birthdate (yyyy-mm-dd):");
$input->set("value", $birthdate);
$form->add($input);

// Gender: male
$input = new Input("radio", "gender", "Male");
$input->set("value", "M");
if($gender == "M") {
    $input->set("checked", "true");
}
$form->add($input);

// Gender: female
$input = new Input("radio", "gender", "Female");
$input->set("value", "F");
if($gender == "F") {
    $input->set("checked", "true");
}
$form->add($input);

// Profile picture
$input = new Input("file", "file", "Select your profile picture:");
$form->add($input);

// Role select
$input = new Select("role", "Your role:");
$input->set("id", "select_role");
$input->add_option("alcoholic", "Alcoholic");
$input->add_option("patron", "Patron");
$input->add_option("expert", "Expert");
$input->select($role);
if($authorized) {
    $input->set("disabled", "true");
}
$form->add($input);

// Expert: education
$block = new Block();
$block->set("class", "expert_field");
$input = new Input("text", "education", "education");
$input->set("value", $education);
$block->add($input);
$form->add($block);

// Expert: education
$block = new Block();
$block->set("class", "expert_field");
$input = new Input("text", "practice", "practice");
$input->set("value", $practice);
$block->add($input);
$form->add($block);

// Submit
$input = new Input("submit", "submit");
$input->set("value", "Submit");
$input->set("class", "button");
$form->add($input);

// Account deletion button
if($authorized) {
    $input = new Input("submit", "delete");
    $input->set("value", "Delete profile");
    $input->set("class", "button");
    $form->add($input);
}

// Error message
$form->add_error($error);

// Render the page
$page->render();

?>

<script>

    /**
     * Set visibilty of all elements of specific class
     */
    function setVisible(classname, value) {
        var x = document.getElementsByClassName(classname);
        for (var i = 0; i < x.length; i++) {
            x[i].style.display = value;
        }
    }
    
    /**
     * Update visibility of expert fields.
     */
    function updateVisibility() {
        var display = 'none';
        var role = document.getElementById('select_role');
        if(role.value == 'expert') {
            display = 'block';
        }
        setVisible('expert_field', display);
    }

    // Set expert fields visibility on page load and on role change
    updateVisibility();
    var role = document.getElementById('select_role');
    role.onchange = updateVisibility;
    
</script>
