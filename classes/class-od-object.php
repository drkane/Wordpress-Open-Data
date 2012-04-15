<?php

/**
 * Defines the od_object class, which contains function and variables for 
 * viewing and filtering open data stored in the WordPress database
 *
 * @package WordPressOpenData
 */

class Open_Data_Object {
	
	public $tables = array(); // The tables associated with Open Data
	public $table_config = array(); // The tables associated with Open Data
	public $data = array(); // The selected data
	public $item = array(); // An individual data item
	public $status = false;
	private $item_id = ""; // the data item's id
	public $filters = array(); // Filters selected by the user
	public $search = array(); // Search terms selected by the user
	public $default_table = ""; // the table that will be set as a default
	public $selected_table = ""; // the currently selected table
	public $setup = false; // whether the od_object has been setup
	public $data_type = "data"; // whether the od_object has been setup
	public $file_type = "html"; // whether the od_object has been setup
	public $header = "Content-type: text/html"; // header used when displaying the data page (default is html)
	public $od_include = false; // the path to the file used to display the data
	public $error = array();
	public $action = array();
	public $return_array = true;
	public $synonym = array( //allows different phrases to be used in the URL
		"data"=>"data",
		"map"=>"map",
		"item"=>"item",
		"filter"=>"filter",
		"search"=>"search"
	);
	public $filetypes = array(
		"html"=>array("header"=>"Content-type: text/html", "name"=>"HTML"),
		"txt"=>array("header"=>"Content-type: text/plain", "name"=>"Text document"),
		"csv"=>array("header"=>"Content-type: text/csv", "name"=>"Comma Separated Values"),
		"json"=>array("header"=>"Content-type: application/json", "name"=>"JavaScript Object Notation"),
		"jsonp"=>array("header"=>"Content-type: application/jsonp", "name"=>"JSON with padding"),
		"xml"=>array("header"=>"Content-type: text/xml", "name"=>"eXtensible Markup Language"),
		"rss"=>array("header"=>"Content-type: application/rss+xml", "name"=>"Really Simple Syndication"),
		"kml"=>array("header"=>"Content-type: application/vnd.google-earth.kml+xml", "name"=>"Keyhole Markup Language"),
		"georss"=>array("header"=>"Content-type: application/rss+xml", "name"=>"GeoRSS"),
		"xls"=>array("header"=>"Content-type: application/vnd.ms-excel", "name"=>"Excel 2003 spreadsheet"),
		"xlsx"=>array("header"=>"Content-type: application/vnd.openXMLformats-officedocument.spreadsheetml.sheet", "name"=>"Excel 2007 spreadsheet")
		//"ods"=>array("header"=>"application/vnd.oasis.opendocument.spreadsheet", "name"=>"Open Document Spreadsheet")
		);
	public $current_row = false;
	
	/**
	 * called when an object is created, sets and gets appropriate metadata
	**/
	function __construct($table=false){
		$this->tables = get_option("od_tables");
		$this->default_table = get_option("od_default_table");
		if(get_option("od_synonym_data")){
			$this->synonym["data"] = get_option("od_synonym_data");
		}
		if(get_option("od_synonym_map")){
			$this->synonym["map"] = get_option("od_synonym_map");
		}
		if(get_option("od_synonym_item")){
			$this->synonym["item"] = get_option("od_synonym_item");
		}
		if(get_option("od_synonym_filter")){
			$this->synonym["filter"] = get_option("od_synonym_filter");
		}
		if(get_option("od_synonym_search")){
			$this->synonym["search"] = get_option("od_synonym_search");
		}
		$this->select_table($table);
		$this->status = "constructed";
	}
	
	/**
	 * Gets the configuration options of all the tables
	**/
	public function get_table_config($od_table=false){
		if(!$od_table||!$this->table_exists($od_table)){
			if($this->selected_table!=""){
				$od_table=$this->selected_table;
			} else {
				$od_table=$this->default_table;
			}
		}
		
		if(get_option("od_table_" . $od_table)){
			$this->table_config = get_option("od_table_" . $od_table);
			return $this->table_config;
		} else {
			return false;
		}
	}
	
	/**
	 * Gets the number of rows in a table
	**/
	public function get_number_rows($table=false) {
		if(!$table){
			if($this->selected_table!=""){
				$table = $this->selected_table; 
			}
		} else if($this->table_exists($table)){
			$table = $table;
		} else {
			$table = false;
		}
		
		if($table){
			global $wpdb;
			$sql = "SELECT COUNT(*) AS rows FROM `" . $this->turl($table) . "`";
			$rows = $wpdb->get_results($sql, ARRAY_A);
			return $rows[0]["rows"] * 1;
		} else {
			return false;
		}
	}
	
