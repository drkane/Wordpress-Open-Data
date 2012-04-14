<?php
/*
Plugin Name: Wordpress Open Data
Description: Adds open data functionality to a Wordpress-based site.
Plugin URI: http://drkane.co.uk/projects/wordpress-open-data
Version: 0.2
Author: David Kane
Author URI: http://drkane.co.uk/
License: GPLv2
*/
include_once("class.od_object.php");
include_once("class.od_object_maintenance.php");
include_once("od_object_loop.php");

$od_global = false;

function od_queryvars($qvars){
	$qvars[] = "od_data";
	$qvars[] = "od_table";
	$qvars[] = "od_filter";
	$qvars[] = "od_id";
	$qvars[] = "od_filetype";
	$qvars[] = "od_search";
	$qvars[] = "od_options";
	return $qvars;
}

$drk_table_types = array("thead", "tfoot");

function od_template_redirect(){
	global $wp_query, $wpdb;

	if(isset($wp_query->query_vars["od_data"])){
	
		
		if(isset($wp_query->query_vars["od_table"])){
			$od_data = new od_object($wp_query->query_vars["od_table"]);
		} else {
			$od_data = new od_object();
		}
		$od_filetypes = $od_data->filetypes;
		$od_data_type = $wp_query->query_vars["od_data"];
		$od_file_type = false;
		if(isset($wp_query->query_vars["od_id"])){
			$od_data_type = "item";
			$od_data->set_item_id($wp_query->query_vars["od_id"]);
		}
		if(isset($wp_query->query_vars["od_filetype"])){
			$od_file_type = $wp_query->query_vars["od_filetype"];
		}
		if(isset($wp_query->query_vars["od_options"])){
			$od_data->parse_url($wp_query->query_vars["od_options"], $od_data_type);
		} else {
			$od_data->set_data_type($od_data_type, $od_file_type);
			if(isset($_REQUEST["od_filter"])){
				$od_data->set_filters($_REQUEST["od_filter"]);
			}
			if(isset($_REQUEST["od_search"])){
				$od_data->set_search($_REQUEST["od_search"]);
			}
		}
	
		if($od_data->od_include){
			header("HTTP/1.1 200 OK");
			header($od_data->header);
			if(isset($od_data->filetypes[$od_data->od_include])){
				echo $od_data->display_data();
				exit;
			} else {
				global $od_global;
				$od_global = $od_data;
				include($od_data->od_include);
				exit;
			}
		} else {
				drk_print_r($od_data->od_include);
			header("HTTP/1.1 404 Not Found");
		}
	}
}

function get_table_menu() {
	$od_data = new od_object($wp_query->query_vars["od_table"]);
	return $od_data->get_menu_categories();
}
	
function opendata_dir_rewrite($wp_rewrite) {
	
	$od_data = new od_object();
	$synonym["data"] = $od_data->synonym["data"];
	$synonym["map"] = $od_data->synonym["map"];
	$synonym["item"] = $od_data->synonym["item"];
	
    $feed_rules = array(
		'(' . implode("|",$synonym) . ')/(.*)'=>'index.php?od_data=$1&od_options=$2',
		'(' . implode("|",$synonym) . ').(.*)'=>'index.php?od_data=$1&od_filetype=$2',
		'(' . implode("|",$synonym) . ')' => 'index.php?od_data=$1&od_filetype=html',
		'([^/]+)/(' . implode("|",$synonym) . ')/(.*)'=>'index.php?od_data=$2&od_table=$1&od_options=$3',
		'([^/]+)/(' . implode("|",$synonym) . ')' => 'index.php?od_data=$2&od_table=$1&od_filetype=html'
	);

	$wp_rewrite->non_wp_rules = $feed_rules;
}

// Hook in.
add_filter( 'generate_rewrite_rules', 'opendata_dir_rewrite' );
add_action('query_vars', 'od_queryvars');
add_action('template_redirect', 'od_template_redirect');
add_action('admin_menu', 'od_menu');
add_action('admin_init', 'od_nav_menu_metabox' );

