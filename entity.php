<?php

/**
 * @file entity.php
 * All database entities.
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
    /** Gender, one of {"M", "F", null}. */ 
    public $gender;
    /** Picture (raw). */
    public $picture;
    /** Person role, one of {"alcoholic", "patron", "expert", null}. */
    public $role = null;

    /**
     * Replace data of this person with the ones from a database.
     * @param email email of a person
     */
    public function fill($email) {
        // Look email up
        $data = DB::$person->look_up("'$email'");
        if($data == null) {
            return;
        }
        
        // Set object attributes
        $this->email = $email;
        $this->password = $data["password"];
        $this->name = $data["name"];
        $this->birthdate = $data["birthdate"];
        $this->gender = $data["gender"];
        $this->picture = $data["picture"];
        // role field is ignored
    }
    
    /**
     * Insert this person as a new record in a person table.
     */
    protected function insert() {
        // Preprocess data
        $birthdate = isset($this->birthdate) ? "'$this->birthdate'" : "NULL";
        $gender = isset($this->gender) ? "'$this->gender'" : "NULL";
        $picture = isset($this->picture) ? $this->picture : "NULL";
        
        // Insert person
        DB::$person->insert(
            array(
                "'$this->email'", "'$this->password'", "'$this->name'",
                $birthdate, $gender, $picture
            )
        );
    }

    /**
     * Update a record in a person table.
     */
    public function update() {
        // Preprocess data
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
    
    /**
     * Delete person record.
     */
    public function delete() {
        DB::$person->delete("email='$this->email'");
    }
    
    /**
     * List future sessions starting from the earliest one.
     */
    public function future_sessions() {
        return Session::future_sessions_of($this->email);
    }
    
    /**
     * Enroll a session.
     */
    public function enroll($session) {
        DB::$person_attends->insert(array("'$this->email'", $session));
    }

    /**
     * Unenroll from a session.
     */
    public function unenroll($session) {
        DB::$person_attends->delete(
            "email='$this->email' AND session=$session"
        );
    }

    /**
     * Look person up in a database.
     * @param email email of a person that exists in a database
     * @return      an object of class Alcoholic, Patron or Expert and null on
     *              failed search.
     */
    public static function look_up($email) {
        // Identify a role
        $found = FALSE;
        foreach(array(DB::$alcoholic, DB::$patron, DB::$expert) as $table) {
            if(array_search($email, $table->select()) !== FALSE) {
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
        } elseif($table == DB::$patron) {
            $person = new Patron();
        } else {
            $person = new Expert();
            $expert_data = DB::$expert->look_up("'$email'");
            $person->education = $expert_data["education"];
            $person->practice = $expert_data["practice"];
        }
        $person->fill($email);
        
        // Success
        return $person;
    }

    /**
     * Extract all people.
     * @return  array of emails (might be empty)
     */
    public static function all() {
        return DB::$person->select();
    }
}

/** Alcoholic data. */
class Alcoholic extends Person {

    /**
     * Construct an alcoholic.
     */
    public function Alcoholic() {
        $this->role = "alcoholic";
    }
    
    /**
     * Insert this alcoholic as a new record in a table.
     */
    public function insert() {
        Person::insert();
        DB::$alcoholic->insert(array("'$this->email'"));
    }

    /**
     * List patrons that support this alcoholic.
     * @return  an array of emails (might me empty)
     */
    public function patrons() {
        return DB::$patron_supports->select(
            "patron", "alcoholic = '$this->email'"
        );
    }

    /**
     * List experts that supervise this alcoholic.
     * @return  an array of emails (might me empty)
     */
    public function experts() {
        return DB::$expert_supervises->select(
            "expert", "alcoholic = '$this->email'"
        );
    }

    /**
     * List all meetings of this alcoholic.
     */
    public function meetings() {
        return Meeting::meetings_of($this->email, $this->role);
    }

    /**
     * Meet a patron.
     */
    public function meet($email, $date) {
        $meeting = new Meeting(-1, $email, $this->email, $date);
        $meeting->insert();
    }

    /**
     * List alcohol consumption reports.
     */
    public function reports() {
        return DB::$report->select("id", "alcoholic = '$this->email'");
    }
}

/** Patron data. */
class Patron extends Person {
    
    /**
     * Construct a patron.
     */
    public function Patron() {
        $this->role = "patron";
    }
    
    /**
     * Insert this alcoholic as a new record in a table.
     */
    public function insert() {
        Person::insert();
        DB::$patron->insert(array("'$this->email'"));
    }

    /**
     * List alcoholics supported by this expert.
     * @return  an array of emails (might me empty)
     */
    public function alcoholics() {
        return DB::$patron_supports->select(
            "alcoholic", "patron = '$this->email'"
        );
    }

    /**
     * Support an alcoholic.
     */
    public function support($email) {
        DB::$patron_supports->insert(array("'$this->email'", "'$email'"));
    }

    /**
     * Drop alcoholic support.
     */
    public function drop($email) {
        DB::$patron_supports->delete(
            "patron='$this->email' AND alcoholic='$email'"
        );
    }

    /**
     * List all meetings.
     */
    public function meetings() {
        return Meeting::meetings_of($this->email, $this->role);
    }

    /**
     * Meet an alcoholic.
     */
    public function meet($email, $date) {
        $meeting = new Meeting(-1, $this->email, $email, $date);
        $meeting->insert();
    }
}

/** Expert data. */
class Expert extends Person {
    
    /** Expert education. */
    public $education;
    /** Expert practice. */
    public $practice;
    
    /**
     * Construct an expert.
     */
    public function Expert() {
        $this->role = "expert";
    }
    
    /**
     * Insert this alcoholic as a new record in a table.
     */
    public function insert() {
        Person::insert();
        DB::$expert->insert(
            array("'$this->email'", "'$this->education'", "'$this->practice'")
        );
    }

    /**
     * Update expert and person fields in a database.
     */
    public function update() {
        Person::update();
        DB::$expert->update(
            array(
                "'$this->email'", "'$this->education'", "'$this->practice'",
            ),
            "email='$this->email'"
        );
    }

    /**
     * List alcoholics supervised by this expert.
     * @return  an array of emails (might me empty)
     */
    public function alcoholics() {
        return DB::$expert_supervises->select(
            "alcoholic", "expert = '$this->email'"
        );
    }

    /**
     * Supervise an alcoholic.
     * @param email email of an alcoholic
     */
    public function support($email) {
        DB::$expert_supervises->insert(array("'$this->email'", "'$email'"));
    }

    /**
     * Drop alcoholic supervising.
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

    /**
     * Construct a meeting.
     */
    public function __construct($id, $patron, $alcoholic, $date) {
        $this->id = $id;
        $this->patron = $patron;
        $this->alcoholic = $alcoholic;
        $this->date = $date;
    }

    /**
     * Register a new meeting in a database.
     */
    public function insert() {
        DB::$meeting->insert(
            array(-1, "'$this->patron'", "'$this->alcoholic'", "'$this->date'")
        );
    }

    /**
     * Look meeting up by identifier.
     * @return  an instance of a Meeting class or null on failed search
     */
    public static function look_up($id) {
        $record = DB::$meeting->look_up($id);
        if($record == null) {
            return null;
        }
        return new Meeting(
            $id, $record["patron"], $record["alcoholic"], $record["date"]
        );
    }

    /**
     * Look up all meetings of a person. 
     * @param email person to look up
     * @param role  role of a person ("alcoholic" or "patron")
     * @return      array of meeting identifiers (might be empty)
     */
    public static function meetings_of($email, $role) {
        return DB::$meeting->select("id", "$role='$email'");
    }
}

/** Place data. */
class Place {
    /** Place identifier. */
    public $id;
    /** Address. */
    public $address;

    /**
     * Construct a place. 
     */
    public function __construct($id, $address) {
        $this->id = $id;
        $this->address = $address;
    }

    /**
     * Register a new place in a database.
     */
    public function insert() {
        DB::$place->insert(array(-1, "'$this->address'"));
    }

    /**
     * Look place up by identifier.
     * @return  an instance of a Place class or null on failed search
     */
    public static function look_up($id) {
        $record = DB::$place->look_up($id);
        if($record == null) {
            return null;
        }
        return new Place($id, $record["address"]);
    }

    /**
     * Look up all places.
     * @return  array of place identifiers (might be empty)
     */
    public static function all() {
        return DB::$place->select();
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

    /**
     * Construct a session.
     */
    public function __construct($id, $date, $place, $leader) {
        $this->id = $id;
        $this->date = $date;
        $this->place = $place;
        $this->leader = $leader;
    }

    /**
     * Register a new session in a database.
     */
    public function insert() {
        DB::$session->insert(
            array(-1, "'$this->date'", "$this->place", "'$this->leader'")
        );
    }

    /**
     * Get session members.
     * @return array of emails (might be empty)
     */
    public function members() {
        return DB::$person_attends->select("email", "session='$this->id'");
    }

    /**
     * Look session up by identifier.
     * @return  an instance of a Session class or null on failed search
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

    /**
     * Look up all sessions.
     * @return  an array of session identifiers (might be empty)
     */
    public static function all() {
        return DB::$session->select();
    }
    
    /**
     * Look up all sessions of a person. 
     * @param email person to look up
     * @return      array of session identifiers (might be empty)
     */
    public static function sessions_of($email) {
        return DB::$person_attends->select("session", "email = '$email'");
    }
    
    /**
     * Look up all future sessions of a person.
     * @param email person to look up
     * @return      sorted array of upcoming session identifiers
     *              (might be empty)
     */
    public static function future_sessions_of($email) {
        // Find all sessions
        $sessions = self::sessions_of($email);
        
        // Filter only future ones
        $unsorted = array();
        foreach($sessions as $session) {
            $date = self::look_up($session)->date;
            if(is_future($date)) {
                $unsorted[$session] = strtotime($date);
            }
        }
        
        
        // Sort
        asort($unsorted);
        
        // Extract keys
        $sorted = array();
        foreach($unsorted as $key => $value) {
            array_push($sorted, $key);
        }

        // Success
        return $sorted;
        
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
	
	/**
	 * Construct a report.
	 */
	public function __construct(
		$id, $date, $bac, $alcoholic, $expert
	) {
		$this->id = $id;
		$this->date = $date;
		$this->bac = $bac;
		$this->alcoholic = $alcoholic;
		$this->expert = $expert;
	}
	
	/**
	 * Register a new report in a database.
	 */
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

	/**
	 * Look report up by identifier.
	 * @return  an instance of a Report class or null on failed search
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

    /**
     * Construct alcohol variant.
     */
    public function __construct($id, $type, $origin) {
        $this->id = $id;
        $this->type = $type;
        $this->origin = $origin;
    }

    /**
     * Register a new alcohol in a database.
     */
    public function insert() {
        DB::$alcohol->insert(array(-1, "'$this->type'", "'$this->origin'"));
    }

    /**
     * Look alcohol up by identifier.
     * @return  an instance of an Alcohol class or null on failed search
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

    /**
     * Look up all alcohol variants.
     * @return an array of alcohol identifiers (might be empty)
     */
    public static function all() {
        return DB::$alcohol->select();
    }
}

?>
