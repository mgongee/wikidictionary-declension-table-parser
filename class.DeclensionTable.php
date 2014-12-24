<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Declension table parser and entity.
 *
 * @author mgongee
 */

class DeclensionTable {

	public $type = false;
	public $html = '';
	public $json = '';
	
	public function __construct($type, $html) {
		$this->type = $type;
		$this->html = $html;
	}
	
	public function parse() {
		$jsonTableHeader = $this->parseTableCells('th');
		$jsonTableBody = $this->parseTableCells('td');
		$this->json = array_merge($jsonTableHeader,$jsonTableBody);
	}
	
	public function parseTableCells($tag = 'th') {
		$htmlParser = str_get_html($this->html);
		$tableJson = array();
		
		$curRow = 0; // current json row
		
		foreach($htmlParser->find('tr') as $rowElement) {
			$curCol = 0; // current json column
			
			if (!isset($tableJson[$curRow])) $tableJson[$curRow] = array(); // create next JSON row if it not exists
			
			foreach($rowElement->find($tag) as $colElement) {	
				
				// each parsed table cell has its size
				
				$cellSize = array(
					'rows' => 1,
					'columns' => 1
				);
				
				// determine actual rowpan & colspan of the parsed table cell
				
				if (isset($colElement->rowspan)) {
					$cellSize['rows'] = $colElement->rowspan;
				}
				
				if (isset($colElement->colspan)) {
					$cellSize['columns'] = $colElement->colspan;
				}
				
				// skip JSON cells until empty cell is found
				while (isset($tableJson[$curRow][$curCol])) { 
					$curCol++; // next cell
				}
				
				// fill current JSON cell(s) with content of one parsed table cell
				for ($i = 0; $i < $cellSize['rows']; $i++) {
					for ($j = 0; $j < $cellSize['columns']; $j++) {
						if (!isset($tableJson[$curRow + $i])) $tableJson[$curRow + $i] = array(); // create next row if it not exists

						$tableJson[$curRow + $i][$curCol + $j] = $colElement->innertext;
					}
				}
				$curCol += $cellSize['columns'];
				
			}
			$curRow++;
		}
		
		$this->removeEmptyRows($tableJson);
		$this->compactSameCells($tableJson);
		return $tableJson;
	}
	
	private function removeEmptyRows(&$array) {
		foreach ($array as $key => $value) {
			if (is_array($value) && (count($value) == 0)) {
				unset($array[$key]);
			}
		}
	}
	
	private function compactSameCells(&$array) {
		foreach ($array as $key => $row) {
			if (is_array($row) && (count($row) > 0)) {
				$total = count($row);
				$sameCells = 0;
				foreach ($row as $cell) if ($cell == $row[0]) $sameCells++;
				if ($sameCells == $total) $array[$key] = array($row[0]);
			}
		}
	}
	
}

?>
