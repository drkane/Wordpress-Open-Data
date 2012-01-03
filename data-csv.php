<?php

/**
 * Used to create a csv file of data requested by the user
 *
 * @package WordPressOpenData
 */

function od_display_data($od_object,$od_type="data"){
	if($od_type=="item"){ // if the user is requesting an item, then get data for the item
		$od_data = array($od_object->get_item());
	} else { // otherwise get all the data requested
		$od_data = $od_object->get_data();
	}
	header("Content-type: text/csv"); // file is csv
	header('Content-Disposition: attachment; filename="data_export.csv"'); // file should be downloaded in the browser
	$outstream = fopen("php://temp", 'r+'); // open a temporary file to write the CSV to
	$count=0;
	foreach($od_data as $entryrow){ // for each row in the data
		if($count==0){ // if its the first row then create a header row
			$data = array();
			foreach($entryrow as $header=>$column){
				$data[] = $header;
			}
			fputcsv($outstream,$data,",",'"'); // use PHP's CSV write function to write the header row
		}
		$data = array();
		foreach($entryrow as $header=>$column){
			if(is_array($column)){ // for values that are arrays, separate with a semi-colon
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
	return $output;
}

?>