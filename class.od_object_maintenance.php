<?php

class od_object_maintenance extends od_object {
	
	public $filter_types = array("none"=>"None","single"=>"Single","multiple"=>"Multiple");
	public $geography_types = array("none"=>"None","latlng"=>"Latitude and Longitude","lat"=>"Latitude","lng"=>"Longitude","kml_area"=>"KML area");
	public $column_data_types = array(
		"text"=>array("name"=>"Text","mysql"=>"VARCHAR (255)"),
		"number"=>array("name"=>"Number","mysql"=>"DOUBLE"),
		"datetime"=>array("name"=>"Datetime","mysql"=>"DATETIME")
		);
	public $default_settings = array(
		"name"=>"",
		"nice_name"=>"",
		"previous_name"=>"",
		"description"=>"",
		"is_default"=>false,
		"item_template"=>"",
		"sidebar_item_template"=>"",
		"filter_columns"=>array(),
		"search_columns"=>array(),
		"select_columns"=>array(),
		"columns"=>array(),
		"id"=>false
		);
	public $default_column_settings = array(
		"column_name"=>"",
		"nice_name"=>"",
		"description"=>"",
		"mysql_type"=>"VARCHAR (255)",
		"display_type"=>"text",
		"filter_type"=>false,
		"geog_type"=>"",
		"rss_type"=>"",
		"is_open"=>true,
		"is_id"=>false,
		"is_timestamp"=>"",
		"is_search"=>false,
		"is_html"=>true,
		"linked_data_url"=>"",
		"order"=>"1",
		"previous_name"=>false,
		"previous_type"=>false,
		"changed"=>false,
		"delete"=>false
		);
	public $errors = array();
		
	function __construct(){
		parent::__construct();
		if($this->tables){
			$this->setup = true;
		} else {
			$this->setup = false;
		}
	}
	
	function admin_screen(){
		if(count($this->tables)>0):?>
			<table class="wp-list-table widefat fixed posts" cellspacing="0">
				<thead>
					<tr>
						<th scope='col' id='cb' class='manage-column column-cb check-column'  style="">
							<input type="checkbox" />
						</th>
						<th scope='col' id='title' class='manage-column column-title sortable desc'  style="">
							<a href="admin.php?page=open-data&orderby=title&order=asc"><span>Title</span><span class="sorting-indicator"></span></a>
						</th>
						<th scope='col' id='default' class='manage-column column-tags'  style="">Default</th>
						<th scope='col' id='columns' class='manage-column column-tags sortable desc'  style="">
							<a href="admin.php?page=open-data&orderby=columns&order=asc"><span>Columns</span><span class="sorting-indicator"></span></a>
						</th>
						<th scope='col' id='records' class='manage-column column-tags sortable desc' style="">
							<a href="admin.php?page=open-data&orderby=records&order=asc"><span>Records</span><span class="sorting-indicator"></span></a>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th scope='col' id='cb' class='manage-column column-cb check-column'  style="">
							<input type="checkbox" />
						</th>
						<th scope='col' id='title' class='manage-column column-title sortable desc'  style="">
							<a href="admin.php?page=open-data&orderby=title&order=asc"><span>Title</span><span class="sorting-indicator"></span></a>
						</th>
						<th scope='col' id='default' class='manage-column column-tags'  style="">Default</th>
						<th scope='col' id='columns' class='manage-column column-tags sortable desc'  style="">
							<a href="admin.php?page=open-data&orderby=columns&order=asc"><span>Columns</span><span class="sorting-indicator"></span></a>
						</th>
						<th scope='col' id='records' class='manage-column column-tags sortable desc' style="">
							<a href="admin.php?page=open-data&orderby=records&order=asc"><span>Records</span><span class="sorting-indicator"></span></a>
						</th>
					</tr>
				</tfoot>
				<tbody id="the-list">
			<?php foreach($this->tables as $table_key=>$table_name):
				$t = $this->get_table_config($table_key); ?>
					<tr id='post-1' class='alternate author-self status-publish format-default iedit' valign="top">
						<th scope="row" class="check-column"><input type="checkbox" name="post[]" value="1" /></th>
						<td class="post-title page-title column-title">
							<strong><a class="row-title" 
									href="admin.php?page=open-data&od_table=<?php echo $t["name"]; ?>&action=edit" 
									title="Edit &#8220;<?php echo $t["nice_name"]; ?>&#8221;"><?php echo $t["nice_name"]; ?></a></strong>
							<p><?php echo $t["description"]; ?></p>
							<div class="row-actions">
								<span class='edit'><a href="admin.php?page=open-data&od_table=<?php echo $t["name"]; ?>&action=edit" title="Edit this dataset">Edit</a> | </span>
								<span class='trash'><a class='submitdelete' title='Move this dataset to the Trash' href='admin.php?page=open-data&od_table=<?php echo $t["name"]; ?>&action=trash'>Trash</a> | </span>
								<span class='view'><a href="admin.php?page=open-data&od_table=<?php echo $t["name"]; ?>&action=data" title="View this dataset" rel="permalink">View</a></span>
							</div>
						</td>
						<td class="categories column-categories">
							<input type="radio" name="default" <?php if($t["is_default"]==1){ echo 'checked="checked" '; } ?>/>
						</td>
						<td class="tags column-tags"><?php echo count($t["columns"]); ?></td>
						<td class="tags column-tags"><?php echo $this->get_number_rows($table_key); ?></td>
				</tr>
			<?php endforeach; ?>
				</tbody>
			</table>
		<?php else:  ?>
			<p>No data has yet been created. Add a new table to get started</p>
			<form method="post" enctype="multipart/form-data">
			<h3>Quick add</h3>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label for="quickadd_name">Table name</label></th>
						<td>
							<input type="text" id="quickadd_name" name="quickadd[name]" placeholder="Table name" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="quickadd_file">Upload data (CSV)</label></th>
						<td>
							<input type="file" id="quickadd_name" name="quickaddfile" />
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" name="quickaddsubmit" id="quickaddsubmit" class="button-primary" value="Add new table" />
			</p>			
			</form>
		<?php endif;
	}
	
