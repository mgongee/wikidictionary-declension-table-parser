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
		$html = $this->parser->queryForHTML($id);
		$subject = $this->parser->extractSubject($html);
		$tables = $this->parser->extractAllTables($html);
		
		echo('<h1>' . $subject. '</h1>');
		
		$declensionTable = $this->parser->findDeclensionTable($tables);
		if ($declensionTable) {
			$declensionTable->parse();
		
			echo('<h1>' . $declensionTable->type. '</h1>');
			echo($declensionTable->html . '<br><hr>');
			echo('<pre>' . print_r($declensionTable->json, 1) . '</pre>');
			//echo $html;
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
			$html = $this->parser->queryForHTML($i);
			$subject = $this->parser->extractSubject($html);
			$tables = $this->parser->extractAllTables($html);
		
			$out = '<br>' . $i .': ' .  $subject;
		
			$declensionTable = $this->parser->findDeclensionTable($tables);
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
?>