	/**
	 * Return the table name for a table
	**/
	public function turl($table=false){
		if(!$table){
			if($this->selected_table!=""){
				$table = $this->selected_table; 
			}
		}
		
		if($table){
			global $wpdb;
			$sql = $wpdb->get_blog_prefix() . "opendata_" . $wpdb->escape($table);
			return $sql;
		} else {
			return false;
		}
	}
	
	/**
	 * Parses the URL that has been recieved
	**/
	public function parse_url($url="", $data_type="data"){
	
		// explode the URL using a dot to find the filetype
		$url_a = explode(".",$url);
		// explode the URL using a slash to find other variables
		$url_b = explode("/",$url_a[0]);
		
		$file_type = false;
		$search = array();
		$filters = array();
		
		if(isset($url_a[1])&&count($url_a)==2){
			if($url_a[1]!=""){
				$file_type = $url_a[1];
			}
		}
		
		//drk_print_r($url_b);
		
		if($data_type==$this->synonym["item"]){ // check if URL starts with "item" (or synonym)
			$data_type = "item";
			if(isset($url_b[0])&&count($url_b)==1){ // check that the URL has only one string after the text - this should be the item ID
				$this->set_item_id($url_b[0]);
			} else {
				$this->error[] = "Error parsing URL: Item requested but no item ID found";
			}
		} else {
			$part_check["filter_category"] = false;
			$part_check["filter_term"] = false;
			$part_check["search_term"] = false;
			$check_for = false;
			$filter_category = false;
			
			foreach($url_b as $url_part_key=>$url_part){ // go through every part of the URL
			
				if ($url_part_key===$part_check["filter_category"]) { // if this part has been flagged as a filter category
			
					$check = "Part flagged as filter category";
					
					if(isset($this->table_config["filter_columns"][$url_part])){ // if the column matches one that should be filtered
						$filter_category = $url_part;
					} else {
						$this->error[] = "Error parsing URL: filter category $url_part not found";
					}
				} else if ($url_part_key===$part_check["filter_term"]) {
			
					$check = "Part flagged as filter term";
					
					if($filter_category){
						$filters[$filter_category][] = $url_part;
					} else {
						$search[] = $url_part;
					}
				} else if ($url_part_key===$part_check["search_term"]) {
			
					$check = "Part flagged as search term";
					$search[] = $url_part;
				} else if($url_part===$this->synonym["filter"]) { // if the part is filter, then get it to check the next 2 for the right terms
			
					$check = "Part is filter";
				
					$part_check["filter_category"] == $url_part_key + 1;
					$part_check["filter_term"] == $url_part_key + 2;
					$check_for = "filter";
				} else if ($url_part===$this->synonym["search"]) { // if the part is search, then get it to look for search terms
			
					$check = "Part is search";
					$part_check["search_term"] == $url_part_key + 1;
					$check_for = "search";
				} else if ($check_for=="search") { // if meant to be checking for search terms, then add as a search term
			
					$check = "Checking for search terms";
					$search[] = $url_part;
				} else if ($check_for=="filter") { // if meant to be checking for filters, then check whether it is a filterable column
			
					$check = "Checking for filters";
					
					if(isset($this->table_config["filter_columns"][$url_part])){
						$filter_category = $url_part;
						$part_check["filter_term"] == $url_part_key + 1;
					} else {
						if($filter_category){ // if a filter category has already been set, then add a new filter
							$filters[$filter_category][] = $url_part;
						} else { // if not, use as a search term
							$search[] = $url_part;
						}
					}
				} else {
			
					$check = "Not matched anything";
				}
			
				//drk_print_r(array("Part"=>$url_part, "ID"=>$url_part_key, "Check"=>$check, "part_check"=>$part_check, "check_for"=>$check_for));
			}
			
		}
		
		//drk_print_r(array("filters"=>$filters, "search"=>$search));
		
		$this->set_filters($filters);
		$this->set_search($search);
		$this->set_data_type($data_type, $file_type);
			
		$this->status = "Filters set";
		
	}
	
