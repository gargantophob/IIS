<?php

/** @file person.php
 * Person class and its subclasses (roles).
 * @author xandri03
 */
require_once "library.php";
require_once "database.php";

/** Person data. */
class Person {
	/** Email, unquoted string. */
	public $email;
	/** Password, unquoted string. */
	public $password;
	/** Name, unquoted string. */
	public $name;
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
		$email = null, $password = null, $name = null, $birthdate = null,
		$gender = null, $picture = null, $role = null
	) {
		$this->email = $email;
		$this->password = $password;
		$this->name = $name;
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
		DB::$person->insert(array(
			"'$this->email'", "'$this->password'", "'$this->name'",
			$birthdate, $gender, $picture
		));
		// Insert role
		$role = $this->role;
		if($role == "alcoholic") {
			$table = DB::$alcoholic;
		} elseif($role == "patron") {
			$table = DB::$patron;
		} else {
			$table = DB::$expert;
		}
		$table->insert(array("'$this->email'"));
	}

	/** Update a record in a person table. */
	public function update() {
		// Preprocess
		$birthdate = isset($this->birthdate) ? "'$this->birthdate'" : "NULL";
		$gender = isset($this->gender) ? "'$this->gender'" : "NULL";
		$picture = isset($this->picture) ? $this->picture : "NULL";
		
		// Update table
		DB::$person->update(
			array(
				"'$this->email'", "'$this->password'", "'$this->name'",
				$birthdate, $gender, $picture
			),
			"email='$this->email'"
		);
	}

	/** Enroll a @c session. */
	public function enroll($session) {
		DB::$person_attends->insert(array("'$this->email'", $session));
	}
	
	/** Unenroll from a @c session. */
	public function unenroll($session) {
		DB::$person_attends->delete(
			"session=$session AND email='$this->email'"
		);
	}
	
	/** Look person up in a database.
	 * @param email email of a person that exists in a database
	 * @return an object of class Alcoholic, Patron or Expert and null on failed
	 * search.
	 */
	public static function look_up($email) {
		// Identify a role
		$found = FALSE;
		foreach(array(DB::$alcoholic, DB::$patron, DB::$expert) as $table) {
			if(array_search($email, $table->keyset()) !== FALSE) {
				$found = TRUE;
				break;
			}
		}
		if($found === FALSE) {
			// Search fail
			return null;
		}
		
		// Find a person
		$data = DB::$person->look_up("'$email'");
		
		// Create an object
		if($table == DB::$alcoholic) {
			$person = new Alcoholic();
			$role = "alcoholic";
		} elseif($table == DB::$patron) {
			$role = "patron";
			$person = new Patron();
		} else {
			$role = "expert";
			$person = new Expert();
		}
		
		// Set object attributes
		$person->email = $email;
		$person->password = $data["password"];
		$person->name = $data["name"];
		$person->birthdate = $data["birthdate"];
		$person->gender = $data["gender"];
		$person->picture = $data["picture"];
		$person->role = $role;
		
		// Success
		return $person;
	}
	
	/** Extract all people.
	 * @return array of emails (might be empty)
	 */
	public static function all() {
		return DB::$person->keyset();
	}
}

/** Alcoholic data. */
class Alcoholic extends Person {
	
	/** List patrons that support this alcoholic.
	 * @return an array of emails (might me empty)
	 */
	public function patrons() {
		return DBTable::join_select(
			"email",
			"patron_supports JOIN person",
			"patron_supports.patron = person.email",
			"patron_supports.alcoholic = '$this->email'"
		);
	}
	
	/** List experts that supervise this alcoholic.
	 * @return an array of emails (might me empty)
	 */
	public function experts() {
		return DBTable::join_select(
			"email",
			"expert_supervises JOIN person",
			"expert_supervises.expert = person.email",
			"expert_supervises.alcoholic = '$this->email'"
		);
	}
	
	/** List all meetings. */
	public function meetings() {
		return Meeting::meetings_of($this->email, $this->role);
	}
	