	function add_new_table_admin($table=false, $attempted_settings=false){
		global $drk_table_types;
		$existing_table = false;
		if($table&&$this->table_exists($table)){
			$t = $this->get_table_config($table);
			$existing_table = true;
		} else if($attempted_settings) {
			$t = drk_merge_arrays($this->default_settings, $attempted_settings, false);
		} else {
			$t = $this->default_settings;
		}
		if($this->default_table==$t["name"]){
			$default_checked = 'checked="checked" ';
		} else {
			$default_checked = '';
		}
		?>
		<form method="post" enctype="multipart/form-data">
			<div id="titlediv">
				<div id="titlewrap">
					<label class="hide-if-no-js" style="visibility:hidden" id="title-prompt-text" for="title">Table name</label>
					<input type="text" size="30" tabindex="1" value="<?php echo $t["nice_name"]; ?>" placeholder="Table name" name="od_new_table[nice_name]" id="title" />
					<input type="hidden" class="regular-text" value="<?php echo $t["name"]; ?>" placeholder="Table name" name="od_new_table[previous_name]" id="od_new_table__previous_name" />
				</div>
				<div class="inside">
					<div id="edit-slug-box">
					<strong>Permalink:</strong>
						<span id="sample-permalink"><?php echo home_url('/'); ?><span id="editable-post-name" title="Click to edit this part of the permalink"><?php echo $t["name"]; ?></span>/data</span>
					</div>
				</div>
			</div>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button-primary" value="<?php if($existing_table){echo "Make changes";} else {echo "Add new table";} ?>" />
			</p>
			<div id="tabs">
			<h3 class="nav-tab-wrapper">
			<ul>
				<li><a href="#table-settings" class="nav-tab"><span>Table settings</span></a></li>
				<li><a href="#columns-settings" class="nav-tab"><span>Columns</span></a></li>
				<li><a href="#templates" class="nav-tab"><span>Templates</span></a></li>
			</ul>
				<a href="<?php echo home_url('/'); ?>wp-admin/admin.php?page=open-data&od_table=<?php echo $t["name"]; ?>&action=data" class="nav-tab">Data</a>
			</h3>
			<div id="table-settings">
			<h3>Settings</h3>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label for="od_new_table_description">Table description</label></th>
						<td>
							<p><textarea type="text" class="medium-text" rows="3" cols="55" value="<?php echo $t["description"]; ?>" placeholder="Table description" name="od_new_table[description]" id="od_new_table_description"></textarea></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="od_new_table_default">Make default</label></th>
						<td><input type="checkbox" value="is_default" <?php $default_checked; ?>name="od_new_table[is_default]" id="od_new_table_default"></td>
					</tr>
				</tbody>
			</table>
			</div>
			<div id="columns-settings">
			<h3>Table columns <button class="button" id="new_column">Add new column</button></h3> 
			<table class="wp-list-table widefat" id="table_columns" cellspacing="0">
				<?php foreach($drk_table_types as $drktt):?>
				<<?php echo $drktt; ?>>
				<tr>
					<th>&nbsp;</th>
					<th>Column name</th>
					<th>Description</th>
					<th>Data type</th>
					<th>Filter</th>
					<th>Search</th>
					<th>Open</th>
					<th>ID</th>
					<th>Linked data URL</th>
					<th style="color:red;">Delete</th>
				</tr>
				</<?php echo $drktt; ?>>
				<?php endforeach; ?>
				<tbody>
				<?php
					if(count($t["columns"])==0){
						$t["columns"][] = $this->default_column_settings;
					}
				foreach($t["columns"] as $column): 
				$column = drk_merge_arrays($this->default_column_settings, $column, false); ?>
				<tr class="column" valign="middle" id="column_<?php echo $column["order"]; ?>">
					<td>
						<input type="text" name="od_new_table[columns][<?php echo $column["order"]; ?>][order]" value="<?php echo $column["order"]; ?>" style="width:45px;" size="3" />
						<input type="hidden" name="od_new_table[columns][<?php echo $column["order"]; ?>][previous_name]" value="<?php echo $column["column_name"]; ?>" /> 
						<input type="hidden" name="od_new_table[columns][<?php echo $column["order"]; ?>][changed]" value="<?php echo $column["changed"]; ?>" /> 
						<input type="hidden" name="od_new_table[columns][<?php echo $column["order"]; ?>][delete]" value="<?php echo $column["delete"]; ?>" /> 
					</td>
					<td><input type="text" name="od_new_table[columns][<?php echo $column["order"]; ?>][nice_name]" value="<?php echo $column["nice_name"]; ?>" placeholder="Column name" /></td>
					<td><input type="text" name="od_new_table[columns][<?php echo $column["order"]; ?>][description]" value="<?php echo $column["description"]; ?>" placeholder="Description" /></td>
					<td>
						<select name="od_new_table[columns][<?php echo $column["order"]; ?>][display_type]">
						<?php foreach($this->column_data_types as $key=>$value): ?>
							<option value="<?php echo $key; ?>"<?php if($key==$column["display_type"]){ echo 'selected="selected" ';} ?>><?php echo $value["name"]; ?></option>
						<?php endforeach; ?>
						</select>
					</td>
					<td>
						<select name="od_new_table[columns][<?php echo $column["order"]; ?>][filter_type]">
						<?php foreach($this->filter_types as $key=>$value): ?>
							<option value="<?php echo $key; ?>"<?php if($key==$column["filter_type"]){ echo 'selected="selected" ';} ?>><?php echo $value; ?></option>
						<?php endforeach; ?>
						</select>
					</td>
					<td><input type="checkbox" <?php if($column["is_search"]){echo 'checked="checked" ';} ?> name="od_new_table[columns][<?php echo $column["order"]; ?>][is_search]" value="is_search"/></td>
					<td><input type="checkbox" name="od_new_table[columns][<?php echo $column["order"]; ?>][is_open]" value="is_open" <?php if($column["is_open"]){echo 'checked="checked" ';} ?>/></td>
					<td><input type="radio" name="od_new_table[id]" <?php if($column["is_id"]){echo 'checked="checked" ';} ?>value="<?php echo $column["order"]; ?>" /></td>
					<td><input type="url" name="od_new_table[columns][<?php echo $column["order"]; ?>][linked_data_url]" value="<?php echo $column["linked_data_url"]; ?>" placeholder="Linked data URL" /></td>
					<td><input type="checkbox" name="od_new_table[columns][<?php echo $column["order"]; ?>][delete]" class="column_delete" value="delete" /></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			<div id="templates">
			<h3>Templates</h3>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label for="od_new_table_item_template">Item template</label></th>
						<td>
							<p><textarea type="text" class="large-text code" rows="10" cols="50" value="<?php echo $t["item_template"]; ?>" placeholder="Item template" name="od_new_table[item_template]" id="od_new_table_item_template"></textarea></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="od_new_table_sidebar_template">Item sidebar template</label></th>
						<td>
							<p><textarea type="text" class="large-text code" rows="10" cols="50" value="<?php echo $t["item_sidebar_template"]; ?>" placeholder="Item sidebar template" name="od_new_table[item_sidebar_template]" id="od_new_table_sidebar_template"></textarea></p>
						</td>
					</tr>
				</tbody>
			</table>
			</div>
			</div>
		</form>
		<?php
	}
	
