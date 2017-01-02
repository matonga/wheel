<?php
class Model {
	public static $default_db;
	private $id;
	private $db;
	private $table;
	private $stmt = false;
	private $values_id = false;
	private $values = array ();

	function __construct ($table, $id = null, $db = null) {
		if (!$id) {
			$id = array ('id');
		}
		if (gettype ($id) == 'string') {
			$id = array ($id);
		}
		$this->id = $id;
		$this->db = $db ? $db : Model::$default_db;
		$this->table = preg_match('/\(|\)|,|\.|\sAS\s/', $table) ? $table : ("\"".$table."\"");
	}
	
	private static function bind_values ($stmt, $values, $prefix = '') {
		Log::write ('Bind values: '.json_encode ($values).' (prefix: '.$prefix.')');
		foreach ($values as $name => $value) {
			switch (gettype ($value)) {
			case "integer":
				$stmt->bindValue (':'.$prefix.$name, $value, PDO::PARAM_INT);
				break;
			case "boolean":
				$stmt->bindValue (':'.$prefix.$name, $value, PDO::PARAM_BOOL);
				break;
			case "NULL":
				$stmt->bindValue (':'.$prefix.$name, $value, PDO::PARAM_NULL);
				break;
			default:
				$stmt->bindValue (':'.$prefix.$name, $value, PDO::PARAM_STR);
			}
		}
	}
	
	function begin () {
		$this->db->beginTransaction();
	}
	
	function commit () {
		$this->db->commit ();
	}
	
	function rollback () {
		$this->db->rollBack ();
	}
	
	function insert ($values = false) {
		if (!$values) {
			$values = $this->values;
		} else {
			$this->values = array_merge ($this->values, $values);
		}
		$sql = 'INSERT INTO '.$this->table.' ("'.implode ('", "', array_keys ($values)).'") VALUES (:'.implode (', :', array_keys ($values)).')';
		Log::write ($sql);
		$stmt = $this->db->prepare ($sql);
		Model::bind_values ($stmt, $values);
		$stmt->execute ();
		return $this;
	}
	
	function update ($values = false, $id = false) {
		if (!$values) {
			$values = $this->values;
		} else {
			$this->values = array_merge ($this->values, $values);
		}
		if (!$id) {
			$id = $this->values_id;
		} else {
			$this->values_id = $id;
		}
		$sql = 'UPDATE '.$this->table.' SET '.implode (', ', array_map (function ($value) { return "\"".$value.'" = :set_'.$value; }, array_keys ($values))).' WHERE '.implode(' AND ', array_map (function ($id) { return "\"".$id.'" = :idx_'.$id; }, array_keys ($id)));
		Log::write ($sql);
		$stmt = $this->db->prepare ($sql);
		Model::bind_values ($stmt, $values, 'set_');
		Model::bind_values ($stmt, $id, 'idx_');
		$stmt->execute ();
		return $this;
	}
	
	function delete ($id = false) {
		if (!$id) {
			$id = $this->values_id;
		}
		$sql = 'DELETE FROM '.$this->table.' WHERE '.implode(' AND ', array_map (function ($id) { return "\"".$id.'" = :'.$id; }, array_keys ($id)));
		Log::write ($sql);
		$stmt = $this->db->prepare ($sql);
		Model::bind_values ($stmt, $id);
		$stmt->execute ();
		return $this;
	}

	function __isset ($name) {
		return isset ($this->values[$name]);
	}
	
	function __set ($name, $value) {
		$this->values[$name] = $value;
	}
	
	function __get ($name) {
		if (!isset ($this->values[$name]) && in_array ($name, $this->id)) {
			$this->values[$name] = $this->db->lastInsertId (trim ($this->table, "\"").'_'.$name.'_seq');
		}
		return $this->values[$name];
	}

	function values ($values = false) {
		if (is_array ($values)) {
			$this->values = array_merge ($this->values, $values);
			return $this;
		} else {
			return $this->values;
		}
	}
	
	function select ($arg1 = 'true', $arg2 = array (), $columns = '*') {
		if (is_string ($arg2)) {
			$columns = $arg2;
			$arg2 = array ();
		}
		if (!is_array ($arg1) && preg_match ('/^[0-9]+$/', $arg1)) {
			$arg1 = array ('id' => $arg1);
		}
		if (is_array ($arg1)) {
			$arg2 = $arg1;
			$arg1 = implode(' AND ', array_map (function ($name) { return $name.' = :'.$name; }, array_keys ($arg1)));
		}
		if (!preg_match ('/^(ORDER|LIMIT)/', $arg1)) {
			$arg1 = 'WHERE '.$arg1;
		}
		$sql = 'SELECT '.$columns.' FROM '.$this->table.' '.$arg1;
		Log::write ($sql);
		$this->stmt = $this->db->prepare ($sql);
		Model::bind_values ($this->stmt, $arg2);
		$start_time = gettimeofday (true);
		$this->stmt->execute ();
		$query_time = gettimeofday (true) - $start_time;
		if ($query_time >= 2) {
			Log::write ("Slow query (".round($query_time*1000)."ms): ".$sql, Log::LEVEL_WARNING);
		}
		return $this;
	}
	
	function next () {
		if (($this->values = $this->stmt->fetch (PDO::FETCH_ASSOC))) {
			// ----
			$this->values_id = array();
			foreach ($this->id as $id) {
				if (isset ($this->values[$id])) {
					$this->values_id[$id] = $this->values[$id];
				} else if (isset ($this->values_id[$id])) {
					unset ($this->values_id[$id]);
				}
			}
			// ---- probar este onliner:
			// $this->values_id = array_replace (array_flip ($this->id), $this->values);
			// ----
			return $this;
		} else {
			return false;
		}
	}
	
	function find ($arg1, $arg2 = array (), $columns = '*') {
		$this->select ($arg1, $arg2, $columns);
		return $this->next ();
	}
	
	function all ($key = null, $value = null) {
		$out = array ();
		while ($this->next ()) {
			$row = $this->values ();
			if ($key && $value) {
				$out[$row[$key]] = $row[$value];
			} else if ($key) {
				$out[$row[$key]] = $row;
			} else {
				$out[] = $row;
			}
		}
		return $out;
	}
	/*
	function insert_id ($sequence = false) {
		if (!$sequence) {
			$sequence = $this->table.'_'.$this->id[0].'_seq';
		}
		return $this->db->lastInsertId ($sequence);
	}
	*/
}
