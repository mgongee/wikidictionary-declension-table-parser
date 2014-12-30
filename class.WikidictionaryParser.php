<?php

/**
 * Description of class
 *
 * @author Work
 */

class WikidictionaryParser {
	
	private $debug = false;
	
	public $rawHtml = false;
	public $rawJson = false;
	
	public $html = false;
	public $json = false;
	
	/* extracted info */
	public $tables = false;
	public $declensionType = false;
	public $baseForms = false;
	public $gender = false;
	
	/**
	 * Note that adjective must go before substentive, because checking type of the word stops after the first valid check
	 * and check for substantive is also valid for adjective.
	 * @var array
	 */
	private $tableTypes = array(
		'adjective', // прилагательное
		'substantive', // существительное
		
		'verbPerfect', // глагол в прошедшем
		'verbPresent', // глагол в настоящем
		
		'verb', // глагол (must go after 'verbPerfect', 'verbPresent' on the same reason as with 'adjective' )
	);
	
	public function __construct($debug = false) {
		$this->debug = $debug;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	/**
	 * Finds declension table among other 'table' tags content
	 * @return \declensionTable|boolean
	 */
	public function findDeclensionTable() {
		foreach ($this->tables as $tableHtml) {
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
	 * Searches for 'будущ' cell in the table
	 * @param string $tableHtml
	 * @return boolean
	 */
	public function checkIfDeclensionTableIsVerbPerfect($tableHTML) {
		if ($this->checkIfDeclensionTableIsVerb($tableHTML)) {
			return !(stripos($tableHTML,'<a href="/wiki/%D0%B1%D1%83%D0%B4%D1%83%D1%89%D0%B5%D0%B5_%D0%B2%D1%80%D0%B5%D0%BC%D1%8F" title="будущее время">будущ.</a>') === false);
		}
		else return false;
	}

	/**
	 * Searches for 'наст' cell in the table
	 * @param string $tableHtml
	 * @return boolean
	 */
	public function checkIfDeclensionTableIsVerbPresent($tableHTML) {
		if ($this->checkIfDeclensionTableIsVerb($tableHTML)) {
			return !(stripos($tableHTML,'<a href="/wiki/%D0%BD%D0%B0%D1%81%D1%82%D0%BE%D1%8F%D1%89%D0%B5%D0%B5_%D0%B2%D1%80%D0%B5%D0%BC%D1%8F" title="настоящее время">наст.</a>') === false);
		}
		else return false;
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
	 * Queries HTML & JSON from DB
	 */
	public function queryData() {
		$sql = 'SELECT * from russian_words WHERE id = ? LIMIT 1';
		$res = SQLPatterns::fetchAll($sql, array(intval($this->id)));
		
		if (is_array($res) && count($res)) {
			$this->rawHtml = $res[0]['wikidictionary_html'];
			$this->html = gzuncompress($this->rawHtml);
			
			$this->rawJson = $res[0]['wikidictionary_json'];
			$this->json = json_decode(gzuncompress($this->rawJson), true);
			//echo('<pre>' . print_r($this->json, 1) . '</pre>');die();
			return true;
		}
		else {
			$this->html = false;
			return false;
		}
	}
	
	/**
	 * Extracts all tables from given HTML
	 */
	public function extractAllTables() {	
		$tables = array();
		preg_match_all('/<table.*?>(.*?)<\/table>/si', $this->html, $tables); 
		$this->tables = $tables[0];
	}
	
	/**
	 * Extracts gender, base forms and declension type from given JSON
	 */
	public function extractWordInfo() {
		$text = $this->json['parse']['wikitext']['*'];
		$start = strpos($text, 'Морфологические и синтаксические свойства');	
		if ($start !== false) {
			$text = explode('{{', substr($text, $start));
			$text = explode('}}', $text[1]);
			$text = explode('|', $text[0]);
			
			//echo('[[<pre>' . print_r($text, 1) . '</pre>]]');
			$this->baseForms = array();

			foreach ($text as $str) {
				$res = preg_match_all('/основа(.?)=(.*)/si', $str, $matches);
				if ($res) {
					$this->baseForms[] = $matches[2][0];
				}
			}
			
			$text = explode(' ', $text[0]);
			
			$this->declensionType = $text[count($text) - 1];
			
			if ($text[0] == 'сущ') {
				$this->gender = $text[2];
			}
			else {
				$this->gender = false;
			}
			
		}
		else {
			$this->baseForms = false;
			$this->declensionType = false;
			$this->gender = false;
		}
	}
			
	/**
	 * Returns subject of current article 
	 */
	public function getSubject() {	
		/*
		$subject = array();
		preg_match_all('/<h1.*?>.*?<span dir="auto">(.*?)<\/span>.*?<\/h1>/si', $this->html, $subject);
		$this->subject = $subject[1][0];
		*/
		return $this->json['parse']['title'];
		
	}
	
	
}

?>
