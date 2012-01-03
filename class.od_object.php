<?php

/**
 * Defines the od_object class, which contains function and variables for 
 * viewing and filtering open data stored in the WordPress database
 *
 * @package WordPressOpenData
 */

class od_object {
	
	public $tables = array(); // The tables associated with Open Data
	public $data = array(); // The selected data
	public $item = array(); // An individual data item
	public $filters = array(); // Filters selected by the user
	public $search = array(); // Search terms selected by the user
	public $default_table = ""; // the table that will be set as a default
	public $selected_table = ""; // the currently selected table
	public $categories = array(); // categories included in the data (those that it is possible to select using $filters)
	private $setup = false; // whether the od_object has been setup
	private $tables_name = ""; // the name of the admin table that stores data about the tables
	private $columns_name = ""; // the name of the admin table that stores data about the columns
	private $item_id = ""; 
	private $select_cols = array(); // columns that it is possible to select
	private $search_cols = array(); // columns that can be searched
	private $filter_cols = array("single"=>array(),"multiple"=>array()); // columns that can be filtered (two types of filters)
	
	/**
	 * called when an object is created, sets and gets appropriate metadata
	**/
	function __construct(){
		$this->set_table_names();
		$this->get_table_config();
		$this->select_table();
	}
	
	/**
	 * Sets the admin table names using the standard blog table name prefix
	**/
	private function set_table_names(){
		global $wpdb;
		$this->tables_name = $wpdb->get_blog_prefix()."opendata_tables";
		$this->columns_name = $wpdb->get_blog_prefix()."opendata_columns";
	}
	
	/**
	 * Gets the configuration options of all the tables
	**/
	private function get_table_config(){
		global $wpdb;
		$od_sql = "SHOW TABLES LIKE '".$this->tables_name."'";
		if($wpdb->query($od_sql)==1){ // check if the admin table exists
			$od_sql = "SHOW TABLES LIKE '".$this->columns_name."'";
			if($wpdb->query($od_sql)==1){ // check if the columns admin table exists
				$od_sql = "SELECT * FROM `".$this->tables_name."`";
				$this->tables = $wpdb->get_results($od_sql, ARRAY_A); // get data from the tables admin table
				foreach($this->tables as $od_table_key=>$od_table){ // for each table
					$od_table_name = $wpdb->escape($od_table["name"]);
					$od_sql_col = "SELECT * FROM `".$this->columns_name."` WHERE `table_name` = '$od_table_name'"; // find data on all the columns
					$this->tables[$od_table_name] = $od_table;
					$this->tables[$od_table_name]["columns"] = $wpdb->get_results($od_sql_col, ARRAY_A); // add details about the columns to the table array
					foreach($this->tables[$od_table_name]["columns"] as $od_col_key=>$od_col){
						$this->tables[$od_table_name]["columns"][$od_col["column_name"]] = $od_col;
						unset($this->tables[$od_table_name]["columns"][$od_col_key]);
					}
					if($od_table["is_default"]==1){
						$this->default_table = $od_table_name; // find the default table
					}
					unset($this->tables[$od_table_key]);
				}
			}	// Something needs to happen if the admin tables don't exist (create them?)
		}	// Something needs to happen if the admin tables don't exist (create them?)
	}
	
	/**
	 * Selects the table to be used for data.
	 * if no table is selected, or the selected table doesn't exist, then use the default table
	**/
	function select_table($table=null) {
		if($table==null){ // check if a table is selected
			$this->selected_table = $this->default_table;
		} else {
			if($this->table_exists($table)){ // check if the selected table exists
				$this->selected_table = $table;
			} else {
				$this->selected_table = $this->default_table;
			}
		}
	}
	
	/**
	 * Construct the SQL query used to select data, based on the values that have been selected
	**/
	private function construct_sql_query(){
		global $wpdb;
		$sql = "SELECT ";
		$sql_columns = $this->tables[$this->selected_table]["columns"];
		$sql_columns =$this->subval_sort($sql_columns,"order","forward"); // sort the columns by the sort order provided
		foreach($sql_columns as $col){
			$colname = $wpdb->escape($col["column_name"]);
			if($col["is_open"]==1){ // include only columns that are allowed to be made open
				$this->select_cols[] = $colname;
				if($col["is_search"]==1){ // if a column can be searched then include in search columns
					$this->search_cols[$colname] = $colname;
				}
				if($col["filter_type"]=="multiple"){ // if a column can be filtered then include in filter columns
					$this->filter_cols["multiple"][$colname] = $colname;
				}
				if($col["filter_type"]=="single"){ // if a column can be filtered then include in filter columns
					$this->filter_cols["single"][$colname] = $colname;
				}
			}
		}
		$sql .= "`" . implode("`, `",$this->select_cols) . "` "; // implode all the column names
		$sql .= "FROM `".$wpdb->get_blog_prefix()."opendata_".$this->selected_table."` WHERE 1";
		return $sql;
		/**
		 * this query could create problems for large datasets, as filtering 
		 * and searching is done in PHP rather than in MySQL which means 
		 * unnecessary data is included in the MySQL query. But to overcome 
		 * this the query needs to be able to "properly" filter on fields that
		 * are semi-colon separated (multiple filters)
		**/
	}
	
