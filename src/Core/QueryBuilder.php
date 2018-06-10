<?php 

namespace App\Core;

use PDO;

abstract class QueryBuilder {
	public $pdo = null;

	protected $from = null;
	protected $select = '*';
	protected $where = null;
	protected $op = ['=', "!=", '<', '>', "<=", ">=", "<>"];

	public function __construct(Array $config) {
		$config["driver"]    = (isset($config["driver"]) ? $config["driver"] : "mysql");
    	$config["host"]      = (isset($config["host"]) ? $config["host"] : "localhost");

    	$dsn = '';

    	if ($config['driver'] == 'mysql' || $config['driver'] == '' || $config['driver'] == 'pgsql') {
    		$dsn = $config['driver'] . ':host=' . $config['host'] . ';'
    			   . 'dbname=' . $config['database']; 
    	}

    	try {
    		$this->pdo = new PDO($dsn, $config['username'], $config['password']);
    		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    	} catch (Exception $e) {
    		echo '<b>No se pudo conectar a la base de datos<b>. <br />' . $e->getMessage();
    	}

    	return $this->pdo;
	}

	public function execute ($sql) {
		$query = $this->pdo->query($sql);
		$result = $query->fetchAll();
		
		return $result;
	}

	public function table($table) {
	    if (is_array($table)) {
	    	$tables = '';
	    	foreach ($table as $key) {
	    		$tables .= $key . ', ';
	    		$this->from = rtrim($tables, ', ');
	    	}
	    } else {
	    	$this->from = $table;
	    }

	    return $this;
	}

	public function select ($campos) {
		$select = (is_array($campos) ? implode(', ', $campos) : $campos);
		$this->select = ($this->select == '*' ? $select : $this->select . ', ' . $select);

		return $this;
	}

	public function where($where, $op = null, $val = null, $type = '', $andOr = "AND") {
	    if (is_array($where)) {
	      $_where = [];

	      foreach ($where as $column => $data){
	        $_where[] = $type . $column . '=' . $this->escape($data);
	      }

	      $where = implode(' ' . $andOr . ' ', $_where);
	    }
	    else {
	      if(is_array($op)) {
	        $x = explode('?', $where);
	        $w = '';

	        foreach($x as $k => $v) {
	          if(!empty($v)) {
	            $w .= $type . $v . (isset($op[$k]) ? $this->escape($op[$k]) : '');
	          }
	      	}

	        $where = $w;
	      } elseif (!in_array($op, $this->op) || $op == false) {
	        $where = $type . $where . " = " . $this->escape($op);
	      } else {
	        $where = $type . $where . ' ' . $op . ' ' . $this->escape($val);
	      }
	    }

	    if (is_null($this->where)){
	      $this->where = $where;
	    } else {
	      $this->where = $this->where . ' ' . $andOr . ' ' . $where;
	    }
	    return $this;
	}

	public function insert($data) {
		$sql = 'INSERT INTO ' . $this->from;

		$column = implode(',', array_keys($data));
      	$val = implode(", ", array_map([$this, "escape"], $data));

      	$sql .= " (" . $column . ") VALUES (" . $val . ")";

      	$query = $this->pdo->query($sql);
	}

	public function update($data) {
		$sql = 'UPDATE ' . $this->from . ' SET ';
		$values = [];

		foreach ($data as $column => $val){
		  $values[] = $column . '=' . $this->escape($val);
		}

		$sql .= (is_array($data) ? implode(',', $values) : $data);

		if (!is_null($this->where)) {
		  $sql .= " WHERE " . $this->where;
		}

		$query = $this->pdo->query($sql);
	}

	public function delete () {
		$sql = 'DELETE FROM ' . $this->from;

		if (!is_null($this->where)) {
		  $sql .= " WHERE " . $this->where;
		}

		$query = $this->pdo->query($sql); 
	}

	public function escape($data) {
	   if($data === NULL){
	     return 'NULL';
	   }

	   if(is_null($data)){
	     return null;
	   }

	   return $this->pdo->quote(trim($data));
	}

	public function get() {
		$sql = "SELECT " . $this->select . " FROM " . $this->from;
		$query = $this->pdo->query($sql);

		return $query->fetch();
	}

	public function getAll() {
		$sql = "SELECT " . $this->select . " FROM " . $this->from;
		$query = $this->pdo->query($sql);

		return $query->fetchAll();
	}
}

$config = [
	'host' => 'localhost',
	'driver' => 'mysql',
	'database' => 'maraton',
	'username' => 'root',
	'password' => 'password',
];
