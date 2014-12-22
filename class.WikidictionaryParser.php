<?php

include './simple_html_dom/simple_html_dom.php';
include './class.SQLPatterns.php';
include './class.DeclensionTable.php';

/**
 * Description of class
 *
 * @author Work
 */

class WikidictionaryParser {
	
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
					$table = new DeclensionTable($tableType ,$tableHtml);
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
	public function queryForHTML($id) {
		$sql = 'SELECT * from russian_words WHERE id = ' . intval($id) .' LIMIT 1';
		$res = SQLPatterns::fetchAll($sql);
		
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
	
	
}

?>