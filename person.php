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

	/** Enroll a @c session. */
	public function enroll($session) {
		db_insert(
			"person_session",
			array(
				"id"		=> $session,
				"email"		=> "'$this->email'"
			)
		);
	}
	
	/** Unenroll from a @c session. */
	public function unenroll($session) {
		db_delete(
			"person_session",
			"id=$session AND email='$this->email'"
		);
	}
	
	/** Look person up in a database.
	 * @param email email of a person that exists in a database
	 * @param role person role (optional)
	 * @return an object of class Alcoholic, Patron or Expert and null on failed
	 * search.
	 */
	public static function look_up($email, $role = null) {
		if($role == null) {
			$role_found = FALSE;
			foreach(array("alcoholic", "patron", "expert") as $role) {
				if(db_select("SELECT * FROM $role WHERE email='$email'") != null) {
					// Role identified, create a specific person
					$role_found = TRUE;
					break;
				}
			}
			if($role_found === FALSE) {
				// Search fail
				return null;
			}
		} else {
			// Check the role
			if(db_select("SELECT * FROM $role WHERE email='$email'") == null) {
				// Search fail
				return null;
			}
		}
		
		// Find a person
		$data = db_select("SELECT * FROM person WHERE email='$email'");
		if($data == null) {
			// Search fail
			// (should never happen here since each person has a role)
			return null;
		}
		
		// Create an object
		if($role == "alcoholic") {
			$person = new Alcoholic();
		} elseif($role == "patron") {
			$person = new Patron();
		} else {
			$person = new Expert();
		}
		
		// Set object attributes
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
	
	/** Extract all members.
	 * @return array of emails (might be empty)
	 */
	public static function all() {
		return all("person", "email");
	}
}

/** Alcoholic data. */
class Alcoholic extends Person {
	
	/** List patrons that support this alcoholic.
	 * @return an array of emails (might me empty)
	 */
	public function patrons() {
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
	public function experts() {
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
	
	/** List reports. */
	public function reports() {
		$records = array();
		$data = db_select(
			"SELECT id"
			. " FROM alcoholic JOIN report"
			. " ON alcoholic.email = report.alcoholic"
			. " WHERE alcoholic.email = '$this->email'"
		);
		if($data != null) {
			while($row = db_next($data)) {
				array_push($records, $row["id"]);
			}
		}
		return $records;
	}
}

/** Patron data. */
class Patron extends Person {
	
	/** List alcoholics supported by this expert.
	 * @return an array of emails (might me empty)
	 */
	public function alcoholics() {
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
	public function alcoholics() {
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
	
	/** Register a new meeting in a database. */
	public function insert() {
		db_insert("place", array("address" => "'$this->address'"));
	}
	
	/** Look place up by identifier.
	 * @return an instance of a Place class or null on failed search
	 */
	public static function look_up($id) {
		// Look place up
		$data = db_select("SELECT * FROM place WHERE id=$id");
		if($data == null) {
			// Meeting does not exist
			return null;
		}
		
		// Instantiate
		$place = db_next($data);
		return new Place($id, $place["address"]);
	}
	
	/** Look up all places.
	 * @return array of place identifiers (might be empty)
	 */
	public static function all() {
		return all("place", "id");
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
		$res = db_insert(
			"session",
			array(
				"date"		=> "'$this->date'",
				"place"		=> "$this->place",
				"leader"	=> "'$this->leader'"
			)
		);
	}
	
	/** Get session members.
	 * @return array of emails (might be empty)
	 */
	public function members() {
		$members = array();
		$data = db_select(
			"SELECT email FROM person_session WHERE id='$this->id'"
		);
		if($data != null) {
			while($row = db_next($data)) {
				array_push($members, $row["email"]);
			}
		}
		return $members;
	}
	
	/** Look session up by identifier.
	 * @return an instance of a Session class or null on failed search
	 */
	public static function look_up($id) {
		// Look session up
		$data = db_select("SELECT * FROM session WHERE id=$id");
		if($data == null) {
			// Session does not exist
			return null;
		}
		
		// Instantiate
		$session = db_next($data);
		return new Session(
			$id, $session["date"], $session["place"], $session["leader"]
		);
	}
	
	/** Look up all sessions.
	 * @return array of session identifiers (might be empty)
	 */
	public static function all() {
		return all("session", "id");
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
	/** Alcohol id. */
	public $alcohol;
	
	/** Construct a report. */
	public function __construct(
		$id, $date, $bac, $alcoholic, $expert, $alcohol
	) {
		$this->id = $id;
		$this->date = $date;
		$this->bac = $bac;
		$this->alcoholic = $alcoholic;
		$this->expert = $expert;
		$this->alcohol = $alcohol;
	}
	
	/** Register a new report in a database. */
	public function insert() {
		// Preprocess
		$expert = $this->expert;
		if($expert == null) {
			$expert = "NULL";
		}
		
		// Insert
		$res = db_insert(
			"report",
			array(
				"date"		=> "'$this->date'",
				"bac"		=> "$this->bac",
				"alcoholic"	=> "'$this->alcoholic'",
				"expert"	=> "'$this->expert'",
				"alcohol"	=> "$this->alcohol"
			)
		);
	}

	/** Look report up by identifier.
	 * @return an instance of a Report class or null on failed search
	 */
	public static function look_up($id) {
		// Look report up
		$data = db_select("SELECT * FROM report WHERE id=$id");
		if($data == null) {
			// Report does not exist
			return null;
		}
		
		// Instantiate
		$report = db_next($data);
		$expert = $report["expert"];
		if($expert == "NULL") {
			$expert = null;
		}
		return new Report(
			$id, $report["date"], $report["bac"], $report["alcoholic"], 
			$expert, $report["alcohol"]
		);
	}
	
	/** Look up all reports.
	 * @return array of report identifiers (might be empty)
	 */
	public static function all() {
		// TODO?
		return all("report", "id");
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
		// Insert
		return db_insert(
			"alcohol",
			array(
				"type"		=> "'$this->type'",
				"origin"	=> "'$this->origin'"
			)
		);
	}

	/** Look alcohol up by identifier.
	 * @return an instance of an Alcohol class or null on failed search
	 */
	public static function look_up($id) {
		// Look alcohol up
		$data = db_select("SELECT * FROM alcohol WHERE id=$id");
		if($data == null) {
			// Alcohol does not exist
			return null;
		}
		
		// Instantiate
		$alcohol = db_next($data);
		return new Alcohol(
			$id, $alcohol["type"], $alcohol["origin"]
		);
	}
	
	/** Look up all alcohol variants.
	 * @return array of alcohol identifiers (might be empty)
	 */
	public static function all() {
		return all("alcohol", "id");
	}
}

?>