	/**
	 * Sets the data and file type that will be used
	**/
	public function set_data_type($data_type=false, $file_type=false){
	
		// first set the data type (data, item or map)
		if($data_type) {
			foreach($this->synonym as $key=>$value){
				if($value==$data_type||$key==$data_type){
					$this->data_type = $key;
				}
			}
		}
		
		// then set the file type
		if($file_type) {
			if(isset($this->filetypes[$file_type])){
				$this->file_type = $file_type;
			}
		}
		
		// then check for exceptions
		if($this->data_type=="map"){
			$this->file_type = "html"; // maps must be displayed in HTML
		} else if($this->data_type=="item"&&$this->file_type=="rss"){
			$this->file_type = "html"; // items cannot be shown in RSS
		}
		
		if($this->file_type=="html"
			||$this->file_type=="csv"
			||$this->file_type=="txt"
			||$this->file_type=="rss"
			||$this->file_type=="georss"
			||$this->file_type=="xls"
			||$this->file_type=="xlsx"
			||$this->file_type=="kml"
			||$this->file_type=="ods") {
				$this->return_array = false;
			}
		
		// then set the correct header
		$this->header = $this->filetypes[$this->file_type]["header"];
		
		// then set the include path to be used
		$od_include_file_name = $this->data_type . "-" . $this->file_type . ".php";
		global $od_plugin_dir;
		$od_include_dirs = array( 
			get_stylesheet_directory() . "/" . $this->table_config["name"] . "-" , 
			get_template_directory() . "/" . $this->table_config["name"] . "-" , 
			$od_plugin_dir . "/templates/" . $this->table_config["name"] . "-" ,
			get_stylesheet_directory() . "/" , 
			get_template_directory() . "/" , 
			$od_plugin_dir . "/templates/",
			$od_plugin_dir . "/"
		);
		
		foreach($od_include_dirs as $od_include_dir_name){
			$od_include = $od_include_dir_name . $od_include_file_name;
			if(file_exists($od_include)&&!$this->od_include){
				$this->od_include = $od_include;
			}
		}
			
		if(!$this->od_include&&isset($this->filetypes[$this->file_type])) {
			$this->od_include = $this->file_type;
		}
		
	}
	
	/**
	 * Selects the table to be used for data.
	 * if no table is passed, or the desired table doesn't exist, then use the currently selected table
	 * if no table is curently selected then use the default table
	**/
	function select_table($table=false) {
		$return = false;
		if(!$table){ // check if a table was passed to function
			if($this->selected_table==""){
				$this->selected_table = $this->default_table;
			}
		} else {
			if($this->table_exists($table)){ // check if the selected table exists
				$this->selected_table = $table;
				$return = true;
			} else if($this->selected_table=="") {
				$this->selected_table = $this->default_table;
			}
		}
		$this->get_table_config();
		return $return;
	}
	
	/**
	 * Construct the SQL query used to select data, based on the values that have been selected
	**/
	private function construct_sql_query($rowcount=false, $offset=false){
		global $wpdb;
		$sql = "SELECT ";
		$sql .= drk_implode("`, `",$this->table_config["select_columns"], "`", "` "); // implode all the column names
		$sql .= "FROM `" . $this->turl() . "`";
		$sql_filters = array();
		
		// find search columns
		foreach($this->search as $s){
			$sql_filters[] = drk_implode("` LIKE '%$s%' OR `", $this->table_config["search_columns"], "( `", "` LIKE '%$s%')" );
		}
		
		// find filter columns
		foreach($this->filters as $filter_name=>$filter_col){
			if(isset($this->table_config["filter_columns"][$filter_name])){
				if($this->table_config["filter_columns"][$filter_name]["filter_type"]=="single"){
					$sql_filters[] = drk_implode("' OR `$filter_name` LIKE '", $filter_col, "( `$filter_name` LIKE '", "')" );
				}
				if($this->table_config["filter_columns"][$filter_name]["filter_type"]=="multiple"){
					$sql_filters[] = drk_implode(";%' OR `$filter_name` LIKE '%", $filter_col, "( `$filter_name` LIKE '%", ";%')" );
				}
			}
		}
		
		$sql .= drk_implode(" AND ", $sql_filters, " WHERE ", "", " WHERE 1");
		if($limit){
			if($offset===false){
				$sql .= " LIMIT " . $wpdb->escape($limit);
			} else {
				$sql .= " LIMIT " . $wpdb->escape($offset) . ", " . $wpdb->escape($limit);
			}
		}
			
		//drk_print_r($sql);
		return $sql;
	}
	
	/**
	 * Construct the SQL query used to select individual data items, based on the values that have been selected
	**/
	private function construct_sql_item_query(){
		global $wpdb;
		$id_col = $this->table_config["id"];
		$sql = "SELECT ";
		$sql .= drk_implode("`, `",$this->table_config["select_columns"],"`","` ");
		$sql .= "FROM `" . $this->turl() . "` WHERE `$id_col` = '".$this->item_id."'";
		$sql .= " LIMIT 0,1"; // only one item can be selected at any one time (item_id should be unique)
		return $sql;
	}
	
