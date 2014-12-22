<?php



include './class.ParserRunner.php';

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
