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
	$kml_field_title = $od_object->get_field("title","rss"); // get the field used as RSS title
	$kml_field_description = $od_object->get_field("description","rss"); // get the field used as RSS description
	$kml_field_guid = $od_object->get_field("guid","rss"); // get the field used as RSS guid
	$kml_field_timestamp = $od_object->get_field("timestamp","rss"); // get the field used as RSS timestamp (this is needed)
	$kml_field_id = $od_object->get_field("id","rss"); // get the field used as RSS id
	$geo_field_latlng = $od_object->get_field("latlng","geog"); // get the field used as latitude and longitude
	$geo_field_lat = $od_object->get_field("lat","geog"); // get the field used as latitude
	$geo_field_lng = $od_object->get_field("lng","geog"); // get the field used as longitude
	foreach($od_data as $c){
		$od_timestamp = $c["timestamp"];
		$od_timestamp = strtotime($od_timestamp);
		$od_timestamp = strftime('%a, %d %b %Y %H:%M:%S GMT',$od_timestamp);
		if($c[$geo_field_lng]!=""&&$c[$geo_field_lat]!=""){
			$output .= "\t<Placemark>\n";
			if(strpos($c[$kml_field_title],"&")>0){
				$cdata = "<![CDATA[";
				$cdataend = "]]>";
			} else {
				$cdata = $cdataend = "";
			}
			$output .= "\t\t<name>$cdata".$c[$kml_field_title]."$cdataend</name>\n";
			$description = "";
			if($c["amount"]!=""){
				$description .= "<p>&pound;" . number_format((int)$c["amount"]) . "</p>";
			}
			if($od_object->selected_table==$od_object->default_table){
				$od_link = get_bloginfo('url') . "/data/item/" . trim($c[$kml_field_id]);
			} else {
				$od_link = get_bloginfo('url') . "/" . $od_object->selected_table . "/item/" . trim($c[$kml_field_id]);
			}
			if(strlen($c["Description"])>100){
				$description .= substr($c[$kml_field_description],0,strpos($c[$kml_field_description]," ",100)) . "...";
			} else {
				$description .= $c[$kml_field_description];
			}
			$description .= "<p><a href=\"$od_link\">More on this cut</a></p>";
			$output .= "\t\t<description><![CDATA[$description]]></description>\n";
			$output .= "\t\t<Point>\n";
			$output .= "\t\t\t<coordinates>".$c[$geo_field_lng].",".$c[$geo_field_lat]."</coordinates>\n";
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