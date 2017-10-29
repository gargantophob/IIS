<?php

/** @file person.php
 * Person class and its subclasses (roles).
 * @author xandri03
 */
require_once "library.php";

/** Person data. */
class Person {
	/** Email, unquoted string. */
	public $email;
	/** Name, unquoted string. */
	public $name;
	/** Password, unquoted string. */
	public $password;
	/** Birthdate, unquoted string in form (yyyy-mm-dd) or null. */
	public $birthdate;
	/** Gender, one of {null, "M", "F"}. */ 
	public $gender;
	/** Picture (raw). */
	public $picture;

	/** Constructor a person. Missing arguments are set to null. */
	public function __construct(
		$email = null, $name = null, $password = null,
		$birthdate = null, $gender = null, $picture = null
	) {
		$this->email = $email;
		$this->name = $name;
		$this->password = $password;
		$this->birthdate = $birthdate;
		$this->gender = $gender;
		$this->picture = $picture;
	}
	
	/** Look person up and fill the attributes.
	 * @param email email of a person that MUST exist in a database.
	 */
	public function look_up($email) {
		$data = db_next(db_select("SELECT * FROM person WHERE email='$email'"));
		$this->email = $email;
		$this->name = $data["name"];
		$this->password = $data["password"];
		$this->birthdate = $data["birthdate"];
		$this->gender = $data["gender"];
		$this->picture = $data["picture"];
	}
	
	/** Insert @c this as a new record in a person table.
	 * @param role person role, one of {"alcoholic", "patron", "expert"}
	 */
	public function insert($role) {
		// Preprocess
		$birthdate = isset($this->birthdate) ? "'$this->birthdate'" : "NULL";
		$gender = isset($this->gender) ? "'$this->gender'" : "NULL";
		$picture = isset($this->picture) ? $this->picture : "NULL";
		
		// Insert person
		db_insert(
			"person",
			array(
				"email"	=>	"'$this->email'",
				"name"	=>	"'$this->name'",
				"password"	=>	"'$this->password'",
				"birthdate"	=>	$birthdate,
				"gender"	=>	$gender,
				"picture"	=>	$picture
			)
		);
		
		// Insert role
		db_insert($role, array("email" => "'$this->email'"));
	}

	/** Update a record in a person table. */
	public function update() {
		// Preprocess
		$birthdate = isset($this->birthdate) ? "'$this->birthdate'" : "NULL";
		$gender = isset($this->gender) ? "'$this->gender'" : "NULL";
		$picture = isset($this->picture) ? $this->picture : "NULL";
		
		// Update table
		db_update("person",
			array(
				"name" => "'$this->name'",
				"password" => "'$this->password'",
				"birthdate" => $birthdate,
				"gender" => $gender,
				"picture" => $picture
			),
			"email='$this->email'"
		);
	}

	/** Extract all members.
	 * @return array of emails (might be empty)
	 */
	public static function all() {
		$people = array();
		$data = db_select("SELECT email FROM person");	
		if($data != null) {
			while($row = db_next($data)) {
				array_push($people, $row["email"]);
			}
		}
		return $people;
	}
	
}

/** Alcoholic data. */
class Alcoholic extends Person {
	/** Constructor. Create empty parent and look up by email. */
	public function __construct($email) {
		Person::__construct();
		$this->look_up($email);
	}
	
	/** Role getter.
	 * @return "alcoholic"
	 */
	public function role() {
		return "alcoholic";
	}
	
	/** List patrons that support this alcoholic.
	 * @return an array of emails (might me empty)
	 */
	public function list_patrons() {
		$patrons = array();
		$data = db_select(
			"SELECT email"
			. " FROM patron_supports JOIN person"
			. " ON patron_supports.patron = person.email"
			. " WHERE patron_supports.alcoholic = '$this->email'"
		);
		if($data != null) {
			while($row = db_next($data)) {
				array_push($patrons, $row["email"]);
			}
		}
		return $patrons;
	}
	
	/** List experts that supervise this alcoholic.
	 * @return an array of emails (might me empty)
	 */
	public function list_experts() {
		$experts = array();
		$data = db_select(
			"SELECT email"
			. " FROM expert_supervises JOIN person"
			. " ON expert_supervises.expert = person.email"
			. " WHERE expert_supervises.alcoholic = '$this->email'"
		);
		if($data != null) {
			while($row = db_next($data)) {
				array_push($experts, $row["email"]);
			}
		}
		return $experts;
	}
	
	/** List all meetings. */
	public function meetings() {
		return Meeting::meetings_of($this->email, $this->role());
	}
}

/** Patron data. */
class Patron extends Person {
	/** Constructor. Create empty parent and look up by email. */
	public function __construct($email) {
		Person::__construct();
		$this->look_up($email);
	}
	
	/** Role getter.
	 * @return "patron"
	 */
	public function role() {
		return "patron";
	}
	