add_filter('wp_nav_menu_items', 'hijack_nav_menu' , 10 , 2 );

function hijack_nav_menu ( $items, $args ) {
	$text = '<li id="menu-item-112" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-112"><a href="http://dave.com/">dave</a></li>';
	$DOM = new DOMDocument;
	$DOM->loadHTML($items);
	$lists = $DOM->getElementsByTagName('li');
	foreach ($lists as $list) {
		$classes = $list->getAttribute("class");
		$classes = explode(" ", $classes );
		if(array_search("menu-item-type-custom", $classes)!==false){
			$od_data = new od_object();
			$new_menu_data = $od_data->get_menu_categories($list->nodeValue);
			if($new_menu_data!=""){
				$new_menu = $DOM->createDocumentFragment();
				$new_menu->appendXML($new_menu_data);
				$list->appendChild($new_menu);
			}
		}
	}
	$items = $DOM->saveHTML();
	return $items;
}

function admin_jquery_script() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_script( 'od-admin-script', plugins_url( 'scripts/admin-script.js' , __FILE__ ) , array('jquery', 'jquery-ui-core', 'jquery-ui-draggable' ) );
    wp_enqueue_style( 'od-admin-style', plugins_url( 'styles/admin-style.css' , __FILE__ ) );

}

function od_menu() {
	$page1 = add_menu_page( "Data", "Data", "manage_options", "open-data", "od_options_main" , null, 32 );
	$page2 = add_submenu_page( "open-data", "Add New", "Add New", "manage_options", "open-data-tables", "od_options_table");
 	add_submenu_page( "open-data", "Permalinks", "Permalinks", "manage_options", "open-data-permalinks", "od_options_permalinks");
    add_action('admin_print_scripts-' . $page1, 'admin_jquery_script');
    add_action('admin_print_scripts-' . $page2, 'admin_jquery_script');
 }
 
function od_options_main() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	$action = false;
	$table = "";
	if(isset($_REQUEST["action"])){
		$action = $_REQUEST["action"];
	}
	if(isset($_REQUEST["od_table"])){
		$table = $_REQUEST["od_table"];
	}
	$od_maintain = new od_object_maintenance(); ?>
	<div class="wrap">
		<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br /></div>
	<?php
	if(isset($_REQUEST["od_new_table"])){
		if($od_maintain->add_new_table($_REQUEST["od_new_table"])): ?>
			<div class="updated">
			<h3>Table changed</h3>
			<?php echo drk_implode('</p><p>', $od_maintain->action, '<p>', '</p>'); ?>
			<?php echo drk_implode('</p><p>', $od_maintain->error, '<p>', '</p>'); ?>
			</div>
		<?php else: ?>
			<div class="error">
			<h3>Table could not be changed</h3>
			<?php echo drk_implode('</p><p>', $od_maintain->action, '<p>', '</p>'); ?>
			<?php echo drk_implode('</p><p>', $od_maintain->error, '<p>', '</p>'); ?>
			<?php drk_print_r($_REQUEST["od_new_table"]); ?>
			</div>
		<?php endif;
	} else if (isset($_REQUEST["quickadd"])&&isset($_FILES["quickaddfile"])){
		if($od_maintain->add_table_data($_FILES["quickaddfile"]["tmp_name"], $_REQUEST["quickadd"]["name"],false, true, true)): ?>
			<div class="updated">
			<h3>Table added</h3>
			<?php echo drk_implode('</p><p>', $od_maintain->action, '<p>', '</p>'); ?>
			<?php echo drk_implode('</p><p>', $od_maintain->error, '<p>', '</p>'); ?>
			</div>
		<?php else: ?>
			<div class="error">
			<h3>Table could not be added</h3>
			<?php echo drk_implode('</p><p>', $od_maintain->action, '<p>', '</p>'); ?>
			<?php echo drk_implode('</p><p>', $od_maintain->error, '<p>', '</p>'); ?>
			<?php drk_print_r($_REQUEST["quickadd"]); ?>
			<?php drk_print_r($_FILES["quickaddfile"]); ?>
			</div>
		<?php endif;
	} 
	if($action=="edit"):
		if($od_maintain->table_exists($table)): ?>
			<h2>Edit table</h2>
			<div class="metabox-holder">
			<?php $od_maintain->add_new_table_admin($table); ?>
			</div>
		<?php else: ?>
			<h2>Data options <a href="admin.php?page=open-data-tables" class="add-new-h2">Add New Data Table</a></h2>
			<div class="metabox-holder">
			<div class="error"><p>Table "<?php echo $table; ?>" not found</p></div>
			<?php $od_maintain->admin_screen(); ?>
			</div>
		<?php endif; ?>
	<?php elseif($action=="data"): ?>
			<h2>Edit table</h2>
			<div class="metabox-holder">
			<?php $od_maintain->table_data_admin($table); ?>
			</div>
	<?php elseif($action=="trash"): ?>
			<h2>Data options <a href="admin.php?page=open-data-tables" class="add-new-h2">Add New Data Table</a></h2>
			<div class="metabox-holder">
			<?php if($od_maintain->delete_table($table)): ?>
			<div class="updated">
				<p>Table "<?php echo $table; ?>" deleted</p>
			</div>
			<?php else: ?>
			<div class="updated">
				<p>Table "<?php echo $table; ?>" could not be deleted</p>
				<?php echo drk_implode('</p><p>', $od_maintain->action, '<p>', '</p>'); ?>
				<?php echo drk_implode('</p><p>', $od_maintain->error, '<p>', '</p>'); ?>
			</div>
			<?php endif; ?>
			</div>
			<?php $od_maintain->admin_screen(); ?>
			</div>
	<?php else: ?>
		<h2>Data options <a href="admin.php?page=open-data-tables" class="add-new-h2">Add New Data Table</a></h2>
		<?php $od_maintain->admin_screen(); ?>
	<?php endif; ?>
	</div><?php
}

