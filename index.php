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
		echo htmlspecialchars($this->html);
		
		//$this->json = htmlspecialchars($this->html);
		
		$htmlParser = str_get_html($this->html);
		$tableJson = array();
		
		//$es = $htmlParser->find("a");
		$curRow = 0; // current Row
		$rowspanFlag = array();
		$colspanFlag = array(); // used when rowspan and colspan at the same time
		
		foreach($htmlParser->find('tr') as $trElement) {
			$curCol = 0; // current column
			$tableJson[$curRow] = array();
			foreach($trElement->find('th') as $thElement) {	
				if (isset($colspanFlag[$curCol]) && $colspanFlag[$curCol]) { // we need to copy this cell from previous row
					echo('---- NOTED COLSPAN AND ROWSPAN' . $curCol . $curRow);
					for ($k = 0; $k < $colspanFlag[$curCol]; $k++) {
						$tableJson[$curRow][$curCol + $k] = $tableJson[$curRow - 1][$curCol]; // copy cell content from the previous row
					}
					$curCol += $colspanFlag[$curCol];
				}
				
				if (isset($rowspanFlag[$curCol]) && $rowspanFlag[$curCol]) { // we need to copy this cell from previous row
					echo('isset($rowspanFlag[$j]' . $curCol);
					$rowspanFlag[$curCol]--; // decrease count of how many rows nees to be copied from the original row
					$tableJson[$curRow][$curCol] = $tableJson[$curRow - 1][$curCol]; // copy cell content from the previous row
					$curCol++; // move to the next call in table
					
				}
				elseif (isset($thElement->rowspan)) {
					if (isset($thElement->colspan)) { // we will need to copy N next cells from this
						echo('FOUND COLSPAN AND ROWSPAN');
						$colspanFlag[$curCol] = $thElement->colspan;
					}
					else {
						$rowspanFlag[$curCol] = $thElement->rowspan - 1; // set copy mode for next N rows
					}
					
					$tableJson[$curRow][$curCol] = $thElement->innertext;
					echo('$thElement->rowspan:' . $thElement->rowspan . ' = [' . $curCol.' ]');
				}
				
				if (isset($thElement->colspan)) { // we will need to copy N next cells from this
					echo('$thElement->colspan:' . $thElement->colspan . ' = [' . $curCol.' ]');
					for ($k = 0; $k < $thElement->colspan; $k++) {
						$tableJson[$curRow][$curCol + $k] = $thElement->innertext;
					}
					
					$curCol += $thElement->colspan;	
				}
				else {
					$tableJson[$curRow][$curCol] = $thElement->innertext;
					$curCol++;	
				}
			}
			/*
			foreach($trElement->find('td') as $tdElement) {	
				$tableJson[$i][$j] = $tdElement->innertext;
				$j++;
			}
			 * 
			 */
			$curRow++;
		}
			
		$this->json = $tableJson;
	}
}

class Parser {
	
	private $mysqli = false;
	private $debug = false;
	
	/**
	 * Note that adjective must go before substentive, because checking type of the word stops after the first valid check
	 * and check for substantive is also valid for adjective.
	 * @var array
	 */
	private $tableTypes = array(
		'adjective', // прилагательное
		'substantive', // существительное
		'verb', // глагол
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

	/**
	 * Searches for 'падеж' cell in the table
	 * @param string $tableHtml
	 * @return boolean
	 */
	public function checkIfDeclensionTableIsSubstantive($tableHtml) {
		return !(strpos($tableHtml, '<a href="/wiki/%D0%BF%D0%B0%D0%B4%D0%B5%D0%B6" title="падеж">падеж</a>') === false);
	}

	/**
	 * Searches for 'Я' cell in the table
	 * @param string $tableHtml
	 * @return boolean
	 */
	public function checkIfDeclensionTableIsVerb($tableHtml) {
		return !(strpos($tableHtml, '<a href="/wiki/%D1%8F" title="я">Я</a>') === false);
	}

	
	/**
	 * Searches for three cells 'муж.р','ср.р','жен.р' in the table
	 * @param string $tableHtml
	 * @return boolean
	 */
	public function checkIfDeclensionTableIsAdjective($tableHtml) {
		$maleVariant = !(strpos($tableHtml, 'title="мужской род">') === false);
		$itVariant = !(strpos($tableHtml, 'title="средний род">') === false);
		$femaleVariant = !(strpos($tableHtml, 'title="женский род"') === false);
		
		return ($maleVariant && $itVariant && $femaleVariant);		
	}

	
			
	/**
	 * Queries HTML from DB for given ID
	 * @param integer $id
	 * @return boolean|string
	 */
	public function getHTML($id) {
		$sql = 'SELECT * from russian_words WHERE id = ' . intval($id) .' LIMIT 1';
		$res = $this->query($sql);
		
		if (is_array($res) && count($res)) {
			$html = gzuncompress($res[0]['wikidictionary_html']);
			return $html;
		}
		return false;
	}
	
	/**
	 * Extracts all tables from given HTML
	 * @param string $html
	 * @return array
	 */
	public function extractAllTables($html) {	
		$tables = array();
		preg_match_all('/<table.*?>(.*?)<\/table>/si', $html, $tables); 
		return $tables[0];
	}
	
	
	/**
	 * Extracts subject of the article from given HTML
	 * @param string $html
	 * @return array
	 */
	public function extractSubject($html) {	
		$subject = array();
		preg_match_all('/<h1.*?>.*?<span dir="auto">(.*?)<\/span>.*?<\/h1>/si', $html, $subject);
		//echo('<pre>' . print_r($subject[1], 1) . '</pre>');
		return $subject[1][0];
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
	
	public function runForSingle($id) {
		$this->header();
		$html = $this->getHTML($id);
		$subject = $this->extractSubject($html);
		$tables = $this->extractAllTables($html);
		
		echo('<h1>' . $subject. '</h1>');
		
		$declensionTable = $this->findDeclensionTable($tables);
		if ($declensionTable) {
			$declensionTable->parse();
		
			echo('<h1>' . $declensionTable->type. '</h1>');
			echo($declensionTable->html . '<br><hr>');
			echo('<pre>' . print_r($declensionTable->json, 1) . '</pre>');
			echo $html;
		}
		else {
			echo "<h1>Declenion table not found!</h1>";
			echo($html);
			foreach ($tables as $i => $tableHTML) {
				echo "<h1>Table # $i</h1>";
				echo($tableHTML.'<br><br><br>');
			}
		}
		$this->footer();
	}
	
	public function runForRange($start,$end) {
		$this->header();
		
		for ($i = $start; $i <= $end; $i++) {
			$html = $this->getHTML($i);
			$subject = $this->extractSubject($html);
			$tables = $this->extractAllTables($html);
		
			$out = '<br>' . $i .': ' .  $subject;
		
			$declensionTable = $this->findDeclensionTable($tables);
			if ($declensionTable) {
				$out .= ': ' . $declensionTable->type . $declensionTable->html;
			}
			else {
				$out .= ': <b>unknown</b>';				
			}
			echo ($out);
		}
		$this->footer();
	}
}

$parser = new Parser(true);

$id = isset($_GET['id']) ? intval($_GET['id']) : false;
if ($id) {
	$parser->runForSingle($id);
}
else {
	$start = isset($_GET['start']) ? intval($_GET['start']) : false;
	$end = isset($_GET['end']) ? intval($_GET['end']) : false;
	if ($start && $end) {
		$parser->runForRange($start, $end);
	}
}



?>