	function table_data_admin($table=false){
		global $drk_table_types;
		$existing_table = false;
		if($this->select_table($table)){
			$t = $this->get_table_config();
			$existing_table = true;
		}
		$this->refresh_categories();
		?>
		<form method="post" enctype="multipart/form-data">
			<div id="titlediv">
				<div id="titlewrap">
					<label class="hide-if-no-js" style="visibility:hidden" id="title-prompt-text" for="title">Table name</label>
					<input type="text" size="30" tabindex="1" value="<?php echo $t["nice_name"]; ?>" placeholder="Table name" name="od_new_table[nice_name]" id="title" />
					<input type="hidden" class="regular-text" value="<?php echo $t["name"]; ?>" placeholder="Table name" name="od_new_table[previous_name]" id="od_new_table__previous_name" />
				</div>
				<div class="inside">
					<div id="edit-slug-box">
					<strong>Permalink:</strong>
						<span id="sample-permalink"><?php echo home_url('/'); ?><span id="editable-post-name" title="Click to edit this part of the permalink"><?php echo $t["name"]; ?></span>/data</span>
					</div>
				</div>
			</div>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button-primary" value="Make changes" />
			</p>
			<h3 class="nav-tab-wrapper">
			<ul>
				<li><a href="<?php echo home_url('/'); ?>wp-admin/admin.php?page=open-data&od_table=<?php echo $t["name"]; ?>&action=edit#table-settings" class="nav-tab"><span>Table settings</span></a></li>
				<li><a href="<?php echo home_url('/'); ?>wp-admin/admin.php?page=open-data&od_table=<?php echo $t["name"]; ?>&action=edit#columns-settings" class="nav-tab"><span>Columns</span></a></li>
				<li><a href="<?php echo home_url('/'); ?>wp-admin/admin.php?page=open-data&od_table=<?php echo $t["name"]; ?>&action=edit#templates" class="nav-tab"><span>Templates</span></a></li>
			</ul>
				<a href="<?php echo home_url('/'); ?>wp-admin/admin.php?page=open-data&od_table=<?php echo $t["name"]; ?>&action=data" class="nav-tab">Data</a>
			</h3>
			<div id="data">
			<h3>Data</h3>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label for="od_new_table_item_template">Upload new rows (CSV)</label></th>
						<td>
							<input type="file" />
						</td>
					</tr>
				</tbody>
			</table>
			<br>
			<table class="wp-list-table widefat" id="table_columns" cellspacing="0">
			<?php
				if($this->get_number_rows()>0):
					$existing_data = $this->get_data();
					foreach($drk_table_types as $drktt):?>
					<<?php echo $drktt; ?>>
						<tr>
							<th>Row</th>
						<?php foreach($t["columns"] as $column): ?>
							<th><?php echo $column["nice_name"]; ?></th>
						<?php endforeach; ?></tr>
					</<?php echo $drktt; ?>>
					<?php endforeach; ?>
					<tbody>
					<?php $count = 1;
					foreach ($existing_data as $data): ?>
						<tr>
							<td><?php echo $count; ?></td>
						<?php foreach($t["columns"] as $key=>$column): 
							$value = $data[$key]; 
							if(is_array($value)){$value = implode(";",$value);} ?>
							<td><input type="text" value="<?php echo $value; ?>" /></td>
						<?php endforeach; ?></tr>
						<?php $count++;
							endforeach; ?>
					</tbody>
				<?php else : 
					foreach($drk_table_types as $drktt):?>
					<<?php echo $drktt; ?>>
						<tr>
							<th>Row</th>
						<?php foreach($t["columns"] as $column): ?>
							<th><?php echo $column["nice_name"]; ?></th>
						<?php endforeach; ?></tr>
					</<?php echo $drktt; ?>>
					<?php endforeach; ?>
					<tbody>
						<tr>
							<td>1</td>
						<?php foreach($t["columns"] as $column): ?>
							<td><input type="text" value="" /></td>
						<?php endforeach; ?></tr>
					</tbody>
			<?php endif; ?>
			</table>
			</div>
		</form>
	<?php
	}
	
