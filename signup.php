<?php

/** @file signup.php
 * Sign up (profile edit) page.
 * @author xandri03
 */

require_once "library.php";
require_once "person.php";
require_once "html.php";

session_start();

// Initialize the page
$page = new Page();
$email = $name = $password = $birthdate = $gender = $picture = $role = "";
$error = "";
$authorized = FALSE; // session active

if(isset($_SESSION["user"])) {
	// Preset user data
	$authorized = TRUE;
	$email = $_SESSION["user"];
	$person = person($email);
	$name = $person->name;
	$birthdate = $person->birthdate;
	$gender = $person->gender;
	$role = $person->role();
}

/** form to a block, so the element is surrounded by <div> element */
function to_block($element, $classname = null) {
    $block = new Block();
    if ($classname != null) {
        $block->set("class", $classname);
    }
    $block->add($element);
    return $block;
}

/** Form processor. Check all form inputs.
 * @return TRUE on success, FALSE otherwise.
 */
function form_process() {
	global $email, $name, $password, $birthdate, $gender, $picture, $role;
	global $error;
	global $authorized;

	// Collect data
	if(!$authorized) {
		$email = sanitize($_POST["email"]);
		$role = $_POST["role"];
	}
	$name = sanitize($_POST["name"]);
	$password1 = sanitize($_POST["password1"]);
	$password2 = sanitize($_POST["password2"]);
	$birthdate = sanitize($_POST["birthdate"]);
	$gender = empty($_POST["gender"]) ? "" : sanitize($_POST["gender"]);
	$picture = basename($_FILES["file"]["name"]);

	// Check name
	if($name == "") {
		$error = "Name is required.";
		return FALSE;
	}

	// Check user
	if($email == "") {
		$error = "Email is required.";
		return FALSE;
	}

	// Check email format
	if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$error = "Invalid email format.";
		return FALSE;
	}

	if(!$authorized) {
		// Check email uniqueness
		if(person($email) != null) {
			$error = "User with this email already exists.";
			return FALSE;
		}
	}

	// Check passwords
	if($password1 == "") {
		$error = "Password is required.";
		return FALSE;
	}

	// Check password match
	if($password1 != $password2) {
		$error =  "Passwords do not match.";
		return FALSE;
	}

	// Store
	$password = $password1;

	// Check birthdate
	if($birthdate != "") {
		$birthdate = DateTime::createFromFormat("Y-m-d", $birthdate);
		if($birthdate === FALSE) {
			$error = "Wrong date format.";
			return FALSE;
		}
		$birthdate = $birthdate->format("Y-m-d");
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
	// Process form
	if(form_process() === TRUE) {
		// Form success: preprocess data
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
			// Update person
			$person = person($email);
			$person->name = $name;
			$person->password = $password;
			$person->birthdate = $birthdate;
			$person->gender = $gender;
			$person->picture = $picture;
			$person->update();
		} else {
			// Insert person
			$person = new Person(
				$email, $name, $password, $birthdate, $gender, $picture
			);
			$person->insert($role);
		}

		// Redirect
		$_SESSION["user"] = $email;
		redirect("profile.php?target=$email");
	}
}


// Sign up form
$form = new Form();
$form->set("enctype", "multipart/form-data");

// Name
$input = new Input("text", "name", "Name:");
$input->set("maxlength", "128");
$input->set("value", $name);
$form->add($input);

// Email
$input = new Input("text", "email", "Email:");
$input->set("maxlength", "128");
$input->set("value", $email);
if($authorized) {
	$input->set("disabled", "true");
}
$form->add($input);

// Password
$input = new Input("password", "password1", "Password:");
$input->set("maxlength", "128");
$form->add($input);

// Password again
$input = new Input("password", "password2", "Password again:");
$input->set("maxlength", "128");
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

// education field for an expert
// XXX perhaps different field
$block = to_block(new Input("text", "education", "education"), "expert_field");
$form->add($block);

// Role select
$input = new Select("role", "Your role:");
$input->set("id", "select_role");        // ID set
$input->add_option("alcoholic", "Alcoholic");
$input->add_option("patron", "Patron");
$input->add_option("expert", "Expert");
$input->select($role);
if($authorized) {
	$input->set("disabled", "true");
}
$form->add($input);

// Submit
$input = new Input("submit", "submit");
$input->set("value", "Submit");
$form->add($input);

// Error message
$form->add_error($error);

// From created
$page->add($form);

// Render the page
$page->render();
?>

<!-- javascript part -->
<script>
    /** set visibilty of all elements of specific class */
    function setVisible(classname, value) {
        var x = document.getElementsByClassName(classname);
        for (var i = 0; i < x.length; i++) {
            x[i].style.display = value;
        }
    }
    setVisible('expert_field', 'none');

    var role = document.getElementById("select_role");
    /** set callback on element change */
    role.onchange = function() {
        display = "none";
        if (role.value == 'expert') {
            display = "block";
        }
        setVisible('expert_field', display);
    }
</script>
