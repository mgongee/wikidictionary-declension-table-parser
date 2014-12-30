<?php

/**
 * Runs the parser for single or many rows
 *
 * @author mgongee
 */
class ParserRunner {

	private $parser;
	public function __construct($debug = false) {
		$this->parser = new WikidictionaryParser($debug);
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
		$this->parser->setId($id);
		if ($this->parser->queryData()) {
			$this->parser->extractAllTables();
			$this->parser->extractWordInfo();

			echo('<h1>' . $this->parser->getSubject() . '</h1>');
			
			echo('Base Forms: ' . ($this->parser->baseForms ? implode(' , ' , $this->parser->baseForms) : '<b>not found</b> ') . '<br>');
			echo('Declension type: ' . $this->parser->declensionType . '<br>');
			echo('Gender: ' . $this->parser->gender . '<br>');
		
			$wordInfo = array(
				'id'					=> $id,
				'wikidictionary_json'	=> $this->parser->rawJson,
				'wikidictionary_html'	=> $this->parser->rawJson,
				'declension_html'		=> '',
				'declension_json'		=> '',
				'has_declension'		=> '0',
				'declension_type'		=> $this->parser->declensionType,
				'base_forms'			=> json_encode($this->parser->baseForms),
				'gender'				=> $this->parser->gender
			);
			
			$declensionTable = $this->parser->findDeclensionTable();
			if ($declensionTable) {
				$declensionTable->parse();

				echo('<h1>' . $declensionTable->type. '</h1>');
				echo($declensionTable->html . '<br><hr>');
				echo('<pre>' . print_r($declensionTable->json,1) . '</pre><br><hr>');
				
				$wordInfo['declension_html'] = $declensionTable->html;
				$wordInfo['declension_json'] = $declensionTable->json;
				$wordInfo['has_declension'] = 1;
				
			}
			else {
				echo "<h1>Declenion table not found!</h1>";
				echo($this->parser->html);
				foreach ($this->parser->tables as $i => $tableHTML) {
					echo "<h1>Table # $i</h1>";
					echo($tableHTML.'<br><br><br>');
				}
			}
			
			if (isset ($_GET['save'])) $this->saveWordInfo($wordInfo);
		}
		
		$this->footer();
		
	}
	
	public function runForRange($start,$end) {
		$this->header();
		
		for ($i = $start; $i <= $end; $i++) {
			$this->parser->setId($i);
			if ($this->parser->queryData()) {
				$this->parser->extractAllTables();
				$this->parser->extractWordInfo();

				echo '<br>' . $i .': <b>' . $this->parser->getSubject() . '</b><br>';

				echo('Base Forms: ' . ($this->parser->baseForms ? implode(' , ' , $this->parser->baseForms) : '<b>not found</b> ') . '<br>');
				echo('Declension type: ' . $this->parser->declensionType . '<br>');
				echo('Gender: ' . $this->parser->gender . '<br>');

				$wordInfo = array(
					'id'					=> $i,
					'wikidictionary_json'	=> $this->parser->rawJson,
					'wikidictionary_html'	=> $this->parser->rawJson,
					'declension_html'		=> '',
					'declension_json'		=> '',
					'has_declension'		=> '0',
					'declension_type'		=> $this->parser->declensionType,
					'base_forms'			=> json_encode($this->parser->baseForms),
					'gender'				=> $this->parser->gender
				);
		
				$declensionTable = $this->parser->findDeclensionTable();
				if ($declensionTable) {
					$declensionTable->parse();
					//echo 'DeclensionTable: ' . $declensionTable->type . $declensionTable->html . htmlspecialchars($declensionTable->json);
					
					
					$wordInfo['declension_html'] = $declensionTable->html;
					$wordInfo['declension_json'] = $declensionTable->json;
					$wordInfo['has_declension'] = 1;
				}
				else {
					echo 'DeclensionTable: <b>unknown</b>';				
				}
				
				if (isset ($_GET['save'])) $this->saveWordInfo($wordInfo);
			}
			else {
				echo '<br>' . $i .': <b>Not found</b><br>';
			}
		}
		$this->footer();
	}
	
	private function checkIfWordAsAbsent($id) {
		$sql = 'SELECT id from russian_words_data WHERE id = ? LIMIT 1';
		$res = SQLPatterns::fetchAll($sql, array(intval($id)));
		
		if (is_array($res) && count($res)) {	
			return true;
		}
		return false;
	}
	
	public function runForAbsent() {
		$this->header();
		
		for ($i = 1;$i <= 5000; $i++) {
			if (!$this->checkIfWordAsAbsent($i)) {
				$this->parser->setId($i);
				if ($this->parser->queryData()) {
					$this->parser->extractAllTables();
					$this->parser->extractWordInfo();

					echo '<br>' . $i .': <b>' . $this->parser->getSubject() . '</b><br>';

					echo('Base Forms: ' . ($this->parser->baseForms ? implode(' , ' , $this->parser->baseForms) : '<b>not found</b> ') . '<br>');
					echo('Declension type: ' . $this->parser->declensionType . '<br>');
					echo('Gender: ' . $this->parser->gender . '<br>');

					$wordInfo = array(
						'id'					=> $i,
						'wikidictionary_json'	=> $this->parser->rawJson,
						'wikidictionary_html'	=> $this->parser->rawJson,
						'declension_html'		=> '',
						'declension_json'		=> '',
						'has_declension'		=> '0',
						'declension_type'		=> $this->parser->declensionType,
						'base_forms'			=> json_encode($this->parser->baseForms),
						'gender'				=> $this->parser->gender
					);

					$declensionTable = $this->parser->findDeclensionTable();
					if ($declensionTable) {
						$declensionTable->parse();
						//echo 'DeclensionTable: ' . $declensionTable->type . $declensionTable->html . htmlspecialchars($declensionTable->json);


						$wordInfo['declension_html'] = $declensionTable->html;
						$wordInfo['declension_json'] = $declensionTable->json;
						$wordInfo['has_declension'] = 1;
					}
					else {
						echo 'DeclensionTable: <b>unknown</b>';				
					}

					if (isset ($_GET['save'])) $this->saveWordInfo($wordInfo);
				}
				else {
					echo '<br>' . $i .': <b>Not found</b><br>';
				}
			}
		}
		$this->footer();
	}
	
	public function saveWordInfo($wordInfo) {
		$sql = 'INSERT INTO russian_words_data VALUES (?,?,?,?,?,?,?,?,?)';
		SQLPatterns::exec($sql, $wordInfo);
	}
}
?>
