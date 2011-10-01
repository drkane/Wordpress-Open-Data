<?php

function od_display_data($od_object,$od_type="data"){
	if($od_type=="item"){
		$od_data = array($od_object->get_item());
	} else {
		$od_data = $od_object->get_data();
	}
	header("Content-type: text/xml");
	$output = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; // xml header
	$output .= "<output>\n"; 
	foreach($od_data as $c){
		if($od_type!="item"){$output .= "\t<record>\n";}
		foreach($c as $key=>$field){
			$header = str_replace(" ","_",$key); // in the tags, replace and spaces with underscores
			$header = preg_replace("/[^A-Za-z0-9-_:]/","",$header); // remove any non alphanumeric characters from the tags
			if(is_array($field)){
				if(substr($header,-1,1)=="s"){
					$header = substr($header,0,-1);
				}
				if(substr($header,-1,1)=="y"){
					$output .= "\t\t<".substr($header,0,-1).""."ies>\n";
				} else {
					$output .= "\t\t<$header"."s>\n";
				}
				foreach($field as $subfield){
					if(strpos($subfield,"&")>0){
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
				if(strpos($field,"&")>0){
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
	$output .= "</output>\n";
	return $output;
}

?>