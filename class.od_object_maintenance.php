<?php

class od_object_maintenance {
	
	public $tables = array();
	public $data = array();
	public $filters = array();
	public $search = array();
	public $default_table = "";
	public $selected_table = "";
	private $setup = false;
	private $tables_name = "";
	private $columns_name = "";
	public $filter_types = array("none","single","multiple");
	public $geography_types = array("none","latlng","lat","lng","kml_area");
	
	private function set_table_names(){
		global $wpdb;
		$this->tables_name = $wpdb->get_blog_prefix()."opendata_tables";
		$this->columns_name = $wpdb->get_blog_prefix()."opendata_columns";
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
					foreach($this->tables[$od_table_name]["columns"] as $od_col_key=>$od_col){
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
	
	private function table_exists($table){
		$table_exists = false;
		foreach($this->tables as $od_table){
			if($od_table["name"]==$table){
				$table_exists = true;
			}
		}
		return $table_exists;
	}
	
	function __construct(){
		$this->set_table_names();
		$this->get_table_config();
		$this->get_number_rows();
		$this->select_table();
	}
	
	function create_tables(){
		global $wpdb;
		$od_sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->get_blog_prefix()."opendata_tables` (
					  `name` varchar(255) NOT NULL,
					  `nicename` varchar(255) DEFAULT NULL,
					  `description` longtext,
					  `is_default` tinyint(1) NOT NULL DEFAULT '0',
					  `item_template` longtext,
					  PRIMARY KEY (`name`)
					)";
		$wpdb->query($od_sql);
		$od_sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->get_blog_prefix()."opendata_columns` (
					  `table_name` varchar(255) NOT NULL,
					  `column_name` varchar(255) NOT NULL,
					  `nice_name` varchar(255) DEFAULT NULL,
					  `description` longtext,
					  `mysql_type` varchar(255) DEFAULT NULL,
					  `display_type` varchar(255) DEFAULT NULL,
					  `filter_type` varchar(8) DEFAULT NULL,
					  `geog_type` varchar(20) DEFAULT NULL,
					  `is_open` tinyint(1) NOT NULL DEFAULT '1',
					  `is_id` tinyint(1) NOT NULL DEFAULT '0',
					  `is_timestamp` tinyint(1) NOT NULL DEFAULT '0',
					  `is_search` tinyint(1) NOT NULL DEFAULT '1',
					  `linked_data_url` varchar(255) DEFAULT NULL,
					  PRIMARY KEY (`table_name`,`column_name`)
					)";
		$wpdb->query($od_sql);
		$this->setup = true;
	}
	
	function add_table($od_table_name, $od_table_nicename = "", $od_table_description = "", $columns = array()){
		global $wpdb;
		if(count($columns)==0){
			$columns[] = array("column_name"=>"od_timestamp","description"=>"Timestamp for the row","is_timestamp"=>true);
			$columns[] = array("column_name"=>"od_id","description"=>"Unique identifier","is_id"=>true);
			$columns[] = array("column_name"=>"od_value","description"=>"Data value");
		}
		if($od_table_nicename=="")
			$od_table_nicename = "NULL";
		else
			$od_table_nicename = "'".$wpdb->escape($od_table_nicename)."'";
		if($od_table_description=="")
			$od_table_description = "NULL";
		else
			$od_table_description = "'".$wpdb->escape($od_table_description)."'";
		
		$od_sql = "REPLACE INTO `".$wpdb->get_blog_prefix()."opendata_tables` ( `name`, `nicename`, `description` ) VALUES ( $od_table_name, $od_table_nicename, $od_table_description )";
		$wpdb->query($od_sql);
		$count=0;
		foreach($columns as &$od_c){
			if(!isset($od_c["column_name"])){
				$od_c["column_name"]="column_$count";
				$count++;
			}
			$od_c_keys = array_keys($od_c);
			$od_sql = "REPLACE INTO `".$wpdb->get_blog_prefix()."opendata_columns` ( `".implode($od_c_keys,"`,`")."` ) VALUES ( `".implode($od_c,"`,`")."`  )";
			$wpdb->query($od_sql);
		}
	}
	
	function update_table($od_table_edit){
		global $wpdb;
		$od_table_edit["name"] = strtolower(preg_replace("/[^a-zA-Z0-9_]/"," ",str_replace(" ","_",$od_table_edit["nicename"])));
		$od_sql = "REPLACE INTO `".$this->tables_name."` SET ";
		$od_count = 0;
		foreach($od_table_edit as $od_key=>$od_c){
			if($od_key!="columns"){
				if($od_count>0){$od_sql .= ",";}
				$od_sql .= " `$od_key` = ";
				if($od_c==""){
					$od_sql .= "NULL";
				} else {
					$od_sql .= "'" . $wpdb->escape($od_c) . "'";
				}
				$od_count++;
			}
		}
		$od_sql .= "";
		$wpdb->query($od_sql);
		foreach($od_table_edit["columns"] as $od_col_key=>&$od_col_edit){
			$od_col_edit["table_name"] = $od_table_edit["name"];
			$od_col_edit["column_name"] = strtolower(preg_replace("/[^a-zA-Z0-9_]/"," ",str_replace(" ","_",$od_col_edit["nice_name"])));
			if($od_col_key!=$od_col_edit["column_name"]){
				$od_sql = "ALTER TABLE `".$wpdb->get_blog_prefix()."opendata_".$od_col_edit["table_name"]."` CHANGE `$od_col_key` `".$od_col_edit["column_name"]."` '".$od_col_edit["mysql_type"]."'";
				$wpdb->query($od_sql);
				$od_sql = "DELETE FROM `".$wpdb->get_blog_prefix()."opendata_columns` WHERE `column_name` = '$od_col_key' AND `table_name` = '".$od_col_edit["table_name"]."'";
				$wpdb->query($od_sql);
			}
			$od_sql = "REPLACE INTO `".$wpdb->get_blog_prefix()."opendata_columns` SET ";
			$od_count = 0;
			$od_col_names = array("is_open","is_search","is_id","is_timestamp");
			foreach($od_col_names as $od_col_name){
				if(isset($od_col_edit[$od_col_name]))
					$od_col_edit[$od_col_name] = 1;
				else
					$od_col_edit[$od_col_name] = 0;
			}
			foreach($od_col_edit as $od_key=>$od_c){
				if($od_count>0){$od_sql .= ",";}
				$od_sql .= " `$od_key` = ";
				if($od_c===""){
					$od_sql .= "NULL";
				} else {
					$od_sql .= "'" . $wpdb->escape($od_c) . "'";
				}
				$od_count++;
			}
			$wpdb->query($od_sql);
		}
		return true;
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
		return $this->selected_table;
	}
	
	function get_number_rows($table=null) {
		global $wpdb;
		foreach($this->tables as $t){
			$sql = "SELECT COUNT(*) AS rows FROM `".$wpdb->get_blog_prefix()."opendata_".$t["name"]."`";
			$rows = $wpdb->get_results($sql, ARRAY_A);
			$this->tables[$t["name"]]["rows"] = $rows[0]["rows"];
		}
	}
	
}

?>