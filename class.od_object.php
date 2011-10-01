<?php

class od_object {
	
	public $tables = array();
	public $data = array();
	public $item = array();
	public $filters = array();
	public $search = array();
	public $default_table = "";
	public $selected_table = "";
	public $categories = array();
	private $setup = false;
	private $tables_name = "";
	private $columns_name = "";
	private $item_id = "";
	private $select_cols = array();
	private $search_cols = array();
	private $filter_cols = array("single"=>array(),"multiple"=>array());
	
	private function set_table_names(){
		global $wpdb;
		$this->tables_name = $wpdb->get_blog_prefix()."opendata_tables";
		$this->columns_name = $wpdb->get_blog_prefix()."opendata_columns";
	}
	
	public function get_rss($rss_field){
		$return = false;
		foreach($this->tables[$this->selected_table]["columns"] as $od_col_key=>$od_col){
			if($od_col["rss_type"]==$rss_field){
				$return = $od_col_key;
			}
			if($rss_field=="id"){
				if($od_col["is_id"]==1){
					$return = $od_col_key;
				}
			}
		}
		return $return;
	}
	
	public function get_filters($display_type="html"){
		$filters = array();
		foreach($this->filters as $filtkey=>$filt){
			foreach($filt as $f){
				$filters[] = ucwords($filtkey) . ": $f";
			}
		}
		foreach($this->search as $filt){
			$filters[] = "Search: $filt";
		}
		$text = "";
		if(count($filters)>0){
			if($display_type=="html"){
				$text .= "<ul><li>";
				$text .= implode("</li>\n<li>",$filters);
				$text .= "</li></ul>";
			} else {
				$text = implode(" | ",$filters);
			}
		}
		return $text;
	}
	
	private function get_table_config(){
		global $wpdb;
		$od_sql = "SHOW TABLES LIKE '".$this->tables_name."'";
		if($wpdb->query($od_sql)==1){
			$od_sql = "SHOW TABLES LIKE '".$this->columns_name."'";
			if($wpdb->query($od_sql)==1){
				$od_sql = "SELECT * FROM `".$this->tables_name."`";
				$this->tables = $wpdb->get_results($od_sql, ARRAY_A);
				foreach($this->tables as $od_table_key=>$od_table){
					$od_table_name = $wpdb->escape($od_table["name"]);
					$od_sql_col = "SELECT * FROM `".$this->columns_name."` WHERE `table_name` = '$od_table_name'";
					$this->tables[$od_table_name] = $od_table;
					$this->tables[$od_table_name]["columns"] = $wpdb->get_results($od_sql_col, ARRAY_A);
					$od_col_count = 1;
					foreach($this->tables[$od_table_name]["columns"] as $od_col_key=>$od_col){
						$od_col["order"] = $od_col_count++;
						$this->tables[$od_table_name]["columns"][$od_col["column_name"]] = $od_col;
						unset($this->tables[$od_table_name]["columns"][$od_col_key]);
					}
					if($od_table["is_default"]==1){
						$this->default_table = $od_table_name;
					}
					unset($this->tables[$od_table_key]);
				}
			}
		}
	}
	
	public function construct_sql_query(){
		global $wpdb;
		$sql = "SELECT ";
		foreach($this->tables[$this->selected_table]["columns"] as $col){
			$colname = $wpdb->escape($col["column_name"]);
			if($col["is_open"]==1){
				$this->select_cols[] = $colname;
				if($col["is_search"]==1){
					$this->search_cols[$colname] = $colname;
				}
				if($col["filter_type"]=="multiple"){
					$this->filter_cols["multiple"][$colname] = $colname;
				}
				if($col["filter_type"]=="single"){
					$this->filter_cols["single"][$colname] = $colname;
				}
			}
		}
		$sql .= "`" . implode("`, `",$this->select_cols) . "` ";
		$sql .= "FROM `".$wpdb->get_blog_prefix()."opendata_".$this->selected_table."` WHERE 1";
		return $sql;
	}
	
