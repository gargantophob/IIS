<?php

/** @file library.php
 * Some useful procedures, mainly SQL primitives.
 * @author xandri03
 */
 
/** Restrict page access to authorized users. */
function restrict_page_access() {
	if(empty($_SESSION["user"])) {
		exit("You do not have permission to access this page.");
	}
}

/** Redirect to URL. */
function redirect($url, $permanent = false) {
    if (!headers_sent()) {
        header("Location: " . $url, true, ($permanent === true) ? 301 : 302);
    }
    exit();
}

/** Sanitize form input.
 * @param input input string
 * @return sanitized string
 */
function sanitize($input) {
	return htmlspecialchars(stripslashes(trim($input)));
}

/** Connect to database.
 * @return connection handler on success, exit on failure
 */
function db_connect() {
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
function db_query($query) {
	return db_connect()->query($query);
}

/** Perform SQL SELECT query.
 * @return @c mysqli_result handler on successful search, null otherwise
 */
function db_select($query) {
	$result = db_query($query);
	if($result === FALSE || $result->num_rows == 0) {
		// Failure
		return null;
	}
	// Success
	return $result;
}

/** Fetch next row from a query result.
 * @return next row (an associative array) or null on end of table
 */
function db_next($result) {
	return $result->fetch_assoc();
}

// TODO table join
/** Perform SQL SELECT query.
 * @param table target table
 * @param what target columns
 * @param condition WHERE clause (or null)
 * @return @c mysqli_result handler on successful search, null otherwise
 *
function db_select2($table, $what, $condition = null) {
	$query = "SELECT " . $what . " FROM " . $table;
	if($condition != null) {
		$query .= " WHERE " . $condition;
	}
	$result = db_query($query);
	return $result === FALSE || $result->num_rows == 0 ? null : $result;
}*/

/** Perform SQL insert query.
 * @param table table name
 * @param values associative array of the form (column name => value)
 * @return FALSE on failure, TRUE otherwise
 */
function db_insert($table, $values) {
	$query = "INSERT INTO " . $table . " ( ";
	$columns = "";
	$data = "";
	$first = TRUE;
	foreach($values as $key => $value) {
			if($first === FALSE) {
				$columns .= " , ";
				$data .= " , ";
			}
			$columns .= " $key ";
			$data .= " $value ";
			$first = FALSE;
	}
	
	$query .= $columns . " ) VALUES ( " . $data . " ) ";
	return db_query($query);
}

/** Perform SQL update query.
 * @param table table name
 * @param values associative array in form (column name => new value)
 * @param condition WHERE clause
 * @return FALSE on failure, TRUE otherwise
 */
function db_update($table, $values, $condition) {
	$query = "UPDATE " . $table . " SET ";
	$first = TRUE;
	foreach($values as $key => $value) {
		if($first === FALSE) {
			$query .= " , ";
		}
		$query .= $key . "=" . $value;
		$first = FALSE;
	}
	$query .= " WHERE " . $condition;
	return db_query($query);
}

/** Perform SQL delete query.
 * @param table table to delete from
 * @param condition rows to delete
 * @return FALSE on failure, TRUE otherwise
 */
function db_delete($table, $condition) {
	return db_query("DELETE FROM " . $table . " WHERE " . $condition);
}

?>