	/* 
	 *  For renaming a table
	 */
	function rename_table($old_name, $new_name) {
		$success = false;
		if($this->table_exists($old_name)&&!$this->table_exists($new_name)){
			// step 1: transfer old options to new table
			$old_options = get_option("od_table_" . $old_name);
			delete_option("od_table_" . $old_name);
			add_option("od_table_" . $new_name, $old_options);
			
			// step 2: change name in tables option 
			$this->tables[$new_name] = array("name"=>$old_options["nice_name"]);
			unset($this->tables[$old_name]);
			update_option("od_tables", $this->tables);
			
			// step 3: update default table name if needed
			if(get_option("od_default_table")==$old_name){
				update_option("od_default_table",$new_name);
			}
			
			// step 4: change SQL table name
			global $wpdb;
			$sql = 'RENAME TABLE `' . $wpdb->get_blog_prefix() . 'opendata_' . $wpdb->escape($old_name) . '` TO ';
			$sql .= '`' . $wpdb->get_blog_prefix() . 'opendata_' . $wpdb->escape($new_name) . '`';
			if($wpdb->query($sql)){
				$success = true;
				$this->action[] = 'Renamed table "' . $old_name . '" to "' . $new_name . '"';
			} else {
				$success = false;
				$this->error[] = 'Could not rename table "' . $old_name . '" to "' . $new_name . '": ' . $sql;
			}
		}
		return $success;
	}
	
