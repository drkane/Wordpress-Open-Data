<?php

/**
 * Used to create a json output of data requested by the user
 *
 * @package WordPressOpenData
 */

function od_display_data($od_object,$od_type="data"){
	if($od_type=="item"){
		$od_data = $od_object->get_item();
	} else {
		$od_data = $od_object->get_data();
	}
	header("Content-type: application/json");
	$output = json_encode($od_data); // use PHP's built-in JSON function
	return $output;
}

?>