<?php

include('simple_html_dom/simple_html_dom.php');

class declensionTable {

	public $type = false;
	public $html = '';
	public $json = '';
	
	public function __construct($type, $html) {
		$this->type = $type;
		$this->html = $html;
	}
	
	public function parse() {
		//$this->json = $this->parseHTML($type);
		$this->json = 'not parsed yet';
	}
	
}

class Parser {
	
	private $mysqli = false;
	private $debug = false;
	
	private $tableTypes = array(
		'substantive', // существительное
		'verb', // глагол
		'adjective' // прилагательное
	);
	
	public function __construct($debug = false) {
		$this->debug = $debug;
		$this->connect();
	}
	
	public function connect() {
		$mysqli = new mysqli("localhost", "wiki_json", "wiki_json", "wiki_json");
        if ($mysqli->connect_errno) 
        {
            print "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
			return false;
        }
        $this->mysqli = $mysqli;
		return true;
	}
	
	function query($sql) {
		$result = $this->mysqli->query($sql);
        if (!$result) {
            $error = "MYSQLI Error: (" . $this->mysqli->errno . ") " . $this->mysqli->error;
            if($this->debug) {
                print 'mysqli error: '.$error;
                print '<br>';
            }
        }
		else {
			$data = array();
			while($row = $result->fetch_assoc()) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
    }
	
	/**
	 * Finds declension table among other 'table' tags content
	 * @param array $tables
	 * @return \declensionTable|boolean
	 */
	public function findDeclensionTable($tables) {
		foreach ($tables as $tableHtml) {
			foreach ($this->tableTypes as $tableType) {
				if ($this->checkIfDeclensionTable($tableType, $tableHtml)) {
					$table = new declensionTable($tableType ,$tableHtml);
					return $table;
				}
			}
		}
		return false;
	}
	
	/**
	 * Checks if provided table is declension table
	 * @param string $tableType
	 * @param string $tableHtml
	 * @return boolean
	 */
	public function checkIfDeclensionTable($tableType, $tableHtml) {
		$fnName = 'checkIfDeclensionTableIs' . ucfirst($tableType);
		if (method_exists($this, $fnName)) {
			return $this->$fnName($tableHtml);
		}
		else return false;
	}
	
	public function checkIfDeclensionTableIsSubstantive($tableHtml) {
		return !(strpos($tableHtml, '<a href="/wiki/%D0%BF%D0%B0%D0%B4%D0%B5%D0%B6" title="падеж">падеж</a>') === false);
	}


	public function getAllTablesHTML($id) {
		$sql = 'SELECT * from russian_words WHERE id = ' . intval($id) .' LIMIT 1';
		$res = $this->query($sql);
		

		//$html = str_get_html(gzuncompress($res[0]['wikidictionary_html']));
		//$tables = $html->find('table');
		
		$html = gzuncompress($res[0]['wikidictionary_html']);
		
		$tables = array();
		preg_match_all('/<table.*?>(.*?)<\/table>/si', $html, $tables); 
		
		return $tables[0];
	}
	
	
	public function header() { ?>
<!DOCTYPE html>
<html lang="ru" dir="ltr" class="client-nojs">
	<head>
	<meta charset="UTF-8" />
	<title>w</title>
	<meta name="generator" content="MediaWiki 1.25wmf12" />
	</head>
	<body>
	<?php
	}
	
	public function footer() { ?>
	</body>
	</html>
	<?php
	}
	public function run($id) {
		$this->header();
		$tables = $this->getAllTablesHTML($id);
		
		$declensionTable = $this->findDeclensionTable($tables);
		if ($declensionTable) {
			$declensionTable->parse();
		
			echo('<h1>' . $declensionTable->type. '</h1>');
			echo($declensionTable->html . '<br><hr>');
			echo('<pre>' . print_r($declensionTable->json, 1) . '</pre>');
		}
		else {
			echo "<h1>Declenion table not found!</h1>";
			foreach ($tables as $i => $tableHTML) {
				echo "<h1>Table # $i</h1>";
				echo($tableHTML.'<br><br><br>');
			}
		}
		$this->footer();
	}
}

$parser = new Parser(true);

$id = isset($_GET['id']) ? intval($_GET['id']) : 70;
$parser->run($id);
?>
