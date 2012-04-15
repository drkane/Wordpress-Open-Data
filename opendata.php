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

/*  Copyright 2012 David Kane  ( email : d.r.kane@gmail.com ) 

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// include needed files
include_once ( "classes/class-od-object.php" );
include_once ( "classes/class-od-object-maintenance.php" );
include_once ( "lib/drk_utils.php" );
include_once ( "od_object_loop.php" );

//declare global variable that will be used by data loop functions
global $od_global;
$od_global = false;

//declare global variable that contains the plugin base directory
global $od_plugin_dir;
$od_plugin_dir = dirname(__FILE__);

// declare query variables that will be used in the URL
add_action ( 'query_vars', 'od_queryvars' );
function od_queryvars ( $qvars ) {
	$qvars[] = "od_data";
	$qvars[] = "od_table";
	$qvars[] = "od_filter";
	$qvars[] = "od_id";
	$qvars[] = "od_filetype";
	$qvars[] = "od_search";
	$qvars[] = "od_options";
	return $qvars;
}

/**
 * Main template redirection function
 *
 * If the URL contains any of the 'od_*' variables, this function parses them and returns the correct data in the right format
 * Add action to the template_redirect hook in WordPress
 *
 */
add_action ( 'template_redirect', 'od_template_redirect' );
if ( ! function_exists ( 'od_template_redirect' ) ) {
function od_template_redirect  ()  {
	global $wp_query;

	// if the 'od_data' query variable is set, then activate the function
	if ( isset ( $wp_query->query_vars["od_data"] )  ) {
	
		// if a particular table has been selected then select it, otherwise start with the default
		if ( isset ( $wp_query->query_vars["od_table"] )  ) {
			$od_data = new Open_Data_Object( $wp_query->query_vars["od_table"] );
		} else {
			$od_data = new Open_Data_Object();
		}
		
		// load possible filtypes
		$od_filetypes = $od_data->filetypes;
		$od_file_type = false;
		
		// load data type from URL parameters (this is "data", "item" or "map")
		$od_data_type = $wp_query->query_vars["od_data"];
		
		// if an 'od_id' variable is set, the use is probably trying to find a single item
		if ( isset ( $wp_query->query_vars["od_id"] )  ) {
			$od_data_type = "item";
			$od_data->set_item_id ( $wp_query->query_vars["od_id"] );
		}
		
		// if a filetypes has been set, then use it
		if ( isset ( $wp_query->query_vars["od_filetype"] )  ) {
			$od_file_type = $wp_query->query_vars["od_filetype"];
		}
		
		// there are two ways of setting up the query:
		//  - either with a single 'od_options' variable (eg: '?od_options=filter/foo/bar')
		if ( isset ( $wp_query->query_vars["od_options"] )  ) {
			$od_data->parse_url ( $wp_query->query_vars["od_options"], $od_data_type );
		// or with individual parameters '?od_filter=foo[]=bar?od_seach=bar'
		} else {
			$od_data->set_data_type ( $od_data_type, $od_file_type );
			if ( isset ( $wp_query->query_vars["od_filter"] )  ) {
				$od_data->set_filters ( $wp_query->query_vars["od_filter"] );
			}
			if ( isset ( $wp_query->query_vars["od_search"] )  ) {
				$od_data->set_search ( $wp_query->query_vars["od_search"] );
			}
		}
		
		// if an include variable has been set
		if ( $od_data->od_include ) {
		
			// set the header as a 
			header ( "HTTP/1.1 200 OK" );
			
			// include the header for the file type
			header ( $od_data->header );
			
			// od_include can either contain a file name, or a filetype
			// first check if the filetype is set, if it is we should use the default way of displaying data
			if ( isset ( $od_data->filetypes[$od_data->od_include] )  ) {
				echo $od_data->display_data ();
				exit;
			// if not, it's probably a file that we need to include
			} else {
				global $od_global;
				$od_global = $od_data; // copy the od_data so it can be used in the loop functions
				include ( $od_data->od_include ); // include the relevant 
				exit;
			}
		} else {
			header ( "HTTP/1.1 404 Not Found" ); // throw an error
		}
	}
}
}

function get_table_menu ()  {
	$od_data = new Open_Data_Object ( $wp_query->query_vars["od_table"] );
	return $od_data->get_menu_categories ();
}

/**
 * Add rewrite rules to include URL redirection. Synonyms can be set for other languages/particular terms for tables
 */	
