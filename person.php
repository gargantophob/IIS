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
	/** Person role, one of {null, "alcoholic", "patron", "expert"}. */
	public $role;
	
	/** Construct a person. Missing arguments are set to null. */
	public function __construct(
		$email = null, $name = null, $password = null, $birthdate = null,
		$gender = null, $picture = null, $role = null
	) {
		$this->email = $email;
		$this->name = $name;
		$this->password = $password;
		$this->birthdate = $birthdate;
		$this->gender = $gender;
		$this->picture = $picture;
		$this->role = $role;
	}
	
	/** Insert @c this as a new record in a person table.
	 */
	public function insert() {
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
		db_insert($this->role, array("email" => "'$this->email'"));
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
	
	/** Look person up and fill the attributes.
	 * @param email email of a person that exists in a database.
	 * @param role person role (optional)
	 */
	public static function look_up($email, $role = null) {
		if($role == null) {
			$found = FALSE;
			foreach(array("alcoholic", "patron", "expert") as $role) {
				if(db_select("SELECT * FROM $role WHERE email='$email'") != null) {
					// Role identified, create a specific person
					$found = TRUE;
					break;
				}
			}
			if($found === FALSE) {
				// Search fail
				return null;
			}
		} else {
			// Check the role
			$date = db_select("SELECT * FROM $role WHERE email='$email'");
			if($date == null) {
				// Search fail
				return null;
			}
		}
		
		// Find a person
		$data = db_select("SELECT * FROM person WHERE email='$email'");
		if($data == null) {
			// Search fail (should not happen here)
			return;
		}
		
		// Create an object
		if($role == "alcoholic") {
			$person = new Alcoholic();
		} elseif($role == "patron") {
			$person = new Patron();
		} else {
			$person = new Expert();
		}
		
		// Fill object attributes
		$data = db_next($data);
		$person->email = $email;
		$person->name = $data["name"];
		$person->password = $data["password"];
		$person->birthdate = $data["birthdate"];
		$person->gender = $data["gender"];
		$person->picture = $data["picture"];
		$person->role = $role;
		
		// Success
		return $person;
	}
}

/** Alcoholic data. */
class Alcoholic extends Person {
	
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
		return Meeting::meetings_of($this->email, $this->role);
	}
	
	/** Meet a patron. */
	public function meet($email, $date) {
		$meeting = new Meeting(-1, $this->email, $email, $date);
		$meeting->insert();
	}
}

/** Patron data. */
class Patron extends Person {
	
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
		return Meeting::meetings_of($this->email, $this->role);
	}
	
	/** Meet an alcoholic. */
	public function meet($email, $date) {
		$meeting = new Meeting(-1, $email, $this->email, $date);
		$meeting->insert();
	}
}

/** Expert data. */
class Expert extends Person {
	
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
	
	/** Look meeting up by identifier.
	 * @return an instance of a Meeting class or null on failed search
	 */
	public static function look_up($id) {
		// Look meeting up
		$data = db_select("SELECT * FROM meeting WHERE id=$id");
		if($data == null) {
			// Meeting does not exist
			return null;
		}
		
		// Instantiate
		$meeting = db_next($data);
		return new Meeting(
			$id, $meeting["alcoholic"], $meeting["patron"], $meeting["date"]
		);
	}
	
	/** Look up all meetings of a person. 
	 * @param email person to look up
	 * @param role role of a person ("alcoholic" or "patron")
	 * @return array of meeting identifiers (might be empty)
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
