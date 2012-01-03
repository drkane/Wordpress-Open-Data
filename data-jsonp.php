<?php

/**
 * Used to create a json output of data requested by the user, including a callback function
 *
 * @package WordPressOpenData
 */

function od_display_data($od_object,$od_type="data"){
	if($od_type=="item"){
		$od_data = $od_object->get_item();
	} else {
		$od_data = $od_object->get_data();
	}
	header("Content-type: application/jsonp");
	$callback = "jsonp("; // default callback is jsonp
	if(isset($_REQUEST["od_callback"])){ // replaces the default callback if it exists
		$callback = $_REQUEST["od_callback"] . "("; 
	}
	$endcallback =  ")";
	$output = $callback . json_encode($od_data) . $endcallback;
	return $output;
}

?>