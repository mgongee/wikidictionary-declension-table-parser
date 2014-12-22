<?php

/* https://github.com/tomac120/SqlPatterns */

include_once 'class.DB.php';

class SQLPatterns
{
	/* THIS FUNCTION IS USED BY ALL OTHER FUNCTIONS IN THE CLASS
	 * SUBCLASS OR UPDATE THIS FUNCTION TO USE YOUR OWN DATABASE CONNECTOR
	 */
	protected static function getPrepared($sql,$bound=array(),$opts=null)
	{
		$db = new DB();
		$stmt = $db->prepare($sql);
		$bind_index = 1;
		foreach ($bound as $key => $value)
		{
			$stmt->bindParam($bind_index++,$bound[$key],PDO::PARAM_STR);
		}
		$stmt->execute();
		if (($stmt->errorCode()=='00000'))
		{
			return $stmt;
		}
	}
	/* SAME AS PDO::FETCH_ALL */
	public static function fetchAll($sql,$bound=array(),$opts=null)
    {
        $stmt = self::getPrepared($sql,$bound,$opts);
        if ($stmt)
        {
			return $stmt->fetchAll();
        }
        return Array();
	}
	
	/* GET A SINGLE DIMENSIONAL ARRAY */
	public static function fetchFirstColumnArray($sql,$bound=array(),$opts=null)
	{
		$stmt = self::getPrepared($sql,$bound,$opts);
		$array = Array();
		while($stmt && $row = $stmt->fetch(PDO::FETCH_NUM))
		{
			$array[] = isset($row[0]) ? $row[0]:'';
		}
		return $array;
	}
	
	/* RETURNS AN ARRAY OF OBJECTS */
	public static function fetchObjects($sql,$bound=array(),$class_name='stdclass',$opts=null)
	{
		$stmt = self::getPrepared($sql,$bound,$opts);
		$array  = Array();
		while($stmt && $obj = $stmt->fetchObject($class_name))
		{
			$array[] = $obj;
		}
		return $array;
	
	}
	
	/* RETURNS ONLY ONE VALUE (first column of first row) */
	public static function fetchOne($sql,$bound=array(),$opts=null)
	{
		$row = self::fetchOneRow($sql,$bound,$opts);
		if ($row)
		{
			foreach ($row as $value)
			{
				return $value; // EX. "1" or "abc"
			}
		}
	
	}
	
	/* RETURNS ONLY ONE OBJECT (from the first row) */
	public static function fetchOneObject($sql,$bound=array(),$class_name='stdclass',$opts=null)
	{
		$stmt = self::getPrepared($sql,$bound,$class_name,$opts);
		if($stmt && $obj = $stmt->fetchObject($class_name))
		{
			return $obj;
		}
	}
	
	/* RETURNS ONLY THE FIRST ROW */
	public static function fetchOneRow($sql,$bound=array(),$opts=null)
	{
		$stmt = self::getPrepared($sql,$bound,$opts);
		if($stmt && $row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			return $row;
		}
	}
	
	/* RUN A SQL QUERY AND DISCARD THE RESULT */
	public static function exec($sql,$bound=array(),$opts=null)
	{
		$stmt = self::getPrepared($sql,$bound,$opts);
		return !!$stmt;
	}
	
	/* INSERT INTO A TABLE
	 * @param $table_name Table Name
	 * @param $data An array indexed by field name
	 */
	public static function insert($table_name,$data,$opts=null)
	{
		$fields = Array();
		$bound = Array();
		$question_marks = Array();
		foreach($data as $key=>$value)
		{
			$fields[] = $key;
			$bound[] = $value;
			$question_marks[] = '?';
		}
		$fields = implode("`,`",$fields);
		$question_marks = implode(',',$question_marks);
		$sql = "INSERT INTO `$table_name` (`$fields`) VALUES ($question_marks)";
		$stmt = self::getPrepared($sql,$bound,$opts);
		if ($stmt)
		{
			return DB::lastInsertIdStatic();
		}
	}
	
	/* UPDATE A DATABASE TABLE
	 * @param $table_name Table Name
	 * @param $data An array indexed by field name
	 * @param $end_sql The SQL that goes after the WHERE
	 * @param $end_sql_bound Numeric array of bound params for $end_sql
	 */
	public static function update($table_name,$data,$end_sql,$end_sql_bound=Array(),$opts=null)
	{
		$fields = Array();
		$bound = Array();
		foreach($data as $key=>$value)
		{
			$fields[] = $key;
			$bound[] = $value;
		}
		foreach($end_sql_bound as $value)
		{
			$values[] = $value;
		}
		$end_sql = str_replace('WHERE ','',$end_sql); // allow for a common error
		$fields = implode("`=?,",$fields);
		$sql = "UPDATE `$table_name` SET $fields WHERE ".$end_sql;
		$stmt = self::getPrepared($sql,$bound,$opts);
		return !!$stmt;
	}	
}

?>
