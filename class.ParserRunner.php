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
			
			
			$declensionTable = $this->parser->findDeclensionTable();
			if ($declensionTable) {
				$declensionTable->parse();

				echo('<h1>' . $declensionTable->type. '</h1>');
				echo($declensionTable->html . '<br><hr>');
				echo('<pre>' . print_r($declensionTable->json,1) . '</pre><br><hr>');
				//echo $html;
			}
			else {
				echo "<h1>Declenion table not found!</h1>";
				echo($this->parser->html);
				foreach ($this->parser->tables as $i => $tableHTML) {
					echo "<h1>Table # $i</h1>";
					echo($tableHTML.'<br><br><br>');
				}
			}
			
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
		
			
		
				$declensionTable = $this->parser->findDeclensionTable();
				if ($declensionTable) {
					echo 'DeclensionTable: ' . $declensionTable->type . $declensionTable->html;
				}
				else {
					echo 'DeclensionTable: <b>unknown</b>';				
				}
			}
			else {
				echo '<br>' . $i .': <b>Not found</b><br>';
			}
		}
		$this->footer();
	}
	
}
?>
