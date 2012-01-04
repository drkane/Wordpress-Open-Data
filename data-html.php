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

function od_display_data($od_object,$od_type="data"){
	header("Content-type: text/html");
	function od_set_page_title($orig_title) {
		return "Data | ";  // set the page title (could be improved, eg based on filters. Might be something the user wants to set
	}
	add_filter('wp_title', od_set_page_title);
	get_header(); // include the standard wordpress header, as defined by the theme
	echo "<div id=\"primary\">\n";
	echo "<div id=\"content\" role=\"main\">\n";
	echo "<article id=\"post-2\" class=\"page type-page status-publish hentry\">\n";
	if($od_type=="item"){
		echo $od_object->apply_template(); // use the item template (or use default template if no template set)
		echo "<a href=\"" . od_change_datatype("csv") . "\">CSV</a> | "; // include buttons to change the format of the data
		echo "<a href=\"" . od_change_datatype("json") . "\">JSON</a> | ";
		echo "<a href=\"" . od_change_datatype("txt") . "\">TXT</a> | ";
		echo "<a href=\"" . od_change_datatype("rss") . "\">RSS</a> | ";
		echo "<a href=\"" . od_change_datatype("xml") . "\">XML</a>";
		
		echo "</article><!-- #post-0 -->\n";

		echo "</div><!-- #content -->\n";
		echo "</div><!-- #primary -->\n";
		
		echo $od_object->apply_template("sidebar"); // include the sidebar template (or standard sidebar if no sidebar template set)
		
	} else {
		$od_data = $od_object->get_data(); // get the data
		//echo "<header class=\"entry-header\">\n"; // don't currently include a header for the data, but could contain something useful
		//echo "<h1 class=\"entry-title\">Data</h1>\n";
		//echo "</header><!-- .entry-header -->\n";

		echo "<div class=\"entry-content\">\n";
		echo "<p>" . number_format(count($od_data)) . " items found matching your criteria. <a href=\"" . od_change_datatype("csv") . "\">Download data as CSV</a> or <a href=\"" . od_change_datatype("html","map") . "\">view on a map</a>.</p>\n"; // allow quick access to downloading data as a CSV file - could be more flexible
		echo $od_object->get_filters(); // find the filters that have been used (and output them)
		echo "<table class=\"open-data\">\n";
		$od_rowcount = 0;
		foreach($od_data as $c){
			echo "<thead>\n";
			echo "<tr class=\"open-data\">\n";
			if($od_rowcount==0){ // include a header row
				foreach($c as $od_key=>$od_value){ 
					if($od_object->tables[$od_object->selected_table]["columns"][$od_key]["is_html"]==1){
						echo "<th>". $od_object->tables[$od_object->selected_table]["columns"][$od_key]["nice_name"] ."</th>\n";
					}
					if($od_object->tables[$od_object->selected_table]["columns"][$od_key]["is_id"]==1){
						$od_id_column = $od_key;
					}
				}
				echo "</tr>\n";
				echo "</thead>\n";
				echo "<tbody>\n";
				$od_rowcount++;
			}
			$od_item_url = "";
			if($od_object->selected_table!=$od_object->default_table){$od_item_url .= $od_object->selected_table . "/";}
			$od_item_url .= "data/item/" . urlencode(strtolower($c[$od_id_column])); // work out the stem url for each item
			foreach($c as $od_key=>$od_value){
				$col_properties = $od_object->tables[$od_object->selected_table]["columns"][$od_key];
				if($col_properties["is_html"]==1){
					if(is_array($od_value)){ // if the value is an array then implode the values, breaking each value onto its own line
						foreach($od_value as &$column){
							$orig_col = $column;
							$column = "<a href=\"";
							$column .= get_bloginfo('url') . "/";
							if($od_object->selected_table!=$od_object->default_table){$column .= $od_object->selected_table;}
							$column .= "data/filter/$od_key/" . urlencode(strtolower($orig_col));
							$column .= "\">$orig_col</a>";
							if($od_object->categories[$od_key]["records"][$orig_col]["count"]>0)
								$column .= " [" . $od_object->categories[$od_key]["records"][$orig_col]["count"] . "]"; // include the number of times each item appears
						}
						echo "<td>";
						echo implode(";<br />",$od_value);
						echo "</td>\n";
					} else {
						if($col_properties["is_id"]==1){ // if it's the ID column then include a link
							echo "<td>";
							echo "<a href=\"";
							echo get_bloginfo('url') . "/";
							echo $od_item_url;
							echo "\">$od_value</a>";
							echo "</td>\n";
						} else if($col_properties["is_id"]==2){ // if it's meant to point to the ID column then include a link
							echo "<td>";
							echo "<a href=\"";
							echo get_bloginfo('url') . "/";
							echo $od_item_url;
							echo "\">$od_value</a>";
							echo "</td>\n";
						} else if($col_properties["display_type"]=="currency"){ // if it's meant to be currency then format nicely (could do with being able to specify currencyt.
							echo "<td style=\"text-align:right\">";
							if($od_value!=""){
								echo "&pound;" . number_format((int)$od_value);
							}
							echo "</td>\n";
						} else if($col_properties["display_type"]=="numeric"){ // if numeric then format nicely (could do with being able to change formats)
							echo "<td style=\"text-align:right\">";
							if($od_value!=""){
								echo number_format((int)$od_value);
							}
							echo "</td>\n";
						} else if($col_properties["linked_data_url"]!=""){ // if the column can be linked to another data service then include as a URL (link replaces the term {{field}}
							echo "<td>";
							echo "<a href=\"";
							echo str_replace("{{field}}",$od_value,$col_properties["linked_data_url"]);
							echo "\">$od_value</a>";
							echo "</td>\n";
						} else { // otherwise output the value
							echo "<td>";
							if(strlen($od_value)>100){ // if it's very long then truncate and link
								echo substr($od_value,0,strpos($od_value," ",100))." ";
								echo "<a href=\"";
								echo get_bloginfo('url') . "/";
								if($od_object->selected_table!=$od_object->default_table){echo $od_object->selected_table;}
								echo $od_item_url;
								echo "\">[...]</a>";
							} else {
								echo $od_value;
							}
							echo "</td>\n";
						}
					}
				}
			}
			echo "</tr>\n";
		}
		echo "</tbody>\n";
		echo "</table>\n";
		echo "<a href=\"" . od_change_datatype("csv") . "\">CSV</a> | "; // allow the data to be downloaded in different formats
		echo "<a href=\"" . od_change_datatype("json") . "\">JSON</a> | ";
		echo "<a href=\"" . od_change_datatype("txt") . "\">TXT</a> | ";
		echo "<a href=\"" . od_change_datatype("rss") . "\">RSS</a> | ";
		echo "<a href=\"" . od_change_datatype("xml") . "\">XML</a> | ";
		echo "<a href=\"" . od_change_datatype("html","map") . "\">Map</a>";
		echo "</div><!-- .entry-content -->\n";
		echo "</article><!-- #post-0 -->\n";

		echo "</div><!-- #content -->\n";
		echo "</div><!-- #primary -->\n";
		echo "<div id=\"secondary\" class=\"widget-area\" role=\"complementary\">\n";
		echo "<aside id=\"text-9\" class=\"widget widget_text\">\n";
		echo "<form action=\"". get_bloginfo('url') ."?od_data=data\" method=\"GET\">\n"; // include the possible filters as a sidebar
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
		echo "<input type=\"hidden\" name=\"od_data\" value=\"data\">\n";
		echo "<input type=\"submit\" value=\"Select records\">\n";
		echo "<form>\n";
		echo "</aside>\n";
		echo "</div><!-- #secondary -->\n";

	}
	
	get_footer(); // include the standard wordpress theme footer

}

?>