	/** Meet a patron. */
	public function meet($email, $date) {
		$meeting = new Meeting(-1, $email, $this->email, $date);
		$meeting->insert();
	}
	
	/** List reports. */
	public function reports() {
		return DBTable::join_select(
			"id",
			"alcoholic JOIN report",
			"alcoholic.email = report.alcoholic",
			"alcoholic.email = '$this->email'"
		);
	}
}

/** Patron data. */
class Patron extends Person {
	
	/** List alcoholics supported by this expert.
	 * @return an array of emails (might me empty)
	 */
	public function alcoholics() {
		return DBTable::join_select(
			"email",
			"patron_supports JOIN person",
			"patron_supports.alcoholic = person.email",
			"patron_supports.patron = '$this->email'"
		);
	}
	
	/** Support an alcoholic. */
	public function support($email) {
		DB::$patron_supports->insert(array("'$this->email'", "'$email'"));
	}
	
	/** Drop alcoholic support. */
	public function drop($email) {
		DB::$patron_supports->delete(
			"patron='$this->email' AND alcoholic='$email'"
		);
	}
	
	/** List all meetings. */
	public function meetings() {
		return Meeting::meetings_of($this->email, $this->role);
	}
	
	/** Meet an alcoholic. */
	public function meet($email, $date) {
		$meeting = new Meeting(-1, $this->email, $email, $date);
		$meeting->insert();
	}
}

/** Expert data. */
class Expert extends Person {
	
	/** List alcoholics supervised by this expert.
	 * @return an array of emails (might me empty)
	 */
	public function alcoholics() {
		return DBTable::join_select(
			"email",
			"expert_supervises JOIN person",
			"expert_supervises.alcoholic = person.email",
			"expert_supervises.expert = '$this->email'"
		);
	}
	
	/** Supervise an alcoholic.
	 * @param email email of an alcoholic
	 */
	public function support($email) {
		DB::$expert_supervises->insert(array("'$this->email'", "'$email'"));
	}
	
	/** Drop alcoholic supervising.
	 * @param email email of an alcoholic
	 */
	public function drop($email) {
		DB::$expert_supervises->delete(
			"expert='$this->email' AND alcoholic='$email'"
		);
	}
}

/** Meeting between an alcoholic and a patron. */
class Meeting {
	/** Meeting identifier. */
	public $id;
	/** Patron email (unquoted). */
	public $patron;
	/** Alcoholic email (unquoted). */
	public $alcoholic;
	/** Meeting date. */
	public $date;
	
	/** Construct a meeting. */
	public function __construct($id, $patron, $alcoholic, $date) {
		$this->id = $id;
		$this->patron = $patron;
		$this->alcoholic = $alcoholic;
		$this->date = $date;
	}
	
	/** Register a new meeting in a database. */
	public function insert() {
		DB::$meeting->insert(
			array(-1, "'$this->patron'", "'$this->alcoholic'", "'$this->date'")
		);
	}
	
	/** Look meeting up by identifier.
	 * @return an instance of a Meeting class or null on failed search
	 */
	public static function look_up($id) {
		//$data = db_select("SELECT * FROM meeting WHERE id=$id");
		$record = DB::$meeting->look_up($id);
		if($record == null) {
			return null;
		}
		return new Meeting(
			$id, $record["patron"], $record["alcoholic"], $record["date"]
		);
	}
	
	/** Look up all meetings of a person. 
	 * @param email person to look up
	 * @param role role of a person ("alcoholic" or "patron")
	 * @return array of meeting identifiers (might be empty)
	 */
	public static function meetings_of($email, $role) {
		$data = DB::$meeting->select("*", "$role='$email'");
		return DBTable::set($data, "id");
	}
}

/** Place data. */
class Place {
	/** Place identifier. */
	public $id;
	/** Address. */
	public $address;
	
	/** Construct a place. */
	public function __construct($id, $address) {
		$this->id = $id;
		$this->address = $address;
	}
	
	/** Register a new place in a database. */
	public function insert() {
		DB::$place->insert(array(-1, "'$this->address'"));
	}
	