add_filter ( 'generate_rewrite_rules', 'opendata_dir_rewrite' );
if ( ! function_exists ( 'opendata_dir_rewrite' ) ) {
function opendata_dir_rewrite ( $wp_rewrite )  {
	
	// find synonyms that have been set
	$od_data = new Open_Data_Object ();
	$synonym["data"] = $od_data->synonym["data"];
	$synonym["map"] = $od_data->synonym["map"];
	$synonym["item"] = $od_data->synonym["item"];
	
	// set the feed rules
    $feed_rules = array ( 
		'(' . implode ( "|",$synonym )  . ')/(.*)'=>'index.php?od_data=$1&od_options=$2',
		'(' . implode ( "|",$synonym )  . ').(.*)'=>'index.php?od_data=$1&od_filetype=$2',
		'(' . implode ( "|",$synonym )  . ')' => 'index.php?od_data=$1&od_filetype=html',
		'([^/]+)/(' . implode ( "|",$synonym )  . ')/(.*)'=>'index.php?od_data=$2&od_table=$1&od_options=$3',
		'([^/]+)/(' . implode ( "|",$synonym )  . ')' => 'index.php?od_data=$2&od_table=$1&od_filetype=html'
	 );
	
	// apply feed rules
	$wp_rewrite->non_wp_rules = $feed_rules;
	
	// Permalinks need to be reset for them to work - usually by saving changes on the pretty permalinks page.
}
}

/**
 * Add functionality which intercepts menu creation and adds a submenu with the most popular categories in it
 */	
add_filter ( 'wp_nav_menu_items', 'od_nav_menu' , 10 , 2  );
if ( ! function_exists ( 'od_nav_menu' ) ) {
function od_nav_menu  (  $items, $args  )  {

	// set a new DOM object
	$DOM = new DOMDocument;
	$DOM->loadHTML ( $items );
	$lists = $DOM->getElementsByTagName ( 'li' ); // find every existing list item
	foreach  ( $lists as $list )  {
		$classes = $list->getAttribute ( "class" );
		$classes = explode ( " ", $classes  );
		if ( array_search ( "menu-item-type-custom", $classes ) !==false ) { // also need to check
			$od_data = new Open_Data_Object ();
			$new_menu_data = $od_data->get_menu_categories ( $list->nodeValue );
			if ( $new_menu_data!="" ) {
				$new_menu = $DOM->createDocumentFragment ();
				$new_menu->appendXML ( $new_menu_data );
				$list->appendChild ( $new_menu );
			}
		}
	}
	$items = $DOM->saveHTML (); // problem at the moment is that this adds <html> tags to the beginning
	return $items;
}
}

/**
 * Queue scripts and styles needed in the admin pages
 */	
add_action ( 'admin_menu', 'od_menu' );
if ( ! function_exists ( 'od_menu' ) ) {
function od_menu ()  {
	$page1 = add_menu_page (  "Data", "Data", "manage_options", "open-data", "od_options_main" , null, 32  );
	$page2 = add_submenu_page (  "open-data", "Add New", "Add New", "manage_options", "open-data-tables", "od_options_table" );
 	add_submenu_page (  "open-data", "Permalinks", "Permalinks", "manage_options", "open-data-permalinks", "od_options_permalinks" );
    add_action ( 'admin_print_scripts-' . $page1, 'od_admin_jquery_script' );
    add_action ( 'admin_print_scripts-' . $page2, 'od_admin_jquery_script' );
 }
 }

if ( ! function_exists ( 'od_admin_jquery_script' ) ) {
function od_admin_jquery_script ()  {
	wp_enqueue_script ( 'jquery' );
	wp_enqueue_script ( 'jquery-ui-core' );
	wp_enqueue_script ( 'jquery-ui-sortable' );
	wp_enqueue_script ( 'jquery-ui-tabs' );
    wp_enqueue_script (  'od-admin-script', plugins_url (  'scripts/admin-script.js' , __FILE__  )  , array ( 'jquery', 'jquery-ui-core', 'jquery-ui-draggable'  )   );
    wp_enqueue_style (  'od-admin-style', plugins_url (  'styles/admin-style.css' , __FILE__  )   );
}
}

/**
 * Main admin page template. Needs to be changed to a better template with an include
 */	
