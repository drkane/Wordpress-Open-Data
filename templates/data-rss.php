<?php

/**
 * Used to create an RSS feed of data requested by the user
 *
 * @package WordPressOpenData
 */
function od_display_data($od_object){
		if(strpos($od_desc,"&")>0){ // if there are &s in the description then use CDATA