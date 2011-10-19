<?php
/*
Plugin Name: Wordpress Open Data
Description: Adds open data functionality to a Wordpress-based site.
Plugin URI: http://drkane.co.uk/projects/wordpress-open-data
Version: 0.1
Author: David Kane
Author URI: http://drkane.co.uk/
License: GPLv2
*/

include_once("class.od_object.php");
include_once("class.od_object_maintenance.php");

function od_queryvars($qvars){
	$qvars[] = "od_data";
	$qvars[] = "od_table";
	$qvars[] = "od_filter";
	$qvars[] = "od_id";
	$qvars[] = "od_filetype";
	$qvars[] = "od_search";
	return $qvars;
}

function od_template_redirect(){
	global $wp_query, $wpdb;
	
	$od_filetypes = array(
		"html"=>"Content-type: text/html",
		"txt"=>"Content-type: text/plain",
		"csv"=>"Content-type: text/csv",
		"json"=>"Content-type: application/json",
		"jsonp"=>"Content-type: application/jsonp",
		"xml"=>"Content-type: text/xml",
		"rss"=>"Content-type: application/rss+xml",
		"kml"=>"Content-type: application/vnd.google-earth.kml+xml"
	//	"xls"=>"Content-type: application/vnd.ms-excel"
		);
	
	if(isset($wp_query->query_vars["od_data"])){
		$od_data_type = $wp_query->query_vars["od_data"];
		if($od_data_type!="map"){
			$od_data_type="data";
		}
		$od_filetype = "html";
		$od_data = new od_object();
		if(isset($wp_query->query_vars["od_table"])){
			$od_data->select_table($wp_query->query_vars["od_table"]);
		}
		if(isset($_REQUEST["od_filter"])){
			$od_data->set_filters($_REQUEST["od_filter"]);
		}
		if(isset($_REQUEST["od_search"])){
			$od_data->set_search($_REQUEST["od_search"]);
		}
		if(isset($wp_query->query_vars["od_filetype"])){
			$od_filetype = $wp_query->query_vars["od_filetype"];
			if($od_data_type=="map"){
				$od_filetype = "html";
			}
		}
		if(isset($wp_query->query_vars["od_id"])){
			$od_id = $wp_query->query_vars["od_id"];
			$od_data_type = "item";
			if($od_filetype=="rss"){
				$od_filetype="html";
			}
		}
		$od_include = "/data-$od_filetype.php";
		include($od_include);
		if($od_data_type=="item"){
			$od_data->set_item_id($od_id);
			echo od_display_data($od_data,"item");
			header("HTTP/1.1 200 OK");
		} else if($od_data_type=="map") {
			$od_include = "/map-html.php";
			include($od_include);
			echo od_display_data($od_data);
			header("HTTP/1.1 200 OK");
		} else {
			echo od_display_data($od_data);
			header("HTTP/1.1 200 OK");
		}
		exit;
	}
}

function opendata_dir_rewrite($wp_rewrite) {
    $feed_rules = array(
        'data/filter/([^/]+)/([^/]+)\.([a-z]+)' => 'index.php?od_data=data&od_filetype=$3&od_filter[$1]=$2',
        'data/filter/([^/]+)/([^/]+)' => 'index.php?od_data=data&od_filetype=html&od_filter[$1]=$2',
        'data/search/([^/]+)\.([a-z]+)' => 'index.php?od_data=data&od_filetype=$2&od_search=$1',
        'data/search/([^/]+)' => 'index.php?od_data=data&od_filetype=html&od_search=$1',
        'data/item/([^/]+)\.([a-z]+)' => 'index.php?od_data=item&od_filetype=$2&od_id=$1',
        'data/item/([^/]+)' => 'index.php?od_data=item&od_filetype=html&od_id=$1',
        'data\.([a-z]+)' => 'index.php?od_data=data&od_filetype=$1',
        'data' => 'index.php?od_data=data&od_filetype=html',
        '([^/]+)/data/filter/([^/]+)/([^/]+)\.([a-z]+)' => 'index.php?od_data=data&od_table=$1&od_filetype=$4&od_filter[$2]=$3',
        '([^/]+)/data/filter/([^/]+)/([^/]+)' => 'index.php?od_data=data&od_table=$1&od_filetype=html&od_filter[$2]=$3',
        '([^/]+)/data/search/([^/]+)\.([a-z]+)' => 'index.php?od_data=data&od_table=$1&od_filetype=$3&od_search=$2',
        '([^/]+)/data/search/([^/]+)' => 'index.php?od_data=data&od_table=$1&od_filetype=html&od_search=$2',
        '([^/]+)/data/item/([^/]+)\.([a-z]+)' => 'index.php?od_data=item&od_table=$1&od_filetype=$3&od_id=$2',
        '([^/]+)/data/item/([^/]+)' => 'index.php?od_data=item&od_table=$1&od_filetype=html&od_id=$2',
        '([^/]+)/data\.([a-z]+)' => 'index.php?od_data=data&od_table=$1&od_filetype=$2',
        '([^/]+)/data' => 'index.php?od_data=data&od_table=$1&od_filetype=html'
    );

	$wp_rewrite->non_wp_rules = $feed_rules;
}

