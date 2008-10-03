<?php

require_once php_file('database', CONFIG_DIR);

## TODO: Handle data types and proper quoting of strings (but not numeric fields).
## TODO: Use PDO instead of MySQL calls. (Perhaps keep MySQL calls for old PHP 4 sites.)
## TODO: Use variable binding, instead of plugging data fields directly into SQL statement.
## TODO: Error handling.
## TODO: load_row and find_rows should probably have similar names.
## TODO: Change names of plural versions to *_where?
## TODO: Change names to SELECT, since we already have INSERT/UPDATE/DELETE.
## TODO: Add a more generic SELECTs, allowing joins, limits, ORDER BY, etc.
## TODO: Add a "raw" SELECT, allowing the SQL to be manually specified. Can use AS, SQL functions, etc.


## NOTE: This is primarily a generic Table Data Gateway implementation.


class DBTable
{
	# Name of the table.
	protected $name;

	# Connection to the database.
	protected $conn;

	# Info about each column.
	protected $column_info = array();

	public function __construct($name)
	{
		$this->name = $name;
		$this->connect();
		$this->get_column_info();
	}
	public function __destruct()
	{
		if ($this->conn) mysql_close($this->conn);
	}


	# Return an array of info for each column, including field name, type, constraints, etc.
	public function column_info()
	{
		return $this->column_info;
	}

	public function load_row($id)
	{
		$result = mysql_query("SELECT * FROM {$this->name} WHERE id='$id' LIMIT 1", $this->conn);
		if ( !$result || 0 == mysql_num_rows($result) )
			# TODO: Report on mysql_error();
			return null;
		return mysql_fetch_assoc($result);
	}

	public function find_rows($where)
	{
		$result = mysql_query("SELECT * FROM {$this->name} WHERE $where", $this->conn);
		if ( 0 == mysql_num_rows($result) )
			return null;
		return mysql_fetch_array($result);
	}

	public function count_rows($where)
	{
		$this->connect();
		$result = mysql_query("SELECT COUNT(*) FROM {$this->name} WHERE $where", $this->conn);
		return mysql_num_rows($result);
	}

	public function insert_row($data)
	{
		$keys = join(', ', array_keys($data));
		$values = join(', ', array_values($data));
		$sql = "INSERT INTO {$this->name} ($keys) VALUES ($values)";
		$result = mysql_query($sql, $this->conn);
		# TODO: Report on mysql_error();
		# Return ID of new record.
		return mysql_insert_id($this->conn);
	}

	public function update_row($id, $data)
	{
		return update_rows("WHERE id='$id'", $data);
		# TODO: Make sure 1 record was updated?
	}

	public function update_rows($where, $data)
	{
		$data_set = array();
		foreach ( $data as $var => $val )
		{
			# Need to escape $val, to ensure against SQL injections.
			$val = mysql_real_escape_string($val, $this->conn);

			# Add the item to the result.
			$data_set[] = "$var = '$val'";
		}
		$data = join(', ', $data_set);
		$sql = "UPDATE {$this->name} SET $data WHERE $where";
		$result = mysql_query($sql, $this->conn);
		# TODO: Report on mysql_error();
		# TODO: Return number of records updated?
		return $this;
	}

	public function delete_row($id)
	{
		$result = $this->delete_rows("WHERE id='$id'");
	}

	public function delete_rows($where)
	{
		$result = mysql_query("DELETE FROM {$this->name} WHERE $where", $this->conn);
		# TODO: Report on mysql_error();
		# TODO: Throw an error if 1 != mysql_num_rows($result)?
	}

	private function connect()
	{
		global $DATABASE;
		$this->conn = mysql_connect($DATABASE['host'], $DATABASE['user_name'], $DATABASE['password']);
		mysql_select_db($DATABASE['database'], $this->conn);
    }

	private function get_column_info()
	{
		$result = mysql_query("SHOW COLUMNS FROM {$this->name}", $this->conn);
		if ( !$result || 0 == mysql_num_rows($result) )
			return null; # TODO: Error handling.
		while ( $row = mysql_fetch_assoc($result) )
		{
			# Store information.
			$this->column_info[$row['Field']] = $row;
		}
	}
}