	/* 
	 *  For deleting a table
	 */
	function delete_table($table) {
		$success = false;
		if($this->table_exists($table)){
			// step 1: remove table options
			delete_option("od_table_" . $table);
			
			// step 2: remove from tables option 
			unset($this->tables[$table]);
			update_option("od_tables", $this->tables);
			
			// step 3: update default table name if needed
			if(get_option("od_default_table")==$table){
				update_option("od_default_table","");
			}
			
			// step 4: Remove SQL table
			global $wpdb;
			$sql = 'DROP TABLE `' . $wpdb->get_blog_prefix() . 'opendata_' . $wpdb->escape($table) . '`';
			if($wpdb->query($sql)){
				$success = true;
				$this->action[] = 'Deleted table "' . $table;
			} else {
				$success = false;
				$this->error[] = 'Could not delete table "' . $table . '" : ' . $sql;
			}
		}
		return $success;
	}
	
	function add_table_data($filename, $table, $sep=false,  $header_row=true, $quickadd=false){
		if(!$sep){
			$sep = ",";
		}
		$csv_file = fopen($filename, "r");
		$add_now = false;
		
		$table_name = $table;
		$table = $this->sluggify($table);
		if($this->table_exists($table)){
			$quickadd = false;
			$add_now = true;
		}
		
		$find_number = '/^[-+]?[0-9]*\.?[0-9]+$/';
		$columns = array();
		$row = array();
		
		while (($data = fgetcsv($csv_file, 0, $sep)) !== FALSE) {
			if(count($row)==0&&count($columns)==0){
				foreach($data as $key=>$value){
					$this_column = array();
					if($header_row){
						$this_column["name"] = $value;
					} else {
						$this_column["name"] = $key;
					}
					$this_column["string"] = 0;
					$this_column["number"] = 0;
					$columns[] = $this_column;
				}
			}
			
			if(!$header_row){
				$this_row = array();
				foreach($data as $key=>$value){
					$this_row[$columns[$key]["name"]] = $value;
					if(preg_match($find_number, $value)>0){
						$columns[$key]["number"]++;
					} else {
						$columns[$key]["string"]++;
					}
				}
				$row[] = $this_row;
			}
			$header_row = false;
			
			if($add_now){
				$this->add_row($data, $table);
			}
			
		}
		
		if($quickadd){
			$table_settings = $this->default_settings;
			$table_settings["name"] = $table;
			$table_settings["nice_name"] = $table_name;
			$order = 1;
			foreach($columns as $col){
				$col_settings = $this->default_column_settings;
				$col_settings["column_name"] = $this->sluggify($col["name"]);
				$col_settings["nice_name"] = $col["name"];
				if($col["number"]>0&&$col["string"]==0){
					$col_settings["display_type"] = "number";
				} else {
					$table_settings["filter_columns"][$col_settings["column_name"]] = array("filter_type"=>"single", "categories"=>array() );
					$table_settings["search_columns"][$col_settings["column_name"]] = $col_settings["column_name"];
					$col_settings["filter_type"] = "single";
					$col_settings["is_search"] = true;
				}
				$col_settings["mysql_type"] = $this->column_data_types[$col_settings["display_type"]]["mysql"];
				$col_settings["order"] = $order;
				$table_settings["select_columns"][$col_settings["column_name"]] = $col_settings["column_name"];
				$table_settings["columns"][$col_settings["column_name"]] = $col_settings;
				$order++;
			}
			$this->add_new_table($table_settings);
			foreach($row as $r){
				$this->add_row($r, $table);
			}
		}
	}
	
