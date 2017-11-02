<?php

/** @file database.php
 * MySQL communication layer.
 * @author xandri03
 */

/** Database table interface. */
class DBTable {
	private $table;
	private $columns;
	private $primary;
	private $auto_increment = FALSE;
	public function __construct($table, $columns) {
		$this->table = $table;
		$this->columns = $columns;
		$this->primary = $columns[0];
	}
	public function auto_increment() {
		$this->auto_increment = TRUE;
	}
	
	public function select($what, $condition = null) {
		return self::db_select(
			"SELECT $what FROM $this->table WHERE $condition;"
		);
	}
	
	public function look_up($key) {
		$data = $this->select("*", "$this->primary=$key");
		return $data != null ? self::db_next($data) : null;
	}
	
	public function insert($values) {
		$columns = "";
		$data = "";
		$first = TRUE;
		for($i = 0; $i < count($this->columns); $i++) {
			if($this->auto_increment === TRUE) {
				if($this->columns[$i] == $this->primary) {
					continue;
				}
			}
			if($first === FALSE) {
				$columns .= " , ";
				$data .= " , ";
			} else {
				$first = FALSE;
			}
			$columns .= $this->columns[$i];
			$data .= $values[$i];
		}
		
		$query = "INSERT INTO $this->table ( $columns ) VALUES ( $data );";
		return self::db_query($query);
	}

	public function update($values, $condition) {
		$query = "UPDATE $this->table SET ";
		$first = TRUE;
		for($i = 0; $i < count($this->columns); $i++) {
			if($first === FALSE) {
				$query .= " , ";
			} else {
				$first = FALSE;
			}
			$query .= $this->columns[$i] . "=" . $values[$i];
		}
		$query .= " WHERE $condition";
		return self::db_query($query);
	}
	
	public function delete($condition) {
		return self::db_query("DELETE FROM " . $this->table . " WHERE " . $condition);
	}
	
	public static function join_select($key, $from, $on, $condition) {
		$data = self::db_select(
			"SELECT $key FROM $from ON $on WHERE $condition"
		);
		$records = array();
		return self::set($records, $key);
	}
	
	public function keyset($key = null) {
		if($key == null) {
			$key = $this->primary;
		}
		if($key == null) {
			return null;
		}
		$set = array();
		$records = self::db_select("SELECT $key FROM $this->table");
		return self::set($records, $key);
	}
	
	public static function set($data, $key) {
		$records = array();
		if($data != null) {
			while($row = self::db_next($data)) {
				array_push($records, $row[$key]);
			}
		}
		return $records;
	}
	
	/** Connect to database.
	 * @return connection handler on success, exit on failure
	 */
	public static function connect() {
		$db = new mysqli(
			"localhost", "xandri03", "afekaj5n", "xandri03", null,
			"/var/run/mysql/mysql.sock"
		);
		if($db->connect_error) {
			exit("Connect Error (" . $db->connect_errno . ") "
				. $db->connect_error
			);
		}
		return $db;
	}
	
	/** Perform SQL query.
	 * @return FALSE on failure, @c mysqli_result for SELECT queries and TRUE
	 * otherwise.
	 */
	public static function db_query($query) {
		return self::connect()->query($query);
	}
	
	
	/** Perform SQL SELECT query.
	 * @return @c mysqli_result handler on successful search, null otherwise
	 */
	public static function db_select($query) {
		$result = self::db_query($query);
		if($result === FALSE || $result->num_rows == 0) {
			return null;
		}
		return $result;
	}

	/** Fetch next row from a SELECT query result.
	 * @return next row (an associative array) or null on end of table
	 */
	public static function db_next($result) {
		return $result->fetch_assoc();
	}
}

/** Database scheme. */
class DB {
	public static $person;
	public static $alcoholic;
	public static $patron;
	public static $expert;
	public static $patron_supports;
	public static $expert_supervises;
	public static $meeting;
	public static $place;
	public static $session;
	public static $person_attends;
	public static $alcohol;
	public static $report;
	public static $alcohol_reported;
}
DB::$person = new DBTable(
	"person",
	array("email", "password", "name", "birthdate", "gender", "picture")
);
DB::$alcoholic = new DBTable(
	"alcoholic", array("email")
);

DB::$patron = new DBTable(
	"patron", array("email")
);

DB::$expert = new DBTable(
	"expert", array("email")
);

DB::$patron_supports = new DBTable(
	"patron_supports", array("patron", "alcoholic")
);

DB::$expert_supervises = new DBTable(
	"expert_supervises", array("expert", "alcoholic")
);

DB::$meeting = new DBTable(
	"meeting", array("id", "patron", "alcoholic", "date")
);
DB::$meeting->auto_increment();

DB::$place = new DBTable(
	"place", array("id", "address")
);
DB::$place->auto_increment();

DB::$session = new DBTable(
	"session", array("id", "date", "place", "leader")
);
DB::$session->auto_increment();

DB::$person_attends = new DBTable(
	"person_attends", array("email", "session")
);

DB::$alcohol = new DBTable(
	"alcohol", array("id", "type", "origin")
);
DB::$alcohol->auto_increment();

DB::$report = new DBTable(
	"report",
	array("id", "date", "bac", "alcoholic", "expert")
);
DB::$report->auto_increment();

DB::$alcohol_reported = new DBTable(
	"alcohol_reported", array("alcohol", "report")
);

?>
