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
		//echo htmlspecialchars($this->html);
		
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

?>
