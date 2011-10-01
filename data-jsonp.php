<?php

function od_display_data($od_object,$od_type="data"){
	if($od_type=="item"){
		$od_data = $od_object->get_item();
	} else {
		$od_data = $od_object->get_data();
	}
	header("Content-type: application/jsonp");
	$callback = "jsonp(";
	if(isset($_REQUEST["callback"])){
		$callback =  $_REQUEST["callback"] . "(";
	}
	$endcallback =  ")";
	$output = $callback . json_encode($od_data) . $endcallback;
	return $output;
}

?>