	/* 
	 *  For adding new tables or creating new ones
	 *
	 *	Function creates the settings variable for the table, and carries out the required SQL statements
	 */
	function add_row($data, $table=false){
		global $wpdb;
		$this->select_table($table);
		$cols = array();
		$vals = array();
		foreach($data as $column=>$value){
			$cols[] = '`' . $wpdb->escape($column) . '`';
			if(isset($this->table_config["filter_columns"][$column])){
				$this->add_category($column, $value);
				if($this->table_config["filter_columns"][$column]["filter_type"]=="multiple"){
					if( substr($value, -1)!=";" ) { $value .= ";"; }
				}
			}
			$vals[] = "'" . $wpdb->escape($value) . "'";
		}
		$sql = drk_implode(", ", $cols, "REPLACE INTO `" . $this->turl() . "` ( ", ") ");
		$sql .= drk_implode(", ", $vals, " VALUES (", ")");
		if($sql!= ''){
			return $wpdb->query($sql);
		} else {
			return false;
		}
	}
	
	/* 
	 *  Add another instance of a category
	 */
	function add_category($column, $value, $table=false){
		$this->select_table($table);
		if(isset($this->table_config["filter_columns"][$column])){
			if(is_array($value)){
				$value = $value;
			} else if($this->table_config["filter_columns"][$column]["filter_type"]=="multiple"){
				$value = str_replace("\\;","<<semicolon>>",$value); // temporarily replace any escaped semicolons
				$value = explode(";", $value);
			} else {
				$value = array($value);
			}
			foreach($value as $val){
				if($val!=""){
					$val = str_replace("<<semicolon>>",";",$val); // put back the escaped semicolon
					$val_slug = $this->sluggify($val);
					if(isset($this->table_config["filter_columns"][$column]["categories"][$val_slug])){
						$this->table_config["filter_columns"][$column]["categories"][$val_slug]["records"]++;
					} else {
						$this->table_config["filter_columns"][$column]["categories"][$val_slug] = array( "records"=>1, "name"=>$val );
					}
				}
			}
			
			$this->sort_categories();
			update_option("od_table_" . $this->table_config["name"], $this->table_config);
			
		} else {
			return false;
		}
	}
	
	/* 
	 *  Remove an instance of a category
	 */
	function remove_category($column, $value, $table=false){
		$this->select_table($table);
		if(isset($this->table_config["filter_columns"][$column])){
			if($this->table_config["filter_columns"][$column]["filter_type"]=="multiple"){
				$value = explode(";", $value);
			} else {
				$value = array($value);
			}
			foreach($value as $val){
				$val_slug = $this->sluggify($val);
				if(isset($this->table_config["filter_columns"][$column]["categories"][$val_slug])){
					$this->table_config["filter_columns"][$column]["categories"][$val_slug]["records"]--;
				}
				if($this->table_config["filter_columns"][$column]["categories"][$val_slug]["records"]=0){
					unset($this->table_config["filter_columns"][$column]["categories"][$val_slug]);
				}
			}
			$this->sort_categories();
			update_option("od_table_" . $this->table_config["name"], $this->table_config);
			
		} else {
			return false;
		}
	}
	
	/**
	 * Sort each of the categories according to how many times each value appears (descending)
	**/
	private function sort_categories($column=false, $table=false){
		foreach($this->table_config["filter_columns"] as &$columns){
			$columns["categories"] = $this->subval_sort($columns["categories"],"records"); // uses subval_sort function
		}
		update_option("od_table_" . $this->table_config["name"], $this->table_config);
	}
	
	/**
	 * Sort each of the categories according to how many times each value appears (descending)
	**/
	private function refresh_categories($table=false){
		foreach($this->table_config["filter_columns"] as $key=>$column){
			unset($this->table_config["filter_columns"][$key]["categories"]);
			$this->table_config["filter_columns"][$key]["categories"] = array();
		}
		update_option("od_table_" . $this->table_config["name"], $this->table_config);
		$catdata = $this->get_data();
		foreach($catdata as $d){
			foreach($d as $key=>$value){
				$this->add_category($key, $value);
			}
		}
		$this->sort_categories();
		update_option("od_table_" . $this->table_config["name"], $this->table_config);
	}
	
