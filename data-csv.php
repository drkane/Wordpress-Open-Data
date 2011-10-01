<?php

function od_display_data($od_object,$od_type="data"){
	if($od_type=="item"){
		$od_data = array($od_object->get_item());
	} else {
		$od_data = $od_object->get_data();
	}
	header("Content-type: text/csv");
	$outstream = fopen("php://temp", 'r+'); // open a temporary file to write the CSV to
	$count=0;
	foreach($od_data as $entryrow){ 
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
	return $output;
}

?>