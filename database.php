<?php

/**
 * @file database.php
 * MySQL communication encapsulator.
 * @author xandri03
 */

/** Database table interface. */
class DBTable {

    // Private fields: attributes

    /** Table name. */
    private $table;
    /** Array of column names; first item is primary key. */
    private $columns;
    /** If true, primary key will be ommited when inserting. */
    private $auto_increment;

    // Private fields: SQL primitives.

    /**
     * Connect to database.
     * @return connection handler on success, exit on failure
     */
    private static function db_connect() {
        $db = new mysqli(
            "localhost", "xandri03", "afekaj5n", "xandri03", null,
            "/var/run/mysql/mysql.sock"
        );
        if($db->connect_error) {
            exit("Connect Error ($db->connect_errno): $db->connect_error");
        }
        return $db;
    }

    /**
     * Execute SQL query.
     * @param query	SQL query
     * @return      FALSE on failure, @c mysqli_result for SELECT queries and
     *              TRUE otherwise.
     */
    private static function db_query($query) {
        return self::db_connect()->query($query);
    }

    /**
     * Execute SELECT query.
     * @param query SELECT query
     * @return      @c mysqli_result handler on successful search, null
     *              otherwise
     */
    private static function db_select($query) {
        $result = self::db_query($query);
        if($result === FALSE || $result->num_rows == 0) {
            return null;
        }
        return $result;
    }

    /**
     * Fetch next row from a SELECT query result.
     * @param result    instance of @c mysqli_result
     * @return          next row (an associative array) or null on end of table
     */
    private static function db_next($result) {
        return $result->fetch_assoc();
    }

    /**
     * Transform array of table records to a set of keys.
     * @param data  table records, result of {@code db_query()} (even null)
     * @param key   column name to extract
     * @return      set of column values (might be empty)
     */
    private static function keyset($data, $key) {
        $records = array();
        if($data != null) {
            while($row = self::db_next($data)) {
                array_push($records, $row[$key]);
            }
        }
        return $records;
    }

    // Public fields: table primitives.

    /**
     * Construct a table.
     * @param table     table name
     * @param columns   list of column names; first item is the primary key
     */
    public function __construct($table, $columns) {
        $this->table = $table;
        $this->columns = $columns;
        $this->auto_increment = FALSE;
    }

    /**
     * Allow auto-incrementation.
     */
    public function auto_increment() {
        $this->auto_increment = TRUE;
    }

    /**
     * Look up a concrete table record.
     * @param key   primary key value
     * @return      table record (associative array) or null on failed search
     */
    public function look_up($key) {
        $data = $this->db_select(
            "SELECT * FROM $this->table WHERE " . $this->columns[0] . "=" . $key
        );
        if($data == null) {
            return null;
        } else {
            return self::db_next($data);
        }
    }

    /**
     * Perform table insertion.
     * @param values    list of values, order is the same as @c columns; if
     *                  auto-increment was enabled, primary key may be arbitrary
     */
    public function insert($values) {
        $columns = "";
        $data = "";
        $first = TRUE;
        $i = $this->auto_increment ? 1 : 0;
        while($i < count($this->columns)) {
            if($first === TRUE) {
                $first = FALSE;
            } else {
                $columns .= " , ";
                $data .= " , ";
            }
            $columns .= $this->columns[$i];
            $data .= $values[$i];
            $i++;
        }
        $query = "INSERT INTO $this->table ( $columns ) VALUES ( $data ) ;";
        self::db_query($query);
    }

    /**
     * Update table record.
     * @param values    list of values, order is the same as @c columns
     * @param condition predicate over records to update
     */
    public function update($values, $condition) {
        $query = "UPDATE $this->table SET ";
        $first = TRUE;
        for($i = 0; $i < count($this->columns); $i++) {
            if($first === TRUE) {
                $first = FALSE;
            } else {
                $query .= " , ";
            }
            $query .= $this->columns[$i] . "=" . $values[$i];
        }
        $query .= " WHERE $condition ;";
        self::db_query($query);
    }

    /**
     * Delete table records.
     * @param condition predicate over records to delete
     */
    public function delete($condition) {
        $query = "DELETE FROM $this->table WHERE $condition ;";
        self::db_query($query);
    }

    /**
     * Perform joint selection.
     * @param key       one column to select
     * @param from      table join expression
     * @param on        table join condition
     * @param condition select condition
     * @return          array of selected @c key values (might be empty)
     *
    public static function join_select($key, $from, $on, $condition) {
        $query = "SELECT $key FROM $from ON $on WHERE $condition";
        $records = self::db_select($query);
        return self::keyset($records, $key);
    }*/

    /**
     * Return all values of a column (keyset).
     * @param key       column name; if null, primary key is used
     * @param condition predicate over records to select
     * @return          set of keys (might be empty)
     */
    public function select($key = null, $condition = null) {
        if($key == null) {
            $key = $this->columns[0];
        }
        $query = "SELECT $key FROM $this->table ";
        if($condition != null) {
            $query .= " WHERE $condition";
        }
        $records = self::db_select($query);
        return self::keyset($records, $key);
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
    "alcoholic",
    array("email")
);

DB::$patron = new DBTable(
    "patron",
    array("email")
);

DB::$expert = new DBTable(
    "expert",
    array("email", "education", "practice")
);

DB::$patron_supports = new DBTable(
    "patron_supports",
    array("patron", "alcoholic")
);

DB::$expert_supervises = new DBTable(
    "expert_supervises",
    array("expert", "alcoholic")
);

DB::$meeting = new DBTable(
    "meeting",
    array("id", "patron", "alcoholic", "date")
);
DB::$meeting->auto_increment();

DB::$place = new DBTable(
    "place",
    array("id", "address")
);
DB::$place->auto_increment();

DB::$session = new DBTable(
    "session",
    array("id", "date", "place", "leader")
);
DB::$session->auto_increment();

DB::$person_attends = new DBTable(
    "person_attends",
    array("email", "session")
);

DB::$alcohol = new DBTable(
    "alcohol",
    array("id", "type", "origin")
);
DB::$alcohol->auto_increment();

DB::$report = new DBTable(
    "report",
    array("id", "date", "bac", "alcoholic", "expert")
);
DB::$report->auto_increment();

DB::$alcohol_reported = new DBTable(
    "alcohol_reported",
    array("alcohol", "report")
);

?>