	public function construct_sql_item_query(){
		global $wpdb;
		$id_col = "";
		$sql = "SELECT ";
		foreach($this->tables[$this->selected_table]["columns"] as $col){
			$colname = $wpdb->escape($col["column_name"]);
			if($col["is_open"]==1){
				$this->select_cols[] = $colname;
			}
			if($col["is_id"]==1){
				$id_col = $colname;
			}
		}
		$sql .= "`" . implode("`, `",$this->select_cols) . "` ";
		$sql .= "FROM `".$wpdb->get_blog_prefix()."opendata_".$this->selected_table."` WHERE `$id_col` = '".$this->item_id."'";
		$sql .= " LIMIT 0,1";
		return $sql;
	}
	
	private function table_exists($table){
		$table_exists = false;
		foreach($this->tables as $od_table){
			if($od_table["name"]==$table){
				$table_exists = true;
			}
		}
		return $table_exists;
	}
	
	private function sort_categories(){
		foreach($this->categories as &$cat){
			$cat["records"] = $this->subval_sort($cat["records"],"count");
		}
	}
	
	function __construct(){
		$this->set_table_names();
		$this->get_table_config();
		$this->select_table();
	}
	
	public function set_item_id($od_id=""){
		global $wpdb;
		$this->item_id = $wpdb->escape($od_id);
	}
	
	public function get_data($customsql=""){
		global $wpdb;
		if($customsql==""){
			$sql = $this->construct_sql_query();
		} else {
			$sql = $customsql;
		}
		$initial_data = $wpdb->get_results($sql, ARRAY_A);
		foreach($initial_data as $datarow_key=>$datarow){
			$select = 0;
			$search_select = 0;
			foreach($datarow as $datacol_key=>&$datacol){
				if(isset($this->search_cols[$datacol_key])){
					foreach($this->search as $s){
						if(strpos(strtolower($datacol),strtolower($s))!==false){
							$search_select = 1;
						}
					}
				}
				if(isset($this->filter_cols["multiple"][$datacol_key])){
					$datacol = str_replace("\\;","<<semicolon>>",$datacol);
					$datacol = explode(";",$datacol);
					foreach($datacol as &$datacolitem){
						$colselect = 0;
						$datacolitem = trim($datacolitem);
						$datacolitem = str_replace("<<semicolon>>","\\;",$datacolitem);
						if($datacolitem!=""){
							if(isset($this->categories[$datacol_key]["records"][$datacolitem]))
								$this->categories[$datacol_key]["records"][$datacolitem]["count"]++;
							else {
								$this->categories[$datacol_key]["records"][$datacolitem]["name"]=$datacolitem;
								$this->categories[$datacol_key]["records"][$datacolitem]["count"]=1;
							}
						}
						if(isset($this->filters[$datacol_key])){
							foreach($this->filters[$datacol_key] as $colfilter){
								if(strtolower($colfilter)==strtolower($datacolitem)){
									$colselect = 1;
								}
							}
						}
						$select = $select + $colselect;
					}
				} else if(isset($this->filter_cols["single"][$datacol_key])){
					if($datacol!=""){
						if(isset($this->categories[$datacol_key]["records"][$datacol]))
							$this->categories[$datacol_key]["records"][$datacol]["count"]++;
						else {
							$this->categories[$datacol_key]["records"][$datacol]["name"]=$datacol;
							$this->categories[$datacol_key]["records"][$datacol]["count"]=1;
						}
					}
					if(isset($this->filters[$datacol_key])){
						foreach($this->filters[$datacol_key] as $colfilter){
							if(strtolower($colfilter)==strtolower($datacol)){
								$select = $select + 1;
							}
						}
					}
				}
			}
			if($select==count($this->filters)){
				if($search_select>0&&count($this->search)>0){
					$this->data[] = $datarow;
				} else if (count($this->search)==0){
					$this->data[] = $datarow;
				}
			}
		}
		unset($initial_data);
		$this->sort_categories();
		return $this->data;
	}
	
	public function get_item(){
		global $wpdb;
		$sql = $this->construct_sql_item_query();
		$this->item = $wpdb->get_results($sql, ARRAY_A);
		$this->item = $this->item[0];
		foreach($this->item as $datarow_key=>&$datarow){
			foreach($datarow as $datacol_key=>&$datacol){
				if($this->tables[$this->selected_table]["columns"][$datacol_key]["filter_type"]=="multiple"){
					$datacol = str_replace("\\;","<<semicolon>>",$datacol);
					$datacol = explode(";",$datacol);
					foreach($datacol as &$datacolitem){
						$colselect = 0;
						$datacolitem = trim($datacolitem);
						$datacolitem = str_replace("<<semicolon>>","\\;",$datacolitem);
					}
				}
			}
		}
		return $this->item;
	}
	
