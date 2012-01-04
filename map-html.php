<?php

/**
 * Used to create an html page of data requested by the user
 *
 * @package WordPressOpenData
 */

// Needs:
// which columns to display (max: 5)
// How to display them
// what filters to include
// what the title of the data is
// allow customised table row templates
// allow a choice of currencies
// allow a choice of number formats
// allow data download options to be customised
// 


function od_display_data($od_object,$od_type="map"){

	header("Content-type: text/html");
	
	$od_maplink = od_change_datatype("kml");
	
	function od_set_page_title($orig_title) {
		return "Map | ";  // set the page title (could be improved, eg based on filters. Might be something the user wants to set
	}
	add_filter('wp_title', 'od_set_page_title');
	get_header(); // include the standard wordpress header, as defined by the theme
	echo "<div id=\"primary\" onload=\";\">\n";
	echo "<div id=\"content\" role=\"main\">\n";
	echo "<article id=\"post-2\" class=\"page type-page status-publish hentry\">\n";
	$od_data = $od_object->get_data(); // get the data
	//echo "<header class=\"entry-header\">\n"; // don't currently include a header for the data, but could contain something useful
	//echo "<h1 class=\"entry-title\">Data</h1>\n";
	//echo "</header><!-- .entry-header -->\n";

	echo "<div class=\"entry-content\">\n";
	echo "<p>" . number_format(count($od_data)) . " items found matching your criteria. Items without geographic information won't be plotted on the map.</p>\n"; // allow quick access to downloading data as a KML file - could be more flexible
	echo $od_object->get_filters(); // find the filters that have been used (and output them)
	
	echo "<iframe width=\"600\" height=\"600\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"http://www.google.co.uk/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=".urlencode($od_maplink)."&amp;aq=&amp;vpsrc=0&amp;ie=UTF8&amp;t=m&amp;output=embed\"></iframe>";
	echo "<br />";
	echo "<a href=\"" . od_change_datatype("csv") . "\">CSV</a> | "; // allow the data to be downloaded in different formats
	echo "<a href=\"" . od_change_datatype("json") . "\">JSON</a> | ";
	echo "<a href=\"" . od_change_datatype("txt") . "\">TXT</a> | ";
	echo "<a href=\"" . od_change_datatype("rss") . "\">RSS</a> | ";
	echo "<a href=\"" . od_change_datatype("xml") . "\">XML</a> | ";
	echo "<a href=\"" . od_change_datatype("html") . "\">Table</a> | ";
	echo "<a href=\"http://www.google.co.uk/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q=".urlencode(od_change_datatype("kml"))."&amp;aq=&amp;vpsrc=0&amp;ie=UTF8&amp;t=m\" target=\"_blank\">Google Maps</a>";
	echo "</div><!-- .entry-content -->\n";
	echo "</article><!-- #post-0 -->\n";

	echo "</div><!-- #content -->\n";
	echo "</div><!-- #primary -->\n";
	echo "<div id=\"secondary\" class=\"widget-area\" role=\"complementary\">\n";
	echo "<aside id=\"text-9\" class=\"widget widget_text\">\n";
	echo "<form action=\"". get_bloginfo('url') ."\" method=\"GET\">\n"; // include the possible filters as a sidebar
	foreach($od_object->categories as $catkey=>$cat){
		$col_properties = $od_object->tables[$od_object->selected_table]["columns"][$catkey];
		echo "<p>" . $col_properties["nice_name"] . ": </br>";
		echo "<select name=\"od_filter[".$catkey."][]\"";
		if($col_properties["filter_type"]=="multiple"){
			echo "multiple=\"multiple\"";
		}
		echo ">\n";
		if($col_properties["filter_type"]=="single"){
			echo "<option value=\"\"></option>\n";
		}
		foreach($cat["records"] as $rec){
			echo "<option value=\"".$rec["name"]."\">".substr($rec["name"],0,35)." [".$rec["count"]."]</option>\n"; // names are cropped at 35 characters
		}
		echo "</select></p>\n";
	}
	echo "<p>Search: </br><input type=\"text\" name=\"od_search\"></p>\n"; // search also allowed
	echo "<input type=\"hidden\" name=\"od_data\" value=\"map\">\n";
	echo "<input type=\"submit\" value=\"Select records\">\n";
	echo "<form>\n";
	echo "</aside>\n";
	echo "</div><!-- #secondary -->\n";
	
	get_footer(); // include the standard wordpress theme footer

}

?>