if ( ! function_exists ( 'od_options_main' ) ) {
function od_options_main ()  {
	if  ( !current_user_can ( 'manage_options' )  )   {
		wp_die (  __ ( 'You do not have sufficient permissions to access this page.' )   );
	}
	$action = false;
	$table = "";
	if ( isset ( $_REQUEST["action"] )  ) {
		$action = $_REQUEST["action"];
	}
	if ( isset ( $_REQUEST["od_table"] )  ) {
		$table = $_REQUEST["od_table"];
	}
	$od_maintain = new Open_Data_Object_Maintenance (); ?>
	<div class="wrap">
		<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br /></div>
	<?php
	if ( isset ( $_REQUEST["od_new_table"] )  ) {
		if ( $od_maintain->add_new_table ( $_REQUEST["od_new_table"] )  ) : ?>
			<div class="updated">
			<h3>Table changed</h3>
			<?php echo drk_implode ( '</p><p>', $od_maintain->action, '<p>', '</p>' ); ?>
			<?php echo drk_implode ( '</p><p>', $od_maintain->error, '<p>', '</p>' ); ?>
			</div>
		<?php else: ?>
			<div class="error">
			<h3>Table could not be changed</h3>
			<?php echo drk_implode ( '</p><p>', $od_maintain->action, '<p>', '</p>' ); ?>
			<?php echo drk_implode ( '</p><p>', $od_maintain->error, '<p>', '</p>' ); ?>
			<?php drk_print_r ( $_REQUEST["od_new_table"] ); ?>
			</div>
		<?php endif;
	} else if  ( isset ( $_REQUEST["quickadd"] ) &&isset ( $_FILES["quickaddfile"] )  ) {
		if ( $od_maintain->add_table_data ( $_FILES["quickaddfile"]["tmp_name"], $_REQUEST["quickadd"]["name"],false, true, true )  ) : ?>
			<div class="updated">
			<h3>Table added</h3>
			<?php echo drk_implode ( '</p><p>', $od_maintain->action, '<p>', '</p>' ); ?>
			<?php echo drk_implode ( '</p><p>', $od_maintain->error, '<p>', '</p>' ); ?>
			</div>
		<?php else: ?>
			<div class="error">
			<h3>Table could not be added</h3>
			<?php echo drk_implode ( '</p><p>', $od_maintain->action, '<p>', '</p>' ); ?>
			<?php echo drk_implode ( '</p><p>', $od_maintain->error, '<p>', '</p>' ); ?>
			<?php drk_print_r ( $_REQUEST["quickadd"] ); ?>
			<?php drk_print_r ( $_FILES["quickaddfile"] ); ?>
			</div>
		<?php endif;
	} 
	if ( $action=="edit" ) :
		if ( $od_maintain->table_exists ( $table )  ) : ?>
			<h2>Edit table</h2>
			<div class="metabox-holder">
			<?php $od_maintain->add_new_table_admin ( $table ); ?>
			</div>
		<?php else: ?>
			<h2>Data options <a href="admin.php?page=open-data-tables" class="add-new-h2">Add New Data Table</a></h2>
			<div class="metabox-holder">
			<div class="error"><p>Table "<?php echo $table; ?>" not found</p></div>
			<?php $od_maintain->admin_screen (); ?>
			</div>
		<?php endif; ?>
	<?php elseif ( $action=="data" ) : ?>
			<h2>Edit table</h2>
			<div class="metabox-holder">
			<?php $od_maintain->table_data_admin ( $table ); ?>
			</div>
	<?php elseif ( $action=="trash" ) : ?>
			<h2>Data options <a href="admin.php?page=open-data-tables" class="add-new-h2">Add New Data Table</a></h2>
			<div class="metabox-holder">
			<?php if ( $od_maintain->delete_table ( $table )  ) : ?>
			<div class="updated">
				<p>Table "<?php echo $table; ?>" deleted</p>
			</div>
			<?php else: ?>
			<div class="updated">
				<p>Table "<?php echo $table; ?>" could not be deleted</p>
				<?php echo drk_implode ( '</p><p>', $od_maintain->action, '<p>', '</p>' ); ?>
				<?php echo drk_implode ( '</p><p>', $od_maintain->error, '<p>', '</p>' ); ?>
			</div>
			<?php endif; ?>
			</div>
			<?php $od_maintain->admin_screen (); ?>
			</div>
	<?php else: ?>
		<h2>Data options <a href="admin.php?page=open-data-tables" class="add-new-h2">Add New Data Table</a></h2>
		<?php $od_maintain->admin_screen (); ?>
	<?php endif; ?>
	</div><?php
}
}

/**
 * Add new table template. Needs to be changed to a better template with an include
 */	