	function select_table($table=null) {
		if($table==null){
			$this->selected_table = $this->default_table;
		} else {
			if($this->table_exists($table)){
				$this->selected_table = $table;
			} else {
				$this->selected_table = $this->default_table;
			}
		}
	}
	
	public function set_filters($filters=null){
		global $wpdb;
		$success = false;
		if(is_array($filters)){
			foreach($filters as $filterkey=>$filter){
				$include = false;
				foreach($this->tables[$this->selected_table]["columns"] as $od_columns){
					if($od_columns["column_name"]==$filterkey){
						$include = true;
					}
				}
				if($include===true){
					if(is_array($filter)){
						if(implode("",$filter)!=""){
							$this->filters[$filterkey] = $filter;
						}
					} else {
						if($filter!=""){
							$this->filters[$filterkey] = array($filter);
						}
					}
					$success = true;
				}
			}
		}
		foreach($this->filters as &$filt1){
			foreach($filt1 as &$filt2){
				$filt2 = $wpdb->escape($filt2);
			}
		}
		return $this->filters;
	}
	
	public function set_search($search){
		global $wpdb;
		if(!is_array($search)){
			$search = explode(" OR ",$search);
		}
		foreach($search as $s){
			if($s!=""){
				$s = $wpdb->escape($s);
				//$s = str_replace(" AND ","%",$s);
				$this->search[] = $s;
			}
		}
		return $this->search;
	}
	
	private function get_item_template(){
		global $wpdb;
		$od_sql = "SELECT `item_template` FROM `".$this->tables_name."` WHERE `name` = '".$wpdb->escape($this->selected_table)."' LIMIT 0,1";
		$template = $wpdb->get_results($od_sql, ARRAY_A);
		$template = $template[0]["item_template"];
		return $template;		
	}
	
	private function get_item_sidebar_template(){
		global $wpdb;
		$od_sql = "SELECT `item_sidebar_template` FROM `".$this->tables_name."` WHERE `name` = '".$wpdb->escape($this->selected_table)."' LIMIT 0,1";
		$template = $wpdb->get_results($od_sql, ARRAY_A);
		$template = $template[0]["item_sidebar_template"];
		return $template;		
	}
	
	public function apply_template($od_template_type="main"){
		$od_data = $this->get_item();
		if($od_template_type=="sidebar"){
			$od_template = $this->get_item_sidebar_template();
			if($od_template==""){
				get_sidebar();
			} else {
				$text = "<div id=\"secondary\" class=\"widget-area\" role=\"complementary\">\n";
				$text .= "<aside id=\"text-9\" class=\"widget widget_text\">\n";
			}
		} else {
			$od_template = $this->get_item_template();
			$text = "";
		}
		if($od_template==""&&$od_template_type!="sidebar"){
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
		} else {
			preg_match_all("/%%(.*?)%%/",$od_template,$matches);
			
			$patterns_if = array();
			$patterns = array();
			$replacements_if = array();
			$replacements = array();
			
			foreach($matches[1] as $o){
				$patterns_if[] = "/{{(.*?)%%$o%%(.*?)}}/";
				$patterns[] = "/%%$o%%/";
				if(isset($od_data[$o])){
					$replacements[] = $od_data[$o];
					if($od_data[$o]!=""){
						$replacements_if[] = '${1}'.$od_data[$o].'${2}';
					} else {
						$replacements_if[] = "";
					}
				} else {
					$replacements_if[] = "";
					$replacements[] = "";
				}
			}

			$text2 = preg_replace($patterns_if,$replacements_if,$od_template);
			$text2 = preg_replace($patterns,$replacements,$text2);
			$text .= $text2;
		}
		if($od_template_type=="sidebar"&&$od_template!=""){
			$text .= "</aside>\n";
			$text .= "</div><!-- #secondary -->\n";
		}
		return $text;
	}
	
	function subval_sort($a,$subkey) {
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
				arsort($b); // sort the array of subkeys
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