	/**
	 * Get the data from the database, and filter according to the search terms and filters selected
	 *
	 * If multiple search terms are used (not usual) then items are selected if they match any search term
	 * Multiple filters within the same field are treated as OR - ie they have to match any one
	 * But multiple filters across different field are treated as AND - ie a record must match one from every filter used to be selected
	 *
	**/
	public function get_data($customsql=""){
		global $wpdb;
		if($customsql==""){
			if($this->data_type=="item"){
				$sql = $this->construct_sql_item_query();
			} else {
				$sql = $this->construct_sql_query();
			}
		} else {
			$sql = str_replace("%turl%", $this->turl(), $customsql); // allows a custom query to be sent. May create a security risk, so should be sanitised before being used.
		}
		$initial_data = $wpdb->get_results($sql, ARRAY_A);
		if ( $this->return_array ) { 
			foreach($initial_data as $datarow_key=>$datarow){ // go through the selected data set row by row
				foreach($datarow as $datacol_key=>&$datacol){
					if(isset($this->table_config["filter_columns"][$datacol_key])){
						if($this->table_config["filter_columns"][$datacol_key]["filter_type"]=="multiple"){
							$datacol = str_replace("\\;","<<semicolon>>",$datacol); // temporarily replace any escaped semicolons
							if(substr($datacol, -1)==";"){
								$datacol = substr($datacol, 0, -1);
							}
							$datacol = explode(";",$datacol); // break the item into the multiple values, using a semicolon
							foreach($datacol as &$datacolitem){
								$datacolitem = str_replace("<<semicolon>>",";",$datacolitem); // put back the escaped semicolon
							}
						}
					}
				}
				$this->data[] = $datarow;
			}
		} else {
			$this->data = $initial_data;
		}
		unset($initial_data);
		
		$this->status = "Data set";
		
		return $this->data; // return the data (data is also accessible from the $this->data field)
	}
	
	/**
	 * When creating an RSS feed, find which field should be used for a particular value (title, description, etc)
	 *
	 * This function could probably be done a lot more efficiently.
	**/
	public function get_field_meta($field,$type="rss"){
		$return = false;
		if($type=="geog"){
			$type = "geog_type";
		} else {
			$type = "rss_type";
		}
 		foreach($this->table_config["columns"] as $od_col_key=>$od_col){ // go through each column
			if($od_col[$type]==$field){ // if the rss_type matches the value you're looking for then that's the right field
				$return = $od_col_key;
			}
			if($field=="id"){
				if($od_col["is_id"]==1){
					$return = $od_col_key;
				}
			}
		}
		if($this->current_row>0&&count($this->data)>0){ // if used in the loop then get the value for the current row
			$return = $this->data[$this->current_row - 1][$return];
		}
		return $return; // return the name of the column that should be used for this value
	}
	
	/**
	 * Returns a list of the filters that the user has applied, in a friendly format
	 *
	 * Could also check whether the filters applied correspond to 
	 * actual filtered fields, though that may impact on performance.
	**/
	public function get_filters($display_type="html"){
		$filters = array();
		$filters_xml = array();
		$filters_url = array();
		$search_url = array();
		foreach($this->filters as $filtkey=>$filt){ // get all of the filters currently applied
			foreach($filt as $f){
				if($f != ""){ // if the filter isn't for an empty string
					$filters[] = $this->table_config["columns"][$filtkey]["nice_name"] . ": " . ucwords($f); // produce a text string with the filter and the value
					$filters_xml[] = array("slug"=>$filtkey, "name"=>$this->table_config["columns"][$filtkey]["nice_name"], "filter"=>ucwords($f));
					$filters_url[] = $filtkey . "-" . $f;
				}
			}
		}
		foreach($this->search as $filt){
			if($filt != "" ){ // if the filter isn't for an empty string
				$filters[] = "Search: $filt"; // also include search filters
				$filters_xml[] = array("Search",ucwords($filt));
				$search_url[] = $filt;
			}
		}
		$text = "";
		if(count($filters)>0){ 
			if($display_type=="html"){ // if html output is required
				$text .= drk_implode("</li>\n<li>",$filters, "<ul><li>", "</li></ul>"); // implode all the selected filters
			} else if($display_type=="xml") {
				foreach($filters_xml as $filter_xml){
					$header = $filter_xml["slug"]; // remove any non alphanumeric characters from the tags
					$text .= "<$header value=\"".$filter_xml["name"]."\">".$filter_xml["filter"]."</$header>";
				}
			} else if($display_type=="url") {
				$text = drk_implode("-",$filters_url,"filters-","");
				$text .= drk_implode("-",$search_url,"search-","");
			} else {
				$text = implode(" | ",$filters);
			}
		}
		return $text;
	}
	
