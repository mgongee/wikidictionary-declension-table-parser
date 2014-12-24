<?php

include_once './simple_html_dom/simple_html_dom.php';

include_once './class.DB.php';
include_once './class.SQLPatterns.php';
include_once './class.DeclensionTable.php';
include_once './class.WikidictionaryParser.php';
include_once './class.ParserRunner.php';

$parserRunner = new ParserRunner(true);

$id = isset($_GET['id']) ? intval($_GET['id']) : false;
if ($id) {
	$parserRunner->runForSingle($id);
}
else {
	$start = isset($_GET['start']) ? intval($_GET['start']) : false;
	$end = isset($_GET['end']) ? intval($_GET['end']) : false;
	
	if ($start && $end) {
		$parserRunner->runForRange($start, $end);
	}
}



?>
