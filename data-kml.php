<?php

/**
 * Used to create a kml output of data requested by the user
 * Needs to be written from scratch
 *
 * @package WordPressOpenData
 */

function od_display_data($od_object){
	$od_data = $od_object->get_data();
	header("Content-type: application/vnd.google-earth.kml+xml");
	$output = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; // xml header
	$output .= "<kml xmlns=\"http://www.opengis.net/kml/2.2\">\n"; 
	$output .= "<Document>\n";
	$output .= "<name>Voluntary Sector Cuts Map</name>\n";
	$output .= "<description>A map of Voluntary Sector Cuts</description>\n";
	$output .= "<style id=\"defaultStyle\">\n";
	$output .= "<IconStyle>\n";
	$output .= "\t<Icon>\n";
	$output .= "\t\t<href>http://voluntarysectorcuts.org.uk/icon.png</href>\n";
	$output .= "\t</Icon>\n";
	$output .= "</IconStyle>\n";
	$output .= "</style>\n";
	foreach($od_data as $c){
		$od_timestamp = $c["timestamp"];
		$od_timestamp = strtotime($od_timestamp);
		$od_timestamp = strftime('%a, %d %b %Y %H:%M:%S GMT',$od_timestamp);
		if($c["longitude"]!=""&&$c["latitude"]!=""){
			$output .= "\t<Placemark>\n";
			if(strpos($c["organisation"],"&")>0){
				$cdata = "<![CDATA[";
				$cdataend = "]]>";
			} else {
				$cdata = $cdataend = "";
			}
			$output .= "\t\t<name>$cdata".$c["organisation"]."$cdataend</name>\n";
			$description = "";
			if($c["amount"]!=""){
				$description .= "<p>&pound;" . number_format((int)$c["amount"]) . "</p>";
			}
			if(strlen($c["Description"])>100){
				$description .= substr($c["description"],0,strpos($c["description"]," ",100)) . "...";
			} else {
				$description .= $c["description"];
			}
			$description .= "<p><a href=\"$stem"."cut/".$c["name"]."\">More on this cut</a></p>";
			$output .= "\t\t<description><![CDATA[$description]]></description>\n";
			$output .= "\t\t<Point>\n";
			$output .= "\t\t\t<coordinates>".$c["longitude"].",".$c["latitude"]."</coordinates>\n";
			$output .= "\t\t</Point>\n";
			$output .= "\t\t<styleUrl>#defaultStyle</styleUrl>\n";
			$output .= "\t</Placemark>\n";
		}
	}
	$output .= "</Document>\n";
	$output .= "</kml>\n";
	$output = trim($output);
	return $output;
}

?>