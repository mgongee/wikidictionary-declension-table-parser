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
	public $tableArray = '';
	public $json = '';
	
	public function __construct($type, $html) {
		$this->type = $type;
		$this->html = $html;
	}
	
	public function parse() {
		$jsonTableHeader = $this->parseTableCells('th');
		$jsonTableBody = $this->parseTableCells('td');
		$this->tableArray = array_merge($jsonTableHeader,$jsonTableBody);
		//echo('<pre>' . print_r($this->tableArray , 1) . '</pre>');
		//echo('<pre>' . print_r($this->formatTableArray($this->tableArray) , 1) . '</pre>');
		$this->json = json_encode($this->formatTableArray($this->tableArray));
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
		
				// determine if table cell is row/column header
				$isRowHeader = false;
				$isColumnHeader = false;
					
				if ($tag == 'th') $isColumnHeader = true;
				elseif (	(isset($colElement->bgcolor) && $colElement->bgcolor == '#EEF9FF') || 
						(isset($rowElement->bgcolor) && $rowElement->bgcolor == '#EEF9FF')	) { 					
					$isRowHeader = true;
				}
				// skip JSON cells until empty cell is found
				while (isset($tableJson[$curRow][$curCol])) { 
					$curCol++; // next cell
				}
				
				// fill current JSON cell(s) with content of one parsed table cell
				for ($i = 0; $i < $cellSize['rows']; $i++) {
					for ($j = 0; $j < $cellSize['columns']; $j++) {
						if (!isset($tableJson[$curRow + $i])) $tableJson[$curRow + $i] = array(); // create next row if it not exists

						$tableJson[$curRow + $i][$curCol + $j] = array(
							'isRowHeader'		=> $isRowHeader,
							'isColumnHeader'	=> $isColumnHeader,
							'value'				=> $colElement->innertext
						);
					}
				}
				$curCol += $cellSize['columns'];
				
			}
			$curRow++;
		}
		
		foreach ($tableJson as $key => $row) {
			ksort($tableJson[$key]);
		}
		$this->removeEmptyRows($tableJson);
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
	
	private function formatTableArray($tableArray) {
		$columnHeaders = array();
		$rowHeaders = array();
		$rows = array();
		
		foreach ($tableArray as $key => $row) {
			$rows[$key] = array();
			$rowHeader = '';
			
			foreach ($row as $i => $cell) {
				
				if ($cell['isColumnHeader']) {
					if (!isset($columnHeaders[$i])) $columnHeaders[$i] = '';
					
					if ($columnHeaders[$i] != $cell['value']) {
						$columnHeaders[$i] .= $cell['value'];
					}
				}
				elseif ($cell['isRowHeader']) {
					$rowHeader .= $cell['value'] . ' ';
				}
				else {
					$rows[$key][] = $cell['value'];
				}
			}
			
			$rowHeaders[] = $rowHeader;
			$this->compactSameCells($rows);
		}
		
		$columnHeaders = $this->removeFirstColumn($columnHeaders);
		
		return array(
			'columnHeaders' => $columnHeaders,
			'rowHeaders' => $rowHeaders,
			'rows' => $rows
		);
	}
	
	private function removeFirstColumn($array) {
		$deleteValue = true;
		$deletedValue = false;
		foreach ($array as $key => $value) {
			if ($deleteValue) {
				$deletedValue = $array[$key];
				unset($array[$key]);
				
				$deleteValue = false;
			}
			elseif ($array[$key] == $deletedValue) {
				unset($array[$key]);
			}
		}
		return $array;
	}
}

?>