if ( ! function_exists ( 'od_options_table' ) ) {
function od_options_table ()  {
	if  ( !current_user_can ( 'manage_options' )  )   {
		wp_die (  __ ( 'You do not have sufficient permissions to access this page.' )   );
	}
	$od_maintain = new Open_Data_Object_Maintenance ();
	echo '<div class="wrap">';
	echo '<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br /></div> ';
	if ( isset ( $_REQUEST["od_new_table"] )  ) {
		if ( $od_maintain->add_new_table ( $_REQUEST["od_new_table"] )  ) : ?>
			<h2>Edit table</h2>
			<div class="metabox-holder">
			<div class="updated">
			<h3>Table added</h3>
			<?php echo drk_implode ( '</p><p>', $od_maintain->action, '<p>', '</p>' ); ?>
			<?php echo drk_implode ( '</p><p>', $od_maintain->error, '<p>', '</p>' ); ?>
			</div>
			<?php $od_maintain->add_new_table_admin ( $od_maintain->selected_table ); ?>
			</div>
		<?php else: ?>
			<h2>Add new table</h2>
			<div class="metabox-holder">
			<div class="error">
			<h3>Table could not be added</h3>
			<?php echo drk_implode ( '</p><p>', $od_maintain->action, '<p>', '</p>' ); ?>
			<?php echo drk_implode ( '</p><p>', $od_maintain->error, '<p>', '</p>' ); ?>
			<?php drk_print_r ( $_REQUEST["od_new_table"] ); ?>
			</div>
			<?php $od_maintain->add_new_table_admin ( $od_maintain->selected_table ); ?>
			</div>
		<?php endif;
	} else {
		echo '<h2>Add new table</h2>';
		echo '<div class="metabox-holder">';
		$od_maintain->add_new_table_admin ();
		echo '</div>';
	}
	echo '</div>';
}
}

/**
 * Permalink admin page template. Needs to be completed.
 */	
if ( ! function_exists ( 'od_options_permalinks' ) ) {
function od_options_permalinks ()  {
	if  ( !current_user_can ( 'manage_options' )  )   {
	wp_die (  __ ( 'You do not have sufficient permissions to access this page.' )   );
	}
	$od_maintain = new Open_Data_Object_Maintenance ();
	echo '<div class="wrap">';
	echo '<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br /></div><h2>Edit data permalinks</h2> ';
	echo '</div>';
}
}

/**
 * Adds a custom meta-box to the menu admin page
 */	
add_action ( 'admin_init', 'od_nav_menu_metabox'  );
if ( ! function_exists ( 'od_nav_menu_metabox' ) ) {
function od_nav_menu_metabox ()  {
	add_meta_box ( 
		'add-data',
        'Data',
        'od_inner_custom_box',
        'nav-menus', 
		'side', 
		'default'
	 );
}
}
if ( ! function_exists ( 'od_inner_custom_box' ) ) {
function od_inner_custom_box ()  {?>
	<div class="query">
			<p>
				<label class="howto" for="post_type_or_taxonomy">
					<span><?php _e ( 'Post Type or Taxonomy' ); ?></span>
				</label>
				<select id="post_type_or_taxonomy" name="post_type_or_taxonomy" style="width: 100%">
					<option value="post_type">Post Type</option>
					<option value="taxonomy">Taxonomy</option>
				</select>
			</p>

			<p>
				<label class="howto" for="post_type_or_taxonomy_id">
					<span><?php _e ( 'ID' ); ?></span>
					<input id="post_type_or_taxonomy_id" name="post_type_or_taxonomy_id" type="text" class="regular-text menu-item-textbox input-with-default-title" title="<?php esc_attr_e ( 'ID' ); ?>" />
				</label>
			</p>

			<p style="display: block; margin: 1em 0; clear: both;">
				<label class="howto" for="post_type_or_taxonomy_title">
					<span><?php _e ( 'Title' ); ?></span>
					<input id="post_type_or_taxonomy_title" name="post_type_or_taxonomy_title" type="text" class="regular-text menu-item-textbox input-with-default-title" title="<?php esc_attr_e ( 'Optional' ); ?>" />
				</label>
			</p>

		<p class="button-controls">
			<span class="list-controls">
			</span>
			<span class="add-to-menu">
				<img class="waiting" src="<?php echo esc_url (  admin_url (  'images/wpspin_light.gif'  )   ); ?>" alt="" />
				<input type="submit" class="button-secondary" value="<?php esc_attr_e ( 'Add to Menu' ); ?>" name="add-custom-menu-item" />
			</span>
		</p>

	</div><?php
}
}