	/** Look place up by identifier.
	 * @return an instance of a Place class or null on failed search
	 */
	public static function look_up($id) {
		$record = DB::$place->look_up($id);
		if($record == null) {
			return null;
		}
		return new Place($id, $record["address"]);
	}
	
	/** Look up all places.
	 * @return array of place identifiers (might be empty)
	 */
	public static function all() {
		return DB::$place->keyset();
	}
}

/** Session data. */
class Session {
	/** Session identifier. */
	public $id;
	/** Date. */
	public $date;
	/** Place identifier. */
	public $place;
	/** Session leader (email). */
	public $leader;
	
	/** Construct a session. */
	public function __construct($id, $date, $place, $leader) {
		$this->id = $id;
		$this->date = $date;
		$this->place = $place;
		$this->leader = $leader;
	}
	
	/** Register a new session in a database. */
	public function insert() {
		DB::$session->insert(array(
			-1, "'$this->date'", "$this->place", "'$this->leader'"
		));
	}
	
	/** Get session members.
	 * @return array of emails (might be empty)
	 */
	public function members() {
		$data = DB::$person_attends->select("email", "session='$this->id'");
		return DBTable::set($data, "email");
	}
	
	/** Look session up by identifier.
	 * @return an instance of a Session class or null on failed search
	 */
	public static function look_up($id) {
		$record = DB::$session->look_up($id);
		if($record == null) {
			return null;
		}
		return new Session(
			$id, $record["date"], $record["place"], $record["leader"]
		);
	}
	
	/** Look up all sessions.
	 * @return array of session identifiers (might be empty)
	 */
	public static function all() {
		return DB::$session->keyset();
	}
}

/** Report data. */
class Report {
	/** Report identifier. */
	public $id;
	/** Date. */
	public $date;
	/** Blood alcohol content. */
	public $bac;
	/** Alcoholic. */
	public $alcoholic;
	/** Expert (might be NULL). */
	public $expert;
	
	/** Construct a report. */
	public function __construct(
		$id, $date, $bac, $alcoholic, $expert
	) {
		$this->id = $id;
		$this->date = $date;
		$this->bac = $bac;
		$this->alcoholic = $alcoholic;
		$this->expert = $expert;
	}
	
	/** Register a new report in a database. */
	public function insert() {
		// Preprocess
		$expert = $this->expert;
		if($expert == null) {
			$expert = "NULL";
		} else {
			$expert = "'$expert'";
		}

		// Insert
		DB::$report->insert(array(
			-1, "'$this->date'", "$this->bac", "'$this->alcoholic'", $expert
		));
	}

	/** Look report up by identifier.
	 * @return an instance of a Report class or null on failed search
	 */
	public static function look_up($id) {
		$record = DB::$report->look_up($id);
		if($record == null) {
			return null;
		}
		$expert = $record["expert"];
		if($expert == "NULL") {
			$expert = null;
		}
		return new Report(
			$id, $record["date"], $record["bac"], $record["alcoholic"], 
			$expert
		);
	}
}

/** Alcohol data. */
class Alcohol {
	/** Alcohol identifier. */
	public $id;
	/** Alcohol type. */
	public $type;
	/** Country of origin. */
	public $origin;
	
	/** Construct an alcohol. */
	public function __construct($id, $type, $origin) {
		$this->id = $id;
		$this->type = $type;
		$this->origin = $origin;
	}
	
	/** Register a new alcohol in a database. */
	public function insert() {
		DB::$alcohol->insert(array(-1, "'$this->type'", "'$this->origin'"));
	}

	/** Look alcohol up by identifier.
	 * @return an instance of an Alcohol class or null on failed search
	 */
	public static function look_up($id) {
		$record = DB::$alcohol->look_up($id);
		if($record == null) {
			return null;
		}
		return new Alcohol(
			$id, $record["type"], $record["origin"]
		);
	}
	
	/** Look up all alcohol variants.
	 * @return array of alcohol identifiers (might be empty)
	 */
	public static function all() {
		return DB::$alcohol->keyset();
	}
}

?>