	/** List alcoholics supported by this expert.
	 * @return an array of emails (might me empty)
	 */
	public function list_alcoholics() {
		$alcoholics = array();
		$data = db_select(
			"SELECT email"
			. " FROM patron_supports JOIN person"
			. " ON patron_supports.alcoholic = person.email"
			. " WHERE patron_supports.patron = '$this->email'"
		);
		if($data != null) {
			while($row = db_next($data)) {
				array_push($alcoholics, $row["email"]);
			}
		}
		return $alcoholics;
	}
	
	/** Support an alcoholic.
	 * @param email email of an alcoholic
	 */
	public function support($email) {
		db_insert(
			"patron_supports",
			array("patron" => "'$this->email'", "alcoholic" => "'$email'")
		);
	}
	
	/** Drop alcoholic support.
	 * @param email email of an alcoholic
	 */
	public function drop($email) {
		db_delete(
			"patron_supports",
			"patron='$this->email' AND alcoholic='$email'"
		);
	}
	
	/** List all meetings. */
	public function meetings() {
		return Meeting::meetings_of($this->email, $this->role());
	}
}

/** Expert data. */
class Expert extends Person {
	/** Constructor. Create empty parent and look up by email. */
	public function __construct($email) {
		Person::__construct();
		$this->look_up($email);
	}
	
	/** Role getter.
	 * @return "expert"
	 */
	public function role() {
		return "expert";
	}
	
	/** List alcoholics supervised by this expert.
	 * @return an array of emails (might me empty)
	 */
	public function list_alcoholics() {
		$alcoholics = array();
		$data = db_select(
			"SELECT email"
			. " FROM expert_supervises JOIN person"
			. " ON expert_supervises.alcoholic = person.email"
			. " WHERE expert_supervises.expert = '$this->email'"
		);
		if($data != null) {
			while($row = db_next($data)) {
				array_push($alcoholics, $row["email"]);
			}
		}
		return $alcoholics;
	}
	
	/** Supervise an alcoholic.
	 * @param email email of an alcoholic
	 */
	public function support($email) {
		db_insert(
			"expert_supervises",
			array("expert" => "'$this->email'", "alcoholic" => "'$email'")
		);
	}
	
	/** Drop alcoholic supervising.
	 * @param email email of an alcoholic
	 */
	public function drop($email) {
		db_delete(
			"expert_supervises",
			"expert='$this->email' AND alcoholic='$email'"
		);
	}
}

/** Look person up in a database.
 * @param email email of a person
 * @return object of a specified role (Alcoholic, Patron or Expert) on
 * successful lookup, null otherwise
 */
function person($email) {
	// Find a role
	foreach(array("alcoholic", "patron", "expert") as $role) {
		if(db_select("SELECT * FROM $role WHERE email='$email'") != null) {
			// Role identified, create a specific person
			if($role == "alcoholic") {
				return new Alcoholic($email);
			} elseif($role == "patron") {
				return new Patron($email);
			} else {
				return new Expert($email);
			}
		}
	}
	
	// Such person does not exist
	return null;
}

/** Create a list of people.
 * @param header table header
 * @param people array of emails
 */
function list_of_people($header, $people) {
	$table = new Table(array(new Text($header)));
	foreach($people as $person) {
		// Create a link
		$link = new Link("profile.php?target=$person", person($person)->name);
		// Add a row
		$table->add(array($link));
	}
	return $table;
}

/** Meeting between an alcoholic and a patron. */
class Meeting {
	/** Meeting identifier. */
	public $id;
	/** Alcoholic email (unquoted). */
	public $alcoholic;
	/** Patron email (unquoted). */
	public $patron;
	/** Meeting date. */
	public $date;
	
	/** Construct a meeting. */
	public function __construct($id, $alcoholic, $patron, $date) {
		$this->id = $id;
		$this->alcoholic = $alcoholic;
		$this->patron = $patron;
		$this->date = $date;
	}
	
	/** Register a new meeting in a database. */
	public function insert() {
		db_insert(
			"meeting",
			array(
				// id is auto-incremented
				"alcoholic"	=>	"'$this->alcoholic'",
				"patron"	=>	"'$this->patron'",
				"date"		=>	"'$this->date'"
			)
		);
	}
	
	/** Look meeting up.
	 * @param id meetind identifier
	 * @return an instance of a Meeting class or null on failed search
	 */
	public static function meeting($id) {
		// Look meeting up
		$data = db_select("SELECT * FROM meeting WHERE id=$id");
		if($data == null) {
			// Meeting does not exist
			return null;
		}
		
		// Instantiate
		$data = db_next($data);
		return new Meeting(
			$id, $data["alcoholic"], $data["patron"], $data["date"]
		);
	}
	
	/** Look up all meetings of a person. 
	 * @param email person to look up
	 * @param role role of a person ("alcoholic" or "patron")
	 * @return array of instances of a Meeting class (might be empty)
	 */
	public static function meetings_of($email, $role) {
		$data = db_select("SELECT * FROM meeting WHERE $role='$email'");
		$meetings = array();
		if($data != null) {
			while($row = db_next($data)) {
				array_push($meetings, $row["id"]);
			}
		}
		return $meetings;
	}
}
?>