function od_options_table() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	$od_maintain = new od_object_maintenance();
	echo '<div class="wrap">';
	echo '<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br /></div> ';
	if(isset($_REQUEST["od_new_table"])){
		if($od_maintain->add_new_table($_REQUEST["od_new_table"])): ?>
			<h2>Edit table</h2>
			<div class="metabox-holder">
			<div class="updated">
			<h3>Table added</h3>
			<?php echo drk_implode('</p><p>', $od_maintain->action, '<p>', '</p>'); ?>
			<?php echo drk_implode('</p><p>', $od_maintain->error, '<p>', '</p>'); ?>
			</div>
			<?php $od_maintain->add_new_table_admin($od_maintain->selected_table); ?>
			</div>
		<?php else: ?>
			<h2>Add new table</h2>
			<div class="metabox-holder">
			<div class="error">
			<h3>Table could not be added</h3>
			<?php echo drk_implode('</p><p>', $od_maintain->action, '<p>', '</p>'); ?>
			<?php echo drk_implode('</p><p>', $od_maintain->error, '<p>', '</p>'); ?>
			<?php drk_print_r($_REQUEST["od_new_table"]); ?>
			</div>
			<?php $od_maintain->add_new_table_admin($od_maintain->selected_table); ?>
			</div>
		<?php endif;
	} else {
		echo '<h2>Add new table</h2>';
		echo '<div class="metabox-holder">';
		$od_maintain->add_new_table_admin();
		echo '</div>';
	}
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
        'Data',
        'od_inner_custom_box',
        'nav-menus', 
		'side', 
		'default'
	);
}
function od_inner_custom_box() {?>
	<div class="query">
			<p>
				<label class="howto" for="post_type_or_taxonomy">
					<span><?php _e('Post Type or Taxonomy'); ?></span>
				</label>
				<select id="post_type_or_taxonomy" name="post_type_or_taxonomy" style="width: 100%">
					<option value="post_type">Post Type</option>
					<option value="taxonomy">Taxonomy</option>
				</select>
			</p>

			<p>
				<label class="howto" for="post_type_or_taxonomy_id">
					<span><?php _e('ID'); ?></span>
					<input id="post_type_or_taxonomy_id" name="post_type_or_taxonomy_id" type="text" class="regular-text menu-item-textbox input-with-default-title" title="<?php esc_attr_e('ID'); ?>" />
				</label>
			</p>

			<p style="display: block; margin: 1em 0; clear: both;">
				<label class="howto" for="post_type_or_taxonomy_title">
					<span><?php _e('Title'); ?></span>
					<input id="post_type_or_taxonomy_title" name="post_type_or_taxonomy_title" type="text" class="regular-text menu-item-textbox input-with-default-title" title="<?php esc_attr_e('Optional'); ?>" />
				</label>
			</p>

		<p class="button-controls">
			<span class="list-controls">
			</span>
			<span class="add-to-menu">
				<img class="waiting" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" />
				<input type="submit" class="button-secondary" value="<?php esc_attr_e('Add to Menu'); ?>" name="add-custom-menu-item" />
			</span>
		</p>

	</div><?php
}
function od_change_datatype($od_type="csv",$od_view="data") {
	return "http://www.bbc.co.uk/news/";
	/*
	
	NEEDS TO BE REDONE
	
	
	global $od_data;
	$od_filetypes = $od_data->filetypes;
	$od_pageURL = 'http';
	if(isset($_SERVER["HTTPS"])){
		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";
}	}	$od_pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
	$od_pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$od_pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}	if(strpos($od_pageURL,"od_data=")>0){
		if(strpos($od_pageURL,"od_filetype=")>0){
			$od_new_url = $od_pageURL . "&od_filetype=$od_type";
		} else {
			$od_new_url = $od_pageURL . "&od_filetype=$od_type";
		}
		if($od_view=="map"){
			$od_new_url = str_replace("od_data=data","od_data=map",$od_new_url);
		} else {
			$od_new_url = str_replace("od_data=map","od_data=data",$od_new_url);
		}
	} else {
		if($od_view=="map"){
			$od_new_url = str_replace("/data","/map",$od_pageURL);
		} else {
			$od_new_url = str_replace("/map","/data",$od_pageURL);
		}
		if(substr($od_new_url,-1)=="/"){
			$od_new_url = substr($od_new_url,0,-1);
		}
		drk_print_r($od_filetypes);
		foreach($od_filetypes as $od_key=>$od_value){
			$od_new_url = str_replace(".$od_key","",$od_new_url);
		}
		if($od_type!="html"){
			$od_new_url = $od_new_url . ".$od_type";
		}
	}
	return $od_new_url; */
}