	/**
	 * check whether a named table exists
	**/
	public function table_exists($table){
		$table = drk_sluggify($table);
		$table_exists = false;
		if($this->tables){
			$table_exists = isset($this->tables[$table]);
		}
		return $table_exists;
	}
	
	/**
	 * Set the item that is being found
	**/
	public function set_item_id($od_id=false){
		if($od_id){
			global $wpdb;
			$this->item_id = $wpdb->escape($od_id); // SQL escaped for security
		}
	}
	
	/**
	 * Store the column properties OBSOLETE
	**/
	private function get_column_properties(){
		$sql_columns = $this->table_config["columns"];
		$sql_columns =drk_subval_sort($sql_columns,"order","forward"); // sort the columns by the sort order provided
		foreach($sql_columns as $col){
			$colname = $wpdb->escape($col["column_name"]);
			if($col["is_open"]){ // include only columns that are allowed to be made open
				$this->select_cols[] = $colname;
				if($col["is_search"]){ // if a column can be searched then include in search columns
					$this->search_cols[$colname] = $colname;
				}
				if($col["filter_type"]=="multiple"){ // if a column can be filtered then include in filter columns
					$this->filter_cols["multiple"][$colname] = $colname;
				}
				if($col["filter_type"]=="single"){ // if a column can be filtered then include in filter columns
					$this->filter_cols["single"][$colname] = $colname;
				}
				if($col["filter_type"]=="search"){ // if a column can be filtered then include in filter columns
					$this->filter_cols["single"][$colname] = $colname;
				}
			}
		}
	}
	
	/**
	 * Get an item from a data table
	**/
	public function get_item(){
		global $wpdb;
		$sql = $this->construct_sql_item_query();
		$this->item = $wpdb->get_results($sql, ARRAY_A);
		$this->item = $this->item[0];
		foreach($this->item as $datacol_key=>&$datacol){
			if($this->table_config["columns"][$datacol_key]["filter_type"]=="multiple"){ // turn multiple filter items into an array
				$datacol = str_replace("\\;","<<semicolon>>",$datacol); // replace escaped semicolons
				$datacol = explode(";",$datacol);
				foreach($datacol as &$datacolitem){
					$colselect = 0;
					$datacolitem = trim($datacolitem);
					$datacolitem = str_replace("<<semicolon>>",";",$datacolitem); // put back in escaped semicolons
				}
			}
		}
		return $this->item;
	}
	
	
	
	/**
	 * Set the filters that will be used by the main data query
	**/
	public function get_menu_categories($field, $table=false){
		$field = drk_sluggify($field);
		$return = '';
		if(isset($this->table_config["filter_columns"][$field])){
			$filter_col = $this->table_config["filter_columns"][$field];
			$return .=  "<ul id=\"sub-menu\" class=\"sub-menu\">";
			$count=0;
			foreach($filter_col["categories"] as $catkey=>$cat){
				if($count<12){
					$rlink = strtolower(str_replace(" ","+",$cat["name"]));
					$return .=  "<li id=\"" . $catkey . "\" class=\"menu-item\"><a href=\"";
					$return .=  home_url() . "/data/" . $this->synonym["filter"] . "/" . $filter_key . "/" . $rlink;
					$return .=  "\">".$cat["name"]." [" . $cat["records"] . "]</a></li>\n";
					$count++;
				}
			}
			$return .=  "</ul>\n";
		}
		return $return;
	}
	
	/**
	 * Set the filters that will be used by the main data query
	**/
	public function set_filters($filters=null){
		global $wpdb;
		$success = false;
		if(is_array($filters)){
			foreach($filters as $filterkey=>$filter){
				if(isset($this->table_config["filter_columns"][$filterkey])){ // check if each filter is in a filterable column
					if(is_array($filter)){ 
						if(implode("",$filter)!=""){
							$this->filters[$filterkey] = $filter; // add the filters as an array
						}
					} else { // if only one filter term is included
						if($filter!=""){ 
							$this->filters[$filterkey] = array($filter); // then add it as an array
						}
					}
					$success = true;
				}
			}
		} // possibly need something if filters isn't an array (though it should be). Perhaps use as a search term instead?
		foreach($this->filters as &$filt1){
			foreach($filt1 as &$filt2){
				$filt2 = $wpdb->escape($filt2); // escape all the filter terms, ready to go into a SQL query
			}
		}
		return $this->filters;
	}
	
