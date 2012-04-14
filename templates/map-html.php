<?php

/**
 * Used to create an html map for data requested by the user
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

	function od_set_page_title($orig_title) {
		return "Map | ";  // set the page title (could be improved, eg based on filters. Might be something the user wants to set
	}
	add_filter('wp_title', 'od_set_page_title');
	get_header(); // include the standard wordpress header, as defined by the theme ?>

<div id="primary">
	<div id="content" role="main">
		<article id="post-2" class="page type-page status-publish hentry">
			<div class="entry-content">
				<p><?php echo number_format(od_number_rows()); ?> items found matching your criteria. Items without geographic information won't be plotted on the map.</p>
				<p><?php echo od_filters(); ?>
				<p>Link to map: <a href=\"$od_maplink\">$od_maplink</a></p>";
	echo "<iframe width=\"600\" height=\"600\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"http://www.google.co.uk/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=".urlencode($od_maplink)."&amp;aq=&amp;vpsrc=0&amp;ie=UTF8&amp;t=m&amp;output=embed\"></iframe>";
	echo "<br />";
		echo "<a href=\"" . od_change_datatype("csv") . "\" title=\"Comma Separated Values: compatible with spreadsheet programs like Microsoft Excel\">CSV</a> | "; // allow the data to be downloaded in different formats
		echo "<a href=\"" . od_change_datatype("html") . "\" title=\"View in a table on a web page\">Table</a> | ";
		echo "<a href=\"" . od_change_datatype("html","map") . "\" title=\"View in a map\">Map</a> | ";
		echo "<a href=\"http://www.google.co.uk/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q=".urlencode(od_change_datatype("kml"))."&amp;aq=&amp;vpsrc=0&amp;ie=UTF8&amp;t=m\" target=\"_blank\" title=\"View in Google Maps\">Google Maps</a>";
		echo "<br />";
		echo "<a href=\"" . od_change_datatype("json") . "\" title=\"JavaScript Object Notation\">JSON</a> | ";
		echo "<a href=\"" . od_change_datatype("txt") . "\" title=\"A text file in CSV format\">TXT</a> | ";
		echo "<a href=\"" . od_change_datatype("rss") . "\" title=\"Really Simple Syndication: used in feed readers such as Google Reader\">RSS</a> | ";
		echo "<a href=\"" . od_change_datatype("xml") . "\" title=\"eXtensible Markup Langauge\">XML</a> | ";
		echo "<a href=\"" . od_change_datatype("xml") . "\" title=\"Keyhole Markup Langauge\">KML</a> | ";
		echo "<a href=\"" . od_change_datatype("xml") . "\" title=\"Geographical extension of RSS\">geoRSS</a>";
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
			if($col_properties["filter_type"]=="search"){
				echo "<input type=\"text\" name=\"od_filter[$catkey][]\"";
				if(isset($od_object->filters[$catkey])){
					echo " value=\"".$od_object->filters[$catkey][0]."\"";
				}
				echo ">";
			} else {
				echo "<select name=\"od_filter[$catkey][]\"";
				if($col_properties["filter_type"]=="multiple"){
					echo "multiple=\"multiple\"";
				}
				echo ">\n";
				$od_selected_select = "";
				$od_other_select = "";
				foreach($cat["records"] as $rec){
					$od_already_shown = 0;
					if(isset($od_object->filters[$catkey])){
						foreach($od_object->filters[$catkey] as $filtkey=>$filtvalue){
							if(strtolower($filtvalue)==strtolower($rec["name"])){
								$od_selected_select .= "<option value=\"".$rec["name"]."\" selected=\"selected\">".substr($rec["name"],0,35)." [".$rec["count"]."]</option>\n"; // names are cropped at 35 characters
								$od_already_shown = 1;
							}
						}
					}
					if($od_already_shown==0){
						$od_other_select .= "<option value=\"".$rec["name"]."\">".substr($rec["name"],0,35)." [".$rec["count"]."]</option>\n"; // names are cropped at 35 characters
					}
				}
				echo $od_selected_select;
				if($col_properties["filter_type"]=="single"){
					echo "<option value=\"\"></option>\n";
				}
				echo $od_other_select;
				echo "</select>\n";
			}
			echo "</p>\n";
		}
		echo "<p>Search: </br><input type=\"text\" name=\"od_search\"";
		if(isset($od_object->search)){
			echo " value=\"".implode(" OR ",$od_object->search)."\"";
		}
		echo "></p>\n"; // search also allowed
	echo "<input type=\"hidden\" name=\"od_data\" value=\"map\">\n";
	echo "<input type=\"submit\" value=\"Select records\">\n";
	echo "<form>\n";
	echo "</aside>\n";
	echo "</div><!-- #secondary -->\n";
	
	get_footer(); // include the standard wordpress theme footer

}

?>