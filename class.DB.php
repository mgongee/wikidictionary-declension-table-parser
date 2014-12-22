<?php 

class DB
{
	static $pdo;
	
	public static function connect()
	{
		if (!self::$pdo)
		{
			try {
				self::$pdo = new PDO('mysql:host=localhost;dbname=wiki_json','wiki_json','wiki_json');
				self::$pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8");
			} catch (Exception $e)
			{
				echo 'Database Connection Error';
				exit();
			}
		}
	}
	
	function lastInsertId(){
		return self::$pdo->lastInsertId();
	}
	
	static function lastInsertIdStatic(){
		return self::$pdo->lastInsertId();
	}
	
	public static function prepare($sql){
		self::connect();
		return self::$pdo->prepare($sql);
	}
	
	static function quote($var,$type){
		return self::$pdo->quote($var,$type);
	}
}