	/**
	 * Set the search terms that will be used by the main data query
	**/
	public function set_search($search){
		global $wpdb;
		if(!is_array($search)){
			$search = explode(" OR ",$search); // if the word " OR " is included then use as an array (if only one search term is used then this creates an array anyway)
		}
		foreach($search as $s){
			if($s!=""){
				$s = $wpdb->escape($s); // escape all the search terms, ready to go into a SQL query 
				//$s = str_replace(" AND ","%",$s); // aborted attempt at including wildcard characters - could be reused.
				$this->search[] = $s;
			}
		}
		return $this->search;
	}
	
	/**
	 * When an item is displayed in HTML a template can be used to display the data
	**/
	private function get_item_template($template_type=''){
		if($template_type=="sidebar"){
			$template = $this->table_config["item_sidebar_template"];
		} else {
			$template = $this->table_config["item_template"];
		}
		return $template;		
	}
	
	/**
	 * Get metadata about a particular table
	**/
	public function get_table_metadata($metadatafield){
		$template = false;
		if(isset($this->table_config[$metadatafield])){
			$template = $this->table_config[$metadatafield];
		}
		return $template;		
	}
	
	/**
	 * Apply a main or sidebar template when an item is displayed
	 * Fields are replaced using the pattern %%field%%
	 * Text within double curly brackets eg {{%%field%% xxxxx}} is only included if the field is not blank
	**/
	public function apply_template($od_template_type="main"){
		$od_data = $this->get_item();
		if($od_template_type=="sidebar"){
			$od_template = $this->get_item_template("sidebar"); // get the sidebar template
			if($od_template==""){
				get_sidebar(); // if no template is supplied then use the default sidebar
			} else {
				$text = "<div id=\"secondary\" class=\"widget-area\" role=\"complementary\">\n";
				$text .= "<aside id=\"text-9\" class=\"widget widget_text\">\n";
			}
		} else {
			$od_template = $this->get_item_template(); // get the main template
			$text = "";
		}
		if($od_template==""&&$od_template_type!="sidebar"){ // if no item template is supplied then use a really boring format
			$text .= "<header class=\"entry-header\">\n";
			$text .= "<h1 class=\"entry-title\">Data item</h1>\n";
			$text .= "</header><!-- .entry-header -->\n";
			$text .= "<div class=\"entry-content\">\n";
			$text .= "<table>\n";
			foreach($od_data as $od_key=>$od_item){
				if(is_array($od_item)){
					foreach($od_item as &$column){
						$column = "<a href=\"\">$column</a>";
					}
					$od_item = implode(";\n",$od_item);
				}
				$text .= "<tr><td>". $this->table_config["columns"][$od_key]["nice_name"] .": </td><td>$od_item</td></tr>\n";
			}
			$text .= "</table>\n";
			$text .= "</div><!-- .entry-content -->\n";
		} else { // otherwise display the template with fields replaced
			preg_match_all("/%%(.*?)%%/",$od_template,$matches); // find all the fields mentioned in the template
			
			$patterns_if = array(); // an array of possible fields to match
			$patterns = array(); // an array of possible fields to match
			$replacements_if = array(); // array of replacement values
			$replacements = array(); // array of replacement values
			
			foreach($matches[1] as $o){ // go through all the field matches found
				$patterns_if[] = "/{{(.*?)%%$o%%(.*?)}}/"; // work out the regular expression to match the field
				$patterns[] = "/%%$o%%/"; // work out the regular expression to match the field
				if(isset($od_data[$o])){ // if the data item exists
					if(is_array($od_data[$o])){ // if the data item is an array
						$od_data[$o] = implode(";\n",$od_data[$o]); // then implode it into one string
					}
					$replacements[] = $od_data[$o]; // add the data value as a replacement
					if($od_data[$o]!=""){
						$replacements_if[] = '${1}'.$od_data[$o].'${2}';
					} else {
						$replacements_if[] = "";
					}
				} else { // otherwise replace with blank
					$replacements_if[] = "";
					$replacements[] = "";
				}
			}

			$text2 = preg_replace($patterns_if,$replacements_if,$od_template); // replace all the if values with the appropriate values
			$text2 = preg_replace($patterns,$replacements,$text2); // replace all the fields with appropriate values
			$text .= $text2;
		}
		if($od_template_type=="sidebar"&&$od_template!=""){
			$text .= "</aside>\n";
			$text .= "</div><!-- #secondary -->\n";
		}
		return $text; // return the html based on the template
	}
	
