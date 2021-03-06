<?php

/**
 * Used to create an xml output of data requested by the user
 *
 * @package WordPressOpenData
 */

function od_display_data($od_object){
	$od_type = $od_object->data_type;
	if($od_type=="item"){
		$od_data = array($od_object->get_item());
	} else {
		$od_data = $od_object->get_data();
	}
	$output = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; // xml header
	$output .= "<".$od_object->get_table_metadata("name").">\n"; 
	$output .= "\t<title>". $od_object->get_table_metadata("nicename") ."</title>\n"; // Get the title of the dataset
	$output .= "\t<link>" . htmlspecialchars($od_object->change_datatype("html")) . "</link>\n"; // link to the equivalent dataset
	$output .= "\t<description>". $od_object->get_table_metadata("description") ."</description>\n"; // Get the description of the dataset
	$output .= "\t<filters>" . $od_object->get_filters("xml") . "</filters>\n"; // find the filters that have been used (and output them)
	foreach($od_data as $c){
		if($od_type!="item"){$output .= "\t<record>\n";} // main tag is "record" - this should be customisable
		foreach($c as $key=>$field){
			$header = str_replace(" ","_",$key); // in the tags, replace and spaces with underscores
			$header = preg_replace("/[^A-Za-z0-9-_:]/","",$header); // remove any non alphanumeric characters from the tags
			if(is_array($field)){
				if(substr($header,-1,1)=="s"){ // get rid of plurals in tag names for arrays (this could go horribly wrong!)
					$header = substr($header,0,-1);
				}
				if(substr($header,-1,1)=="y"){ // repluralise for the main tag
					$output .= "\t\t<".substr($header,0,-1).""."ies>\n";
				} else {
					$output .= "\t\t<$header"."s>\n";
				}
				foreach($field as $subfield){
					if(strpos($subfield,"&")>0||strpos($subfield,'>')>0){ // use CDATA if the value contains an &
						$cdata = "<![CDATA[";
						$cdataend = "]]>";
					} else {
						$cdata = $cdataend = "";
					}
					$output .= "\t\t\t<$header>$cdata$subfield$cdataend</$header>\n";
				}
				if(substr($header,-1,1)=="y"){
					$output .= "\t\t</".substr($header,0,-1).""."ies>\n";
				} else {
					$output .= "\t\t</$header"."s>\n";
				}
			} else {
				if(strpos($field,"&")>0||strpos($field,'>')>0){
					$cdata = "<![CDATA[";
					$cdataend = "]]>";
				} else {
					$cdata = $cdataend = "";
				}
				$output .= "\t\t<$header>$cdata$field$cdataend</$header>\n";
			}
		}
		if($od_type!="item"){$output .= "\t</record>\n";}
	}
	$output .= "</".$od_object->get_table_metadata("name").">\n";
	$output = trim($output);
	return $output;
}

?>