	/* 
	 *  For adding new tables or creating new ones
	 *
	 *	Function creates the settings variable for the table, and carries out the required SQL statements
	 */
	function add_new_table($new_table_options=false){
		$success = false; // success is false by default
		if($new_table_options){ // 								if options have been provided
			if(isset($new_table_options["id"])){ //				and if an ID column has been set
				if(isset($new_table_options["nice_name"])){ //	and if a table name has been set ... then we can begin
					
					$delete_columns = array(); // used to store any old columns that should be deleted
					$alter_columns = array(); // used to store any alterations to old columns
					$add_columns = array(); // used to store any new columns
					
					$id = $new_table_options["id"]; // column id is stored in the id field as a column number
					$primary_key = false; // primary key is not known yet
					$new_table_options = drk_merge_arrays($this->default_settings, $new_table_options, false); // merge in default settings
					$new_table_options["name"] = $this->sluggify($new_table_options["nice_name"]); // remove non alpha characters from name
					
					// set the default setting if the table is the default one
					if(isset($new_table_options["is_default"])){
						$this->default_table = $new_table_options["name"];
						update_option("od_default_table" , $new_table_options["name"]);
					}
					
					// if a new name has been provided then change the name
					if($new_table_options["previous_name"]!=""
						&&$new_table_options["previous_name"]!=$new_table_options["name"]
						&&$this->table_exists($new_table_options["previous_name"])){
							$this->rename_table($new_table_options["previous_name"], $new_table_options["name"]);
					}
					
					// if the table already exists then it is not a new table (changes the SQL that needs to be run);
					if($this->table_exists($new_table_options["name"])){
						$new_table = false;
					} else {
						$new_table = true;
					}
					
					$new_columns = array();
					
					// go through each column in turn
					foreach($new_table_options["columns"] as $key=>&$value){
						$value["column_name"] = $this->sluggify($value["nice_name"]); // create slug for column
						
						// first check if the column should be deleted
						if($value["delete"]=="true"){
							$delete_columns[] = $this->sluggify($value["column_name"]);
							unset($new_table_options["columns"][$key]);
						} else { // if not, proceed 
							$value = drk_merge_arrays($this->default_column_settings, $value, false); // merge in default settings
							if($value["order"]==$id){
								$value["is_id"] = true; // if the column number is the same as the id column, this is the ID
								$primary_key = $value["column_name"]; // set the primary key to be used
							}
							if($value["is_open"]=="is_open"){
								$value["is_open"] = true; // set is_open to true
								$new_table_options["select_columns"][$value["column_name"]] = $value["column_name"];
							} else {
								$value["is_open"] = false;
							}
							if($value["is_search"]=="is_search"){
								$value["is_search"] = true; // set search to true
								$new_table_options["search_columns"][$value["column_name"]] = $value["column_name"];
							} else {
								$value["is_search"] = false;
							}
							if($value["filter_type"]=="single"||$value["filter_type"]=="multiple"){
								$new_table_options["filter_columns"][$value["column_name"]] = array( "filter_type"=>$value["filter_type"], "categories"=>array() );
							} else {
								$value["filter_type"] = false;
							}
							$value["mysql_type"] = $this->column_data_types[$value["display_type"]]["mysql"];
							
							if(($value["previous_name"]!=""&&$value["previous_name"]!= $value["column_name"])
								||($value["previous_type"]!=""&&$value["previous_type"]!= $value["display_type"])){
									$alter_columns[] = array("previous_name"=>$value["previous_name"],
																"column_name"=>$value["column_name"],
																"mysql_type"=>$value["mysql_type"]);
							}
							
							if(!$new_table&&$value["previous_name"]==""){
								$add_columns[] = array("column_name"=>$value["column_name"],"mysql_type"=>$value["mysql_type"]);
							}
						}
						$new_columns[$value["column_name"]] = $value;
						unset($new_table_options["columns"][$key]);
					}
					$new_table_options["columns"] = $new_columns;
					$new_table_options["id"] = $primary_key;
					
					$this->table_config = $new_table_options; // set the current table_config to the options we've chosen
					global $wpdb;
					$query_queue = array();
					
					if($new_table){
						$new_table_sql = 'CREATE TABLE IF NOT EXISTS `' . $this->turl($this->table_config["name"]) . '` (';
						$column_count = 0;
						foreach($this->table_config["columns"] as $new_column){
							if($column_count>0){$new_table_sql .= ', ';}
							$new_table_sql .= ' `'. $wpdb->escape($new_column["column_name"]) .'` ' . $wpdb->escape($new_column["mysql_type"]) . ' DEFAULT NULL';
							$column_count++;
						}
						$new_table_sql .= ')';
						if($primary_key){ $new_table_sql .= ' PRIMARY KEY (`' . $wpdb->escape($primary_key) . '`)'; }
						$query_queue["Create new table"] = $new_table_sql;
					} else {
						foreach($delete_columns as $delete_column){
							$sql_column = 'ALTER TABLE `' . $this->turl($this->table_config["name"]) . '` ';
							$sql_column .= 'DROP COLUMN `' . $wpdb->escape($delete_column) . '`';
							$query_queue["Delete old column " . $delete_column] = $sql_column;
						}
						foreach($add_columns as $add_column){
							$sql_column = 'ALTER TABLE `' . $this->turl($this->table_config["name"]) . '` ';
							$sql_column .= 'ADD `' . $wpdb->escape($add_column["column_name"]) . '` ';
							$sql_column .= $wpdb->escape($add_column["mysql_type"]);
							$query_queue["Add new column " . $add_column["column_name"]] = $sql_column;
						}
						foreach($alter_columns as $alter_column){
							$sql_column = 'ALTER TABLE `' . $this->turl($this->table_config["name"]) . '` ';
							$sql_column .= 'CHANGE `' . $wpdb->escape($alter_column["previous_name"]) . '` ';
							$sql_column .= '`' . $wpdb->escape($alter_column["column_name"]) . '` ';
							$sql_column .= $wpdb->escape($alter_column["mysql_type"]);
							$query_queue["Add new column " . $alter_column["column_name"]] = $sql_column;
						}
						if($primary_key){
							$current_primary_key = '';
							$sql_column = 'ALTER TABLE `' . $this->turl($this->table_config["name"]) . '` ';
							$current_primary_key = $wpdb->get_results('SHOW INDEX FROM `' . $wpdb->get_blog_prefix() . 'opendata_' . $wpdb->escape($this->table_config["name"]) . "` WHERE `Key_name` = 'PRIMARY'", ARRAY_A);
							if($wpdb->num_rows>0){
								$current_primary_key = $current_primary_key[0]["Column_name"];
								$sql_column .= 'DROP PRIMARY KEY, ';
							}
							$sql_column .= 'ADD PRIMARY KEY(`' . $wpdb->escape($primary_key) . '`)';
							if($primary_key!=$current_primary_key){
								$query_queue["Add primary key " . $primary_key] = $sql_column;
							}
						}
					}
					
					$success = true;
					
					foreach($query_queue as $key=>$sql){
						if($wpdb->query($sql)){
							$this->action[] = 'Query "' . $key . '" succeeded: ' . $sql;
						} else {
							$success = false;
							$this->error[] = 'Query "' . $key . '" failed: ' . $sql;
						}
					}
					
					update_option("od_table_" . $this->table_config["name"], $this->table_config);
					
					if($this->tables){
						$this->tables[$this->table_config["name"]] = array("name"=>$this->table_config["nice_name"]);
					} else {
						$this->tables = array( $this->table_config["name"] => array("name"=>$this->table_config["nice_name"]) );
					}					
					
					update_option("od_tables", $this->tables);
					
					$this->select_table($this->table_config["name"]);
					
					return $success;
				} else {
					$this->error[] = 'No table name set';
				}
			} else {
				$this->error[] = 'No ID column set';
			}
		} else {
			$this->error[] = 'No table data provided';
		}
		return $success;
	}
	
}

// Refresh multiple filter columns to make sure that they end with a ;
function check_filter_multiple() {
	global $wpdb;
	foreach($this->table_config["filter_columns"] as $col){
		if($col["filter_type"]=="multiple"){
			$wpdb->query($wpdb->prepare("UPDATE  `%s` SET  `%s` = CASE WHEN SUBSTRING(  `%s` , -1 ) !=  ';' THEN CONCAT(  `%s` ,  ';' ) ELSE  `%s` END", $this->turl(), $col, $col, $col, $col));
		}
	}
}

// Refresh single filter columns to make sure that they end with a ;
function check_filter_single() {
	global $wpdb;
	foreach($this->table_config["filter_columns"] as $col){
		if($col["filter_type"]=="single"){
			$wpdb->query($wpdb->prepare("UPDATE  `%s` SET  `%s` = TRIM(TRAILING ';' FROM `%s`)", $this->turl(), $col, $col ));
		}
	}
}