	public function current_row() {
		if($this->current_row>0&&count($this->data)>0){
			return $this->data[$this->current_row - 1];
		} else {
			return false;
		}
	}
	
	public function get_field($field) {
		$return = false;
		if($this->current_row>0&&count($this->data)>0){
			$field = drk_sluggify($field);
			$return = $this->data[$this->current_row - 1][$field];
		}
		return $return;
	}
	
	public function display_data() {
	
		$od_type = $this->file_type;
		$od_data_type = $this->data_type;
		$od_data = $this->get_data();
		
		switch($od_type) {
		
			case "json":
				$output = json_encode($od_data); // use PHP's built-in JSON function
				break;
			
			case "jsonp":
				$callback = "jsonp("; // default callback is jsonp
				if(isset($_REQUEST["od_callback"])){ // replaces the default callback if it exists
					$callback = $_REQUEST["od_callback"] . "("; 
				}
				$endcallback =  ")";
				$output = $callback . json_encode($od_data) . $endcallback;
				break;
			case "csv":
				$filename = $this->table_config["name"] . drk_implode ( "" , array( $this->get_filters("url") ) , "-");
				if($filename==""||$filename=="-"){$filename = "data_export"; }
				$filename .= "-" . date( "Ymd" ) ;
				$filename .= ".csv";
				header('Content-Disposition: attachment; filename="' . $filename . '"'); // file should be downloaded in the browser
			case "txt":
				$proceed = true;
				if($this->data_type=="item"){
					if($od_type=="txt"){
						if(isset($od_data[0])&&count($od_data)==1){
							$output = "";
							foreach($od_data[0] as $key=>$data){
								if(is_array($data)){
									$data = implode(";", $data);
								}
								$output .= $this->table_config["columns"][$key]["nice_name"] . ": " . $data . "\n";
							}
							$proceed = false;
						}
					} else {
						$od_data = array($od_data);
					}
				}
				if($proceed){
					$outstream = fopen("php://temp", 'r+'); // open a temporary file to write the CSV to
					$count=0;
					foreach($od_data as $entryrow){ // for each row in the data
						if($count==0){ // if its the first row then create a header row
							$data = array();
							foreach($entryrow as $header=>$column){
								$data[] = $this->table_config["columns"][$header]["nice_name"];
							}
							fputcsv($outstream,$data,",",'"'); // use PHP's CSV write function to write the header row
						}
						$data = array();
						foreach($entryrow as $header=>$column){
							if(is_array($column)){ // for values that are arrays, separate with a semi-colon
								$data[] = trim(implode("; ",$column));
							} else {
								$data[] = $column;
							}
						}
						fputcsv($outstream,$data,",",'"'); // use PHP's CSV write function to write each row of data
						$count++;
					}
					rewind($outstream); // go back to the beginning of the file
					$output = stream_get_contents($outstream); // get the contents of the temporary file
					fclose($outstream); // close the temporary file
				}
				break;
			case "xls":
			case "xlsx":
			
				$filename = $this->table_config["name"] . drk_implode ( "" , array( $this->get_filters("url") ) , "-");
				if($filename==""||$filename=="-"){$filename = "data_export"; }
				$filename .= "-" . date( "Ymd" ) ;
				
				include_once(dirname(__FILE__) . "/lib/PHPExcel.php" );
				
				if($od_type=="xls") {
					$filename .= ".xls";
					include_once(dirname(__FILE__) . "/lib/phpexcel/Writer/Excel5.php" );
				} else if($od_type=="xlsx") {
					$filename .= ".xlsx";
					include_once(dirname(__FILE__) . "/lib/phpexcel/Writer/Excel2007.php" );
				}
				
				$objPHPExcel = new PHPExcel();
				$objPHPExcel->setActiveSheetIndex(0);
				
				$row = 1;
				foreach($od_data as $data){
					$col = 1;
					foreach($data as $header=>$d) {
						if($row==1){
							$objPHPExcel->getActiveSheet()->SetCellValue( drk_excel_column($col) . $row , $header );
							$objPHPExcel->getActiveSheet()->SetCellValue( drk_excel_column($col) . ( $row + 1 ) , $d );
						} else {
							$objPHPExcel->getActiveSheet()->SetCellValue( drk_excel_column($col) . $row , $d );
						}
						$col++;
					}
					if($row==1){
						$row = $row + 2;
					} else {
						$row++;
					}
				}
				
				header('Content-Disposition: attachment;filename="' . $filename . '"');
				header('Cache-Control: max-age=0');
				
				if($od_type=="xls") {
					$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
				} else if($od_type=="xlsx") {
					$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
				}

				$objWriter->save('php://output'); 
				break;
			case "ods":
			
				$filename = $this->table_config["name"] . drk_implode ( "" , array( $this->get_filters("url") ) , "-");
				if($filename==""||$filename=="-"){$filename = "data_export"; }
				$filename .= "-" . date( "Ymd" ) ;
				
				include_once(dirname(__FILE__) . "/lib/ods-php/ods.php" );
				
				$filename .= ".ods";
				
				$odsoutput = newOds();
				
				$row = 0;
				foreach($od_data as $data){
					$col = 0;
					foreach($data as $header=>$d) {
						if($row==0){
							$odsoutput->addCell( 0 , $row , $col , $header , 'string' );
							$odsoutput->addCell( 0 , ( $row + 1 ) , $col , $d , 'string' );
						} else {
							$odsoutput->addCell( 0 , $row , $col , $d , 'string' );
						}
						$col++;
					}
					if($row==0){
						$row = $row + 2;
					} else {
						$row++;
					}
				}
				
				//header('Content-Disposition: attachment;filename="' . $filename . '"');
				//header('Cache-Control: max-age=0');
				
				saveOds($odsoutput,'new.ods'); //save the object to a ods file
				break;
			case "html":
			default:
				$output = "<html>\n\t<body>\n\t\t<table>\n";
				$count = 0;
				foreach($od_data as $entryrow){ // for each row in the data
					if($count==0){ // if its the first row then create a header row
						$data = array();
						foreach($entryrow as $header=>$column){
							$data[] = $header;
						}
						$output .= drk_implode("</th>\n\t\t\t\t\t<th>", $data, "\n\t\t\t<thead>\n\t\t\t\t<tr>\n\t\t\t\t\t<th>", "</th>\n\t\t\t\t</tr>\n\t\t\t</thead>" );
					}
					$data = array();
					$output .= "\n\t\t\t<tbody>";
					foreach($entryrow as $header=>$column){
						if(is_array($column)){ // for values that are arrays, separate with a semi-colon
							$data[] = trim(implode("; ",$column));
						} else {
							$data[] = $column;
						}
					}
					$output .= drk_implode("</td>\n\t\t\t\t\t<td>", $data, "\n\t\t\t\t<tr>\n\t\t\t\t\t<td>", "</td>\n\t\t\t\t</tr>" );
					$count++;
				}
				$output .= "\n\t\t\t</tbody>\n\t\t</table>\n\t</body>\n</html>";
				break;
		}
		
		return $output;
		
	}