	/**
	 * Construct the SQL query used to select individual data items, based on the values that have been selected
	**/
	private function construct_sql_item_query(){
		global $wpdb;
		$id_col = "";
		$sql = "SELECT ";
		$sql_columns = $this->tables[$this->selected_table]["columns"];
		$sql_columns =$this->subval_sort($sql_columns,"order","forward");
		foreach($sql_columns as $col){
			$colname = $wpdb->escape($col["column_name"]);
			if($col["is_open"]==1){ // include only columns that are allowed to be made open
				$this->select_cols[] = $colname;
			}
			if($col["is_id"]==1){ // find the ID column (used to select the item)
				$id_col = $colname;
			}
		}
		$sql .= "`" . implode("`, `",$this->select_cols) . "` ";
		$sql .= "FROM `".$wpdb->get_blog_prefix()."opendata_".$this->selected_table."` WHERE `$id_col` = '".$this->item_id."'";
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
			$sql = $this->construct_sql_query();
		} else {
			$sql = $customsql; // allows a custom query to be sent. May create a security risk, so should be sanitised before being used.
		}
		$initial_data = $wpdb->get_results($sql, ARRAY_A);
		foreach($initial_data as $datarow_key=>$datarow){ // go through the selected data set row by row
			$select = 0;
			$search_select = 0;
			foreach($datarow as $datacol_key=>&$datacol){ // for each column in the data
				if(isset($this->search_cols[$datacol_key])){ // if the column should be searched
					foreach($this->search as $s){ // for each search term
						if(strpos(strtolower($datacol),strtolower($s))!==false){
							$search_select = 1; // if the search term 
						}
					}
				}
				if(isset($this->filter_cols["multiple"][$datacol_key])){ // if a field is used as a multiple filter
					$datacol = str_replace("\\;","<<semicolon>>",$datacol); // temporarily replace any escaped semicolons
					$datacol = explode(";",$datacol); // break the item into the multiple values, using a semicolon
					foreach($datacol as &$datacolitem){ // for each value within the column
						$colselect = 0;
						$datacolitem = trim($datacolitem); // remove whitespace
						$datacolitem = str_replace("<<semicolon>>",";",$datacolitem); // put back the escaped semicolon
						if($datacolitem!=""){
							/**
							 * this part counts the number of possible values within each selectable column
							 * in order to allow the most popular fields to be identified
							**/
							if(isset($this->categories[$datacol_key]["records"][$datacolitem])) // check if this value has been seen already
								$this->categories[$datacol_key]["records"][$datacolitem]["count"]++; // if it has then count it
							else {
								$this->categories[$datacol_key]["records"][$datacolitem]["name"]=$datacolitem; // if not then create a new field
								$this->categories[$datacol_key]["records"][$datacolitem]["count"]=1; // and start the count at 1
							}
						}
						if(isset($this->filters[$datacol_key])){ // if this column has been filtered by the user
							foreach($this->filters[$datacol_key] as $colfilter){
								if(strtolower($colfilter)==strtolower($datacolitem)){ // and the value matches a value the user is looking for
									$colselect = 1; // then this row should be selected
								}
							}
						}
						$select = $select + $colselect;
					}
				} else if(isset($this->filter_cols["single"][$datacol_key])){
					if($datacol!=""){
						/**
						 * this part counts the number of possible values within each selectable column
						 * in order to allow the most popular fields to be identified
						**/
						if(isset($this->categories[$datacol_key]["records"][$datacol])) // check if this value has been seen already
							$this->categories[$datacol_key]["records"][$datacol]["count"]++; // if it has then count it
						else {
							$this->categories[$datacol_key]["records"][$datacol]["name"]=$datacol; // if not then create a new field
							$this->categories[$datacol_key]["records"][$datacol]["count"]=1; // and start the count at 1
						}
					}
					if(isset($this->filters[$datacol_key])){ // if this column has been filtered by the user
						foreach($this->filters[$datacol_key] as $colfilter){
							if(strtolower($colfilter)==strtolower($datacol)){ // and the value matches a value the user is looking for
								$select = $select + 1; // then this row should be selected
							}
						}
					}
				}
			}
			if($select==count($this->filters)){ // if the number of filter matches is equal to the number of columns being filtered
				if($search_select>0&&count($this->search)>0){ // and the number of search matches matches the number of search terms
					$this->data[] = $datarow; // then add this row to the data
				} else if (count($this->search)==0){ // or if no search is being used
					$this->data[] = $datarow; // then add this row to the data
				}
			}
		}
		unset($initial_data); // remove the original data, to save on memory
		$this->sort_categories(); // sort the values within each category by the number of times they appear
		return $this->data; // return the data (data is also accessible from the $this->data field)
	}
	
	/**
	 * When creating an RSS feed, find which field should be used for a particular value (title, description, etc)
	 *
	 * This function could probably be done a lot more efficiently.
	**/
	public function get_rss($rss_field){
		$return = false;
		foreach($this->tables[$this->selected_table]["columns"] as $od_col_key=>$od_col){ // go through each column
			if($od_col["rss_type"]==$rss_field){ // if the rss_type matches the value you're looking for then that's the right field
				$return = $od_col_key;
			}
			if($rss_field=="id"){
				if($od_col["is_id"]==1){
					$return = $od_col_key;
				}
			}
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
		foreach($this->filters as $filtkey=>$filt){ // get all of the filters currently applied
			foreach($filt as $f){
				if($f != ""){ // if the filter isn't for an empty string
					$filters[] = ucwords($filtkey) . ": $f"; // produce a text string with the filter and the value
				}
			}
		}
		foreach($this->search as $filt){
			if($filt != "" ){ // if the filter isn't for an empty string
				$filters[] = "Search: $filt"; // also include search filters
			}
		}
		$text = "";
		if(count($filters)>0){ 
			if($display_type=="html"){ // if html output is required
				$text .= "<ul><li>";
				$text .= implode("</li>\n<li>",$filters); // implode all the selected filters
				$text .= "</li></ul>";
			} else {
				$text = implode(" | ",$filters);
			}
		}
		return $text;
	}
	
	/**
	 * check whether a named table exists
	**/
	private function table_exists($table){
		$table_exists = false;
		foreach($this->tables as $od_table){ // cycle through all of the tables 
			if($od_table["name"]==$table){ // if the table is in there then it exists
				$table_exists = true; 
			}
		}
		return $table_exists;
	}
	
	/**
	 * Sort each of the categories according to how many times each value appears (descending)
	**/
	private function sort_categories(){
		foreach($this->categories as &$cat){
			$cat["records"] = $this->subval_sort($cat["records"],"count"); // uses subval_sort function
		}
	}
	
	/**
	 * Set the item that is being found
	**/
	public function set_item_id($od_id=""){
		global $wpdb;
		$this->item_id = $wpdb->escape($od_id); // SQL escaped for security
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
			if($this->tables[$this->selected_table]["columns"][$datacol_key]["filter_type"]=="multiple"){ // turn multiple filter items into an array
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
	public function set_filters($filters=null){
		global $wpdb;
		$success = false;
		if(is_array($filters)){
			foreach($filters as $filterkey=>$filter){
				$include = false;
				foreach($this->tables[$this->selected_table]["columns"] as $od_columns){ // check if each filter is in a filterable column
					if($od_columns["column_name"]==$filterkey){
						$include = true;
					}
				}
				if($include===true){
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
	private function get_item_template(){
		global $wpdb;
		$od_sql = "SELECT `item_template` FROM `".$this->tables_name."` WHERE `name` = '".$wpdb->escape($this->selected_table)."' LIMIT 0,1";
		$template = $wpdb->get_results($od_sql, ARRAY_A);
		$template = $template[0]["item_template"];
		return $template;		
	}
	
	/**
	 * When an item is displayed in HTML a template can be used to display extra info in a sidebar
	**/
	private function get_item_sidebar_template(){
		global $wpdb;
		$od_sql = "SELECT `item_sidebar_template` FROM `".$this->tables_name."` WHERE `name` = '".$wpdb->escape($this->selected_table)."' LIMIT 0,1";
		$template = $wpdb->get_results($od_sql, ARRAY_A);
		$template = $template[0]["item_sidebar_template"];
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
			$od_template = $this->get_item_sidebar_template(); // get the sidebar template
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
				$text .= "<tr><td>". $od_object->tables[$od_object->selected_table]["columns"][$od_key]["nice_name"] .": </td><td>$od_item</td></tr>\n";
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
	
	function subval_sort($a,$subkey,$direction="reverse") {
	/* 
	** a function for sorting a multidimensional array 
	** stolen from http://www.firsttube.com/read/sorting-a-multidimensional-array-with-php/
	** 		$a = array to sort
	**		$subkey = the key of the contained array to sort by
	** returns a new array sorted by the subkey
	*/
		if(is_array($a)){
			foreach($a as $k=>$v) {
				$b[$k] = strtolower($v[$subkey]); // make an array of the subkeys
			}
			if(isset($b)){
				if($direction=="reverse"){
					arsort($b); // sort the array of subkeys in reverse
				} else {
					asort($b); // sort the array of subkeys
				}
				foreach($b as $key=>$val) {
					$c[$key] = $a[$key]; // remake original array based on the order of the subkeys
				}
				return $c;
			} else {
				return $a; // if no array made then return the original
			}
		} else {
			return $a; // if no array made then return the original
		}
	}
	
}

?>