// Hook in.
add_filter( 'generate_rewrite_rules', 'opendata_dir_rewrite' );

add_action('query_vars', 'od_queryvars');
add_action('template_redirect', 'od_template_redirect');

add_action('admin_menu', 'od_menu');
add_action( 'add_meta_boxes', 'od_nav_menu_metabox' );

function od_menu() {
	add_menu_page( "Data", "Data", "manage_options", "open-data", "od_options_main" , null, 32 );
	add_submenu_page( "open-data", "Add New", "Add New", "manage_options", "open-data-tables", "od_options_table"); 
	add_submenu_page( "open-data", "Permalinks", "Permalinks", "manage_options", "open-data-permalinks", "od_options_permalinks"); 
}

function od_options_main() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	$od_maintain = new od_object_maintenance();
	echo '<div class="wrap">';
	if(isset($_REQUEST["od_table"])){
		echo '<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br /></div><h2>Edit data table <a href="admin.php?page=open-data-tables" class="add-new-h2">Add New Data Table</a> </h2> ';
		if(isset($_REQUEST["od_table_edit"])){

		}
		$od_table_data = $od_maintain->tables[$_REQUEST["od_table"]];
		if(count($od_table_data)>0){
			$od_page = "<form name=\"data\" action=\"admin.php?page=open-data&od_table=".$od_table_data["name"]."\" method=\"post\" id=\"data\">";
			$od_page .= "<h3>Table details</h3>";
			$od_page .= "<input type=\"hidden\" name=\"od_table_edit[is_default]\" tabindex=\"1\" value=\"".$od_table_data["is_default"]."\" id=\"title\" class=\"hidden\" /><br/>";
			$od_page .= "<input type=\"text\" name=\"od_table_edit[nicename]\" size=\"50\" tabindex=\"1\" value=\"".$od_table_data["nicename"]."\" id=\"title\" autocomplete=\"off\" /><br/>";
			$od_page .= "Description: <textarea name=\"od_table_edit[description]\" rows=\"6\" cols=\"35\"></textarea>";
			$od_page .= "<h3>Data columns</h3>";
			$od_page .= "<table>";
			$od_page .= "<thead>";
			$od_page .= "<tr>";
			$od_page .= "<th>Name</th>";
			$od_page .= "<th>Description</th>";
			$od_page .= "<th>mysql</th>";
			$od_page .= "<th>Display</th>";
			$od_page .= "<th>Filter</th>";
			$od_page .= "<th>Geography</th>";
			$od_page .= "<th>Open data</th>";
			$od_page .= "<th>Identifier</th>";
			$od_page .= "<th>Timestamp</th>";
			$od_page .= "<th>Searchable</th>";
			$od_page .= "<th>Linked data URL</th>";
			$od_page .= "</tr>";
			$od_page .= "</thead>";
			foreach($od_table_data["columns"] as $od_col){
				$od_page .= "<tr>";
				$od_page .= "<td><input name=\"od_table_edit[columns][".$od_col["column_name"]."][nice_name]\" type=\"text\" value=\"".$od_col["nice_name"]."\" /></td>";
				$od_page .= "<td><input name=\"od_table_edit[columns][".$od_col["column_name"]."][description]\" type=\"text\" value=\"".$od_col["description"]."\" /></td>";
				$od_page .= "<td><input name=\"od_table_edit[columns][".$od_col["column_name"]."][mysql_type]\" type=\"text\" value=\"".$od_col["mysql_type"]."\" /></td>";
				$od_page .= "<td><input name=\"od_table_edit[columns][".$od_col["column_name"]."][display_type]\" type=\"text\" value=\"".$od_col["display_type"]."\" /></td>";
				$od_page .= "<td><select name=\"od_table_edit[columns][".$od_col["column_name"]."][filter_type]\">";
				foreach($od_maintain->filter_types as $filter_type){
					$od_page .= "<option";
					if($filter_type=="none") {
						$od_page .= " value=\"\"";
					} else { 
						$od_page .= " value=\"$filter_type\"";
						if($filter_type==$od_col["filter_type"]){$od_page .= "selected=\"selected\"";}
					}
					$od_page .= ">$filter_type</option>";
				}
				$od_page .= "</select></td>";
				$od_page .= "<td><select name=\"od_table_edit[columns][".$od_col["column_name"]."][geog_type]\">";
				foreach($od_maintain->geography_types as $geog_type){
					$od_page .= "<option";
					if($geog_type=="none") {
						$od_page .= " value=\"\"";
					} else { 
						$od_page .= " value=\"$geog_type\"";
						if($geog_type==$od_col["geog_type"]){$od_page .= "selected=\"selected\"";}
					}
					$od_page .= ">$geog_type</option>";
				}
				$od_page .= "</select></td>";
				$od_page .= "<td><input name=\"od_table_edit[columns][".$od_col["column_name"]."][is_open]\" type=\"checkbox\"";
				if($od_col["is_open"]==1){$od_page .= " checked=\"checked\"";}
				$od_page .= " /></td>";
				$od_page .= "<td><input name=\"od_table_edit[columns][".$od_col["column_name"]."][is_id]\" type=\"radio\" name=\"is_type\"";
				if($od_col["is_id"]==1){$od_page .= " checked=\"checked\"";}
				$od_page .= " /></td>";
				$od_page .= "<td><input name=\"od_table_edit[columns][".$od_col["column_name"]."][is_timestamp]\" type=\"radio\" name=\"is_timestamp\"";
				if($od_col["is_timestamp"]==1){$od_page .= " checked=\"checked\"";}
				$od_page .= " /></td>";
				$od_page .= "<td><input name=\"od_table_edit[columns][".$od_col["column_name"]."][is_search]\" type=\"checkbox\"";
				if($od_col["is_search"]==1){$od_page .= " checked=\"checked\"";}
				$od_page .= " /></td>";
				$od_page .= "<td><input name=\"od_table_edit[columns][".$od_col["column_name"]."][linked_data_url]\" type=\"text\" value=\"".$od_col["linked_data_url"]."\" /></td>";
				$od_page .= "</tr>";
			}
			$od_page .= "</table>";
			$od_page .= "<h3>Item template</h3>";
			$od_page .= "<textarea name=\"od_table_edit[item_template]\" rows=\"20\" cols=\"120\"></textarea><br />";
			$od_page .= "<input type=\"submit\" />";
			$od_page .= "</form>";
			if(isset($_REQUEST["action"])){
				if($_REQUEST["action"]=="trash"){
					$od_page = "<p>Are you sure you want to delete the table?</p>";
				}
			}
			echo $od_page;
		} else {
			echo "<p>Table not found</p>\n";
		}
	} else {
		echo '<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br /></div><h2>Data options <a href="admin.php?page=open-data-tables" class="add-new-h2">Add New Data Table</a> </h2> ';
		print_r($_REQUEST);
		echo "<table class=\"wp-list-table widefat fixed posts\" cellspacing=\"0\">";
		echo "<thead>";
		echo "<tr>";
		echo "<th scope='col' id='cb' class='manage-column column-cb check-column'  style=\"\"><input type=\"checkbox\" /></th>";
		echo "<th scope='col' id='title' class='manage-column column-title sortable desc'  style=\"\"><a href=\"admin.php?page=open-data&orderby=title&#038;order=asc\"><span>Title</span><span class=\"sorting-indicator\"></span></a></th>";
		echo "<th scope='col' id='default' class='manage-column column-tags'  style=\"\">Default</th>";
		echo "<th scope='col' id='columns' class='manage-column column-tags sortable desc'  style=\"\"><a href=\"admin.php?page=open-data&orderby=columns&#038;order=asc\"><span>Columns</span><span class=\"sorting-indicator\"></span></a></th>";
		echo "<th scope='col' id='records' class='manage-column column-tags sortable desc'  style=\"\"><a href=\"admin.php?page=open-data&orderby=records&#038;order=asc\"><span>Records</span><span class=\"sorting-indicator\"></span></a></th>";
		echo "</tr>";
		echo "</thead>";
		echo "<tfoot>";
		echo "<tr>";
		echo "<th scope='col' id='cb' class='manage-column column-cb check-column'  style=\"\"><input type=\"checkbox\" /></th>";
		echo "<th scope='col' id='title' class='manage-column column-title sortable desc'  style=\"\"><a href=\"admin.php?page=open-data&orderby=title&#038;order=asc\"><span>Title</span><span class=\"sorting-indicator\"></span></a></th>";
		echo "<th scope='col' id='default' class='manage-column column-tags'  style=\"\">Default</th>";
		echo "<th scope='col' id='columns' class='manage-column column-tags sortable desc'  style=\"\"><a href=\"admin.php?page=open-data&orderby=columns&#038;order=asc\"><span>Columns</span><span class=\"sorting-indicator\"></span></a></th>";
		echo "<th scope='col' id='records' class='manage-column column-tags sortable desc'  style=\"\"><a href=\"admin.php?page=open-data&orderby=records&#038;order=asc\"><span>Records</span><span class=\"sorting-indicator\"></span></a></th>";
		echo "</tr>";
		echo "</tfoot>";
		echo "<tbody id=\"the-list\">\n";
		foreach($od_maintain->tables as $t){
			echo "			<tr id='post-1' class='alternate author-self status-publish format-default iedit' valign=\"top\">\n";
			echo "			<th scope=\"row\" class=\"check-column\"><input type=\"checkbox\" name=\"post[]\" value=\"1\" /></th>\n";
			echo "					<td class=\"post-title page-title column-title\"><strong><a class=\"row-title\" href=\"admin.php?page=open-data&od_table=".$t["name"]."&action=edit\" title=\"Edit &#8220;".$t["nicename"]."&#8221;\">".$t["nicename"]."</a></strong>\n";
			echo "<p>".$t["description"]."</p>\n";
			echo "<div class=\"row-actions\"><span class='edit'><a href=\"admin.php?page=open-data&od_table=".$t["name"]."&action=edit\" title=\"Edit this item\">Edit</a> | </span><span class='inline hide-if-no-js'><a href=\"#\" class=\"editinline\" title=\"Edit this item inline\">Quick&nbsp;Edit</a> | </span><span class='trash'><a class='submitdelete' title='Move this item to the Trash' href='admin.php?page=open-data&od_table=".$t["name"]."&action=trash'>Trash</a> | </span><span class='view'><a href=\"/?p=1\" title=\"View &#8220;Hello world!&#8221;\" rel=\"permalink\">View</a></span></div></td>\n";
			echo "					<td class=\"categories column-categories\"><input type=\"radio\" name=\"default\" ";
			if($t["is_default"]==1){ echo "checked=\"checked\" ";}
			echo "/></td>\n";
			echo "					<td class=\"tags column-tags\">".count($t["columns"])."</td>\n";
			echo "					<td class=\"tags column-tags\">".$t["rows"]."</td>\n";
			echo "</tr>\n";
		}
		echo "	</tbody>\n";
		echo "</table>";
	}
	echo "</div>";
}

function od_options_table() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	$od_maintain = new od_object_maintenance();
	echo '<div class="wrap">';
	echo '<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br /></div><h2>Add new data table</h2> ';
	echo '</div>';
}

function od_options_permalinks() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	$od_maintain = new od_object_maintenance();
	echo '<div class="wrap">';
	echo '<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br /></div><h2>Edit data permalinks</h2> ';
	echo '</div>';
}

function od_nav_menu_metabox() {
	add_meta_box( 
        'add-data',
        __( 'Add data', 'myplugin_textdomain' ),
        'od_inner_custom_box',
        'nav-menu' 
    );
}

function od_inner_custom_box() {
	echo "<p>Text box</p>";
}

function od_change_datatype($od_type="csv") {
	$od_pageURL = 'http';
	if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	$od_pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$od_pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$od_pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	if(strpos($od_pageURL,"od_data=")>0){
		if(strpos($od_pageURL,"od_filetype=")>0){
			$od_new_url = $od_pageURL . "&od_filetype=$od_type";
		} else {
			$od_new_url = $od_pageURL . "&od_filetype=$od_type";
		}
	} else {
		$od_new_url = $od_pageURL . ".$od_type";
	}
	return $od_new_url;
}


?>