	function change_datatype ( $od_type="csv",$od_view="data" )  {
		return "http://www.bbc.co.uk/news/";
		/*
		
		NEEDS TO BE REDONE
		
		
		global $od_data;
		$od_filetypes = $od_data->filetypes;
		$od_pageURL = 'http';
		if ( isset ( $_SERVER["HTTPS"] )  ) {
			if  ( $_SERVER["HTTPS"] == "on" )  {$pageURL .= "s";
	}	}	$od_pageURL .= "://";
		if  ( $_SERVER["SERVER_PORT"] != "80" )  {
		$od_pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$od_pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}	if ( strpos ( $od_pageURL,"od_data=" ) >0 ) {
			if ( strpos ( $od_pageURL,"od_filetype=" ) >0 ) {
				$od_new_url = $od_pageURL . "&od_filetype=$od_type";
			} else {
				$od_new_url = $od_pageURL . "&od_filetype=$od_type";
			}
			if ( $od_view=="map" ) {
				$od_new_url = str_replace ( "od_data=data","od_data=map",$od_new_url );
			} else {
				$od_new_url = str_replace ( "od_data=map","od_data=data",$od_new_url );
			}
		} else {
			if ( $od_view=="map" ) {
				$od_new_url = str_replace ( "/data","/map",$od_pageURL );
			} else {
				$od_new_url = str_replace ( "/map","/data",$od_pageURL );
			}
			if ( substr ( $od_new_url,-1 ) =="/" ) {
				$od_new_url = substr ( $od_new_url,0,-1 );
			}
			drk_print_r ( $od_filetypes );
			foreach ( $od_filetypes as $od_key=>$od_value ) {
				$od_new_url = str_replace ( ".$od_key","",$od_new_url );
			}
			if ( $od_type!="html" ) {
				$od_new_url = $od_new_url . ".$od_type";
			}
		}
		return $od_new_url; */
	}
	
}

?>