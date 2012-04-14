<?php

$od_count = 0;

function od_have_data() {
	$return = false;
	global $od_global;
	if($od_global->status!="Data set"){
		$od_global->get_data();
	}
	if($od_global->current_row!==false){
		$next_item = $od_global->current_row + 1;
		if($next_item<=count($od_global->data)){
			$od_global->current_row = $next_item;
			$return = true;
		}
	} else {
		if(count($od_global->data)>0){
			$od_global->current_row = 0;
			$return = true;
		}
	}
	return $return;
}

function od_the_row($return_type = "html-table", $return_method = "echo") {
	global $od_global;
	$return = false;
	if($od_global->current_row){
		$data = $od_global->current_row();
		switch ($return_type) :
			case "html-table":
				$return = drk_implode("</td><td>", $data, "<td>", "</td>");
				break;
			case "html-list":
				$return = drk_implode("</li><li>", $data, "<li>", "</li>");
				break;
			default:
				$return = $data;
				break;
		endswitch;
	}
	return od_return ($return, $return_method) ;
}

function od_the_field($field, $return_method = "echo") {
	global $od_global;
	$return = false;
	if($od_global->current_row){
		$return = $od_global->get_field($field);
	}
	return od_return ($return, $return_method) ;
}

function od_the_rss_field($field, $return_method = "echo") {
	global $od_global;
	$return = false;
	if($od_global->current_row){
		$return = $od_global->get_field_meta ( $field , "rss" );
	}
	return od_return ($return, $return_method) ;
}

function od_the_geog_field($field, $return_method = "echo") {
	global $od_global;
	$return = false;
	if($od_global->current_row){
		$return = $od_global->get_field_meta ( $field , "geog" );
	}
	return od_return ($return, $return_method) ;
}

function od_the_columns($return_type = "html-table", $return_method = "echo") {
	global $od_global;
	$return = false;
	if($od_global->table_config["columns"]){
		$data = $od_global->table_config["columns"];
		foreach($data as &$d){
			$d = $d["nice_name"];
		}
		switch ($return_type) {
			case "html-table":
				$return = drk_implode("</th><th>", $data, "<th>", "</th>");
				break;
			case "html-list":
				$return = drk_implode("</li><li>", $data, "<li>", "</li>");
				break;
			default:
				$return = $data;
				break;
		}
	}
	return od_return ($return, $return_method) ;
}

function od_number_rows($return_method = "echo") {
	global $od_global;
	$return = false;
	$return = count($od_global->data);
	return od_return ($return, $return_method) ;
}

function od_total_number_rows($return_method = "echo") {
	global $od_global;
	$return = false;
	$return = $od_global->get_number_rows();
	return od_return ($return, $return_method) ;
}

function od_get_filters($before = '', $after = '' , $return_method = "echo") {
	global $od_global;
	$return = false;
	$return = $od_global->get_filters();
	if($return!=""){
		$return = $before . $od_global->get_filters() . $after;
	}
	return od_return ($return, $return_method);
}

function od_get_filter_form($return_method = "echo") {
	global $od_global;
	$return = '<form action="' . get_bloginfo('url') . '?od_data=data" method="GET">';
	foreach ( $od_global->table_config["filter_columns"] as $colkey=>$filt ) {
		$return .= "<p>" . $od_global->table_config["columns"][$colkey]["nice_name"] . ": </br>";
		$return .= "<select name=\"od_filter[$colkey][]\"";
		if($filt["filter_type"]=="multiple"){
			$return .= "multiple=\"multiple\"";
		}
		$return .= ">\n";
		$od_selected_select = "";
		$od_other_select = "";
		foreach($filt["categories"] as $catkey=>$rec){
			$od_already_shown = 0;
			if(isset($od_global->filters[$colkey])){
				foreach($od_global->filters[$colkey] as $filtkey=>$filtvalue){
					if(strtolower($filtvalue)==strtolower($rec["name"])){
						$od_selected_select .= "<option value=\"" . $colkey . "\" selected=\"selected\">" . substr($rec["name"],0,35) . " [" . $rec["records"] . "]</option>\n"; // names are cropped at 35 characters
						$od_already_shown = 1;
					}
				}
			}
			if($od_already_shown==0){
				$od_other_select .= "<option value=\"" . $catkey . "\">" . substr($rec["name"],0,35) . " [" . $rec["records"] . "]</option>\n"; // names are cropped at 35 characters
			}
		}
		$return .= $od_selected_select;
		if($filt["filter_type"]=="single"){
			$return .= "<option value=\"\"></option>\n";
		}
		$return .= $od_other_select;
		$return .= "</select>\n";
	}
	$return .= '<p>Search: </br><input type="text" name="od_search"';
	if(isset($od_object->search)){
		$return .= " value=\"".implode(" OR ",$od_object->search)."\"";
	}
	$return .= "></p>\n"; // search also allowed
	$return .= '<input type="hidden" name="od_data" value="data">' . "\n";
	$return .= '<input type="submit" value="Select records">' . "\n";
	$return .= '</form>' . "\n";
	return od_return ($return, $return_method);
}

function od_return ($return, $return_method) {
	if($return_method=="echo"){
		if ( is_array ( $return ) ) {
			echo drk_implode_recursive ( " " , $return );
		} else if ( is_string( $return ) ) {
			echo $return;
		} else {
			return $return;
		}
	} else {
		return $return;
	}
}