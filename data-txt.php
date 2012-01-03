<?php

/**
 * Used to create a plain text file of data requested by the user
 * Identical to CSV output except for two points
 *  - individual items are displayed with a row for each column value
 *  - the file is displayed in the browser rather than offered as a download
 *
 * Could also include the option to access the value for just one field.
 *
 * @package WordPressOpenData
 */

function od_display_data($od_object,$od_type="data"){
	header("Content-type: text/plain");
	if($od_type=="item"){ // if it's a data item then display each column on its own row
		$od_data = $od_object->get_item();
		foreach($od_data as $entrykey=>$entryrow){
			echo "$entrykey: ";
			if(is_array($entryrow)){
				echo implode("; ",$entryrow);
			} else {
				echo $entryrow;
			}
			echo "\n";
		}
	} else {
		$od_data = $od_object->get_data();
		$outstream = fopen("php://temp", 'r+'); // open a temporary file to write the CSV to
		$count=0;
		foreach($od_data as $entrykey=>$entryrow){ 
			if($count==0){
				$data = array();
				foreach($entryrow as $header=>$column){
					$data[] = $header;
				}
				fputcsv($outstream,$data,",",'"'); // use PHP's CSV write function to write the header row
			}
			$data = array();
			foreach($entryrow as $header=>$column){
				if(is_array($column)){
					$data[] = implode("; ",$column);
				} else {
					$data[] = $column;
				}
			}
			fputcsv($outstream,$data,",",'"'); // use PHP's CSV write function to write each row of data
			$count++;
		}
		rewind($outstream); // go back to the beginning of the file
		$output = stream_get_contents($outstream); // get the contents of the temporary file
		fclose($outstream); // close the temporary file
	}
	return $output;
}

?>