function drk_merge_arrays($defaults, $options = array(), $recursive=true){
	if(is_array($defaults)&&is_array($options)){
		foreach($defaults as $key=>&$value){
			if(isset($options[$key])){
				if(is_array($value)&&is_array($options[$key])&&$recursive){
					$value = drk_merge_arrays($value, $options[$key]);
				} else {
					$value = $options[$key];
				}
			}
		}
		return $defaults;
	} else {
		return false;
	}
}

function drk_implode($glue, $pieces, $before="", $after="", $default=""){
	$return = '';
	$return .= implode($glue, $pieces);
	if($return!=''){
		$return = $before . $return . $after;
	} else {
		$return = $default;
	}
	return $return;
}

function drk_print_r($var, $return=false, $before='<pre>', $after='</pre>'){
	$pretty = $before . print_r($var, true) . $after;
	if($return){
		return $pretty;
	} else {
		echo $pretty;
		return true;
	}
}

function drk_implode_recursive($glue, $pieces) {
	$return = "";
	if ( is_array ( $pieces ) ) {
		foreach ($pieces as &$piece){
			if ( is_array ( $piece ) ) {
				$piece = drk_implode_recursive( $glue, $piece ); 
			}
		}
		$return = implode( $glue, $pieces );
	} else if ( is_string ( $pieces ) ) {
		$return = $pieces;
	}
	return $return;
}