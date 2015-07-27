<?php
/*
Plugin Name: WordPress Domain Sync cPanel
Plugin URI: -
Description: Sync all domains in a WordPress multi websites with the cPanel domains. This plugin require WordPress MU Domain Mapping
Version: 0.0.2
Author: helmi@bee38.net
Author URI: -
*/

/* start test */
/*
define('CPANEL_HOST', 'web01.bee38.net');
define('CPANEL_USER', 'helmidevbtx');
define('CPANEL_PASS', 'joebtx...38');
define('CPANEL_PORT', '2083');
define('CPANEL_SSL', true);
define('CPANEL_THEME', 'paper_lantern');
define( 'ABSPATH', '1' );
*/
/* end test */

defined( 'ABSPATH' ) or die( 'No weird entry.' );

require_once('cPanel.php');

//$cp = new domain(CPANEL_HOST, CPANEL_USER, CPANEL_PASS, CPANEL_PORT, CPANEL_SSL, CPANEL_THEME, '');
$cp_all_arr = array();


/* test area */

//$cp->listParkDomain();exit;
//echo $wpdb->dmtable;exit;
//ds_domain_sync_wp_cpanel();exit;


/*

function ds_check() {
	ds_config_check();
}

function ds_config_check() {
	$msg = '';
	defined('CPANEL_HOST') or $msg = 'Please define CPANEL_HOST inside your wp-config.php';
	defined('CPANEL_USER') or $msg = 'Please define CPANEL_USER inside your wp-config.php';
	defined('CPANEL_PASS') or $msg = 'Please define CPANEL_PASS inside your wp-config.php';
	defined('CPANEL_PORT') or $msg = 'Please define CPANEL_PORT inside your wp-config.php';
	defined('CPANEL_SSL') or $msg = 'Please define CPANEL_SSL inside your wp-config.php';
	defined('CPANEL_THEME') or $msg = 'Please define CPANEL_THEME inside your wp-config.php';
	if($msg != '') wp_die( __('wp-domain-sync-cpanel : Config Error! '.$msg, 'wp-domain-sync-cpanel' ));
}
*/

function ds_alias_add($domain, $cp) {
	//global $cp;
	$cp->domain = $domain;
	$r = $cp->parkDomain();
	return $r;
}

function ds_alias_remove($domain, $cp) {
	//global $cp;
	$cp->domain = $domain;
	$r = $cp->unparkDomain();
	return $r;
}

function ds_alias_sync_admin($cp) {
	$domain = $_POST['domain'];
	switch($_POST['action']) {
		case 'save' : 
			if(!empty($_POST['orig_domain'])) {
				$r = ds_alias_remove($_POST['orig_domain'], $cp);
				if($r) ds_alias_add($domain, $cp);
			} else ds_alias_add($domain, $cp);
			break;
		case 'del' : ds_alias_remove($domain, $cp); break;
	}
}

function ds_domain_add($domain) {
	global $cp_all_arr;
	ds_create_all_obj();
	if(!empty($cp_all_arr)) foreach($cp_all_arr as $cp) ds_alias_add($domain, $cp);
}

function ds_domain_del($domain) {
	global $cp_all_arr;
	ds_create_all_obj();
	if(!empty($cp_all_arr)) foreach($cp_all_arr as $cp) ds_alias_remove($domain, $cp);
}

function ds_domain_sync_admin() {
	global $cp_all_arr;
	ds_create_all_obj();
	if(!empty($cp_all_arr)) foreach($cp_all_arr as $cp) ds_alias_sync_admin($cp);
}

/*
add_action('admin_init', 'ds_check');
add_action('dm_handle_actions_add', 'ds_domain_add', 10, 1); //hooks to action dm_handle_actions_add from MU Multidomain
add_action('dm_handle_actions_del', 'ds_domain_del', 10, 1); //hooks to action dm_handle_actions_del from MU Multidomain

if(($_POST['_wp_http_referer'] == '/wp-admin/network/settings.php?page=dm_domains_admin') and (!empty($_POST['action']))) {
	add_action('dm_domains_admin', 'ds_domain_sync_admin'); //hooks to action dm_domains_admin from MU Multidomain
	do_action('dm_domains_admin'); //this one must be executed here since this is not yet executed in MU Multidomain
}
*/


/* Version 0.0.2 */

function get_domain_mapping_table() {
	global $wpdb;

	//$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
	return $wpdb->dmtable;
}

function ds_site_admin() {
	if ( function_exists( 'is_super_admin' ) ) {
		return is_super_admin();
	} elseif ( function_exists( 'is_site_admin' ) ) {
		return is_site_admin();
	} else {
		return true;
	}
}

function ds_maybe_create_db() {
	global $wpdb;

	$wpdb->dstable = $wpdb->base_prefix . 'domain_sync_cpanel';
	if ( ds_site_admin() ) {
		$created = 0;
		if ( $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->dstable}'") != $wpdb->dstable ) {
			$r = $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->dstable}` (
				`cpanel_id` int unsigned NOT NULL auto_increment,
				`cpanel_host` varchar(200) unique NOT NULL,
				`cpanel_user` varchar(100) NOT NULL,
				`cpanel_pass` varchar(100) NOT NULL,
				`cpanel_port` varchar(10) NOT NULL,
				`cpanel_ssl` varchar(1) NOT NULL default '1',
				`cpanel_theme` varchar(100) NOT NULL,
				`is_del` varchar(1) default '0',
				PRIMARY KEY (`cpanel_id`)
			)engine=MyISAM;");
			if($r) $created = 1;
		}
		
		if ( $created ) {
			?> <div id="message" class="updated fade"><p><strong><?php _e( 'Domain Sync cPanel database table created.', 'wp-domain-sync-cpanel' ) ?></strong></p></div> <?php
		}
	}

}

function ds_alias_get_cpanel($cp) {
	
	return $cp->listParkDomain();
}

function ds_alias_get_wp() {
	global $wpdb;
	
	$domain_arr = array();
	$tbl = get_domain_mapping_table();
	$sql = "SELECT * FROM {$tbl}";
	$rows = $wpdb->get_results($sql);
	if(!empty($rows)) foreach($rows as $v) $domain_arr[] = $v->domain;
	return $domain_arr;
}

function ds_get_all_cpanel() {
	global $wpdb;
	
	$wpdb->dstable = $wpdb->base_prefix . 'domain_sync_cpanel';
	$sql = "SELECT * FROM {$wpdb->dstable} where is_del='0'";
	$rows = $wpdb->get_results($sql);
	return $rows;
}

function ds_create_all_obj() {
	global $cp_all_arr;
	
	$cp_rows = ds_get_all_cpanel();
	if(!empty($cp_rows)) foreach($cp_rows as $v) {
		$cp_all_arr[$v->cpanel_host] = new domain($v->cpanel_host, $v->cpanel_user, $v->cpanel_pass, $v->cpanel_port, $v->cpanel_ssl, $v->cpanel_theme, '');
	}
}

function ds_alias_sync_wp_cpanel($cp) {
	$cpanel_alias_arr = ds_alias_get_cpanel($cp);
	$wp_alias_arr = ds_alias_get_wp();
	if($cpanel_alias_arr == $wp_alias_arr) return false; //nothing to do
	$cpanel_alias_del_arr = array_diff($cpanel_alias_arr, $wp_alias_arr);
	$cpanel_alias_add_arr = array_diff($wp_alias_arr, $cpanel_alias_arr);
	if(!empty($cpanel_alias_del_arr)) {
		foreach($cpanel_alias_del_arr as $v) ds_alias_remove($v, $cp);
	}
	if(!empty($cpanel_alias_add_arr)){
		foreach($cpanel_alias_add_arr as $v) ds_alias_add($v, $cp);
	}
	//if(!empty($cpanel_alias_del_arr) or !empty($cpanel_alias_add_arr)) return true;
	return true;
}

function ds_domain_sync_wp_cpanel() {
	global $cp_all_arr;
	ds_create_all_obj();
	$r = array();
	if(!empty($cp_all_arr)) {
		foreach($cp_all_arr as $cp) {
			$tmp = ds_alias_sync_wp_cpanel($cp);
			if($tmp) array_push($r, true);
		}
	}
	return $r;
}

function ds_edit_cpanel( $row = false ) {
	if ( is_object( $row ) ) {
		echo "<h3>" . __( 'Edit cPanel', 'wp-domain-sync-cpanel' ) . "</h3>";
	}  else {
		echo "<h3>" . __( 'New cPanel', 'wp-domain-sync-cpanel' ) . "</h3>";
		$row = new stdClass();
		$row->cpanel_host = '';
		$_POST[ 'cpanel_host' ] = '';
		$row->cpanel_user = '';
		$row->cpanel_pass = '';
		$row->cpanel_port = '';
		$row->cpanel_theme = '';
	}

	echo "<form method='POST'><input type='hidden' name='action' value='save' /><input type='hidden' name='orig_cpanel_host' value='" . esc_attr( $_POST[ 'cpanel_host' ] ) . "' />";
	wp_nonce_field( 'domain_sync' );
	echo "<table class='form-table'>\n";
	echo "<tr><th>" . __( 'cPanel Host', 'wp-domain-sync-cpanel' ) . "</th><td><input type='text' name='cpanel_host' value='{$row->cpanel_host}' /></td></tr>\n";
	echo "<tr><th>" . __( 'cPanel User', 'wp-domain-sync-cpanel' ) . "</th><td><input type='text' name='cpanel_user' value='{$row->cpanel_user}' /></td></tr>\n";
	echo "<tr><th>" . __( 'cPanel Password', 'wp-domain-sync-cpanel' ) . "</th><td><input type='text' name='cpanel_pass' value='{$row->cpanel_pass}' /></td></tr>\n";
	echo "<tr><th>" . __( 'cPanel Port', 'wp-domain-sync-cpanel' ) . "</th><td><input type='text' name='cpanel_port' value='{$row->cpanel_port}' /></td></tr>\n";
	echo "<tr><th>" . __( 'cPanel Theme', 'wp-domain-sync-cpanel' ) . "</th><td><input type='text' name='cpanel_theme' value='{$row->cpanel_theme}' /></td></tr>\n";
	
	echo "</table>";
	echo "<p><input type='submit' class='button-primary' value='" .__( 'Save', 'wp-domain-sync-cpanel' ). "' /></p></form><br /><br />";
}

function ds_cpanel_listing( $rows, $heading = '' ) {
	if ( $rows ) {
		if ( $heading != '' )
			echo "<h3>$heading</h3>";
		echo '<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th>'.__( 'cPanel Host', 'wp-domain-sync-cpanel' ).'</th>
						<th>'.__( 'cPanel User', 'wp-domain-sync-cpanel' ).'</th>
						<th>'.__( 'cPanel Password', 'wp-domain-sync-cpanel' ).'</th>
						<th>'.__( 'cPanel Port', 'wp-domain-sync-cpanel' ).'</th>
						<th>'.__( 'cPanel Theme', 'wp-domain-sync-cpanel' ).'</th>
						<th>'.__( 'Edit', 'wp-domain-sync-cpanel' ).'</th>
						<th>'.__( 'Delete', 'wp-domain-sync-cpanel' ).'</th>
					</tr>
				</thead>
				<tbody>';
		foreach( $rows as $row ) {
			echo "<tr>
				<td>{$row->cpanel_host}</td>
				<td>{$row->cpanel_user}</td>
				<td>{$row->cpanel_pass}</td>
				<td>{$row->cpanel_port}</td>
				<td>{$row->cpanel_theme}</td>
			";
			echo "<td><form method='POST'><input type='hidden' name='action' value='edit' /><input type='hidden' name='cpanel_host' value='{$row->cpanel_host}' />";
			wp_nonce_field( 'domain_sync' );
			echo "<input type='submit' class='button-secondary' value='" .__( 'Edit', 'wp-domain-sync-cpanel' ). "' /></form></td><td><form method='POST'><input type='hidden' name='action' value='del' /><input type='hidden' name='cpanel_host' value='{$row->cpanel_host}' />";
			wp_nonce_field( 'domain_sync' );
			echo "<input type='submit' class='button-secondary' value='" .__( 'Del', 'wp-domain-sync-cpanel' ). "' /></form>";
			echo "</td></tr>";
		}
		
		/*
		echo "
			<tfoot><tr><td align=\"center\" colspan=\"7\"><form method='POST'>";
		wp_nonce_field( 'domain_sync' );
		echo "<input type='hidden' name='action' value='sync' /><input type='submit' class='button-secondary' value='" .__( 'Sync All Now', 'wp-domain-sync-cpanel' ). "' /></form></td></tr>
		";
		*/
		
		echo '</table>';
	}
}



function ds_domains_admin() {
	global $wpdb;
	if ( false == ds_site_admin() ) { // paranoid? moi?
		return false;
	}

	ds_maybe_create_db();


	echo '<h2>' . __( 'Domain Sync cPanel: cPanel Configuration', 'wp-domain-sync-cpanel' ) . '</h2>';
	
	if(empty($_POST[ 'action' ])) {
		//initial sync whenever user entering this page
		$sync_status = ds_domain_sync_wp_cpanel();
		if ( !empty($sync_status) ) {
			?> <div id="message" class="updated fade"><p><strong><?php _e( 'Sync finished. Everything is now synced.', 'wp-domain-sync-cpanel' ) ?></strong></p></div> <?php
		}
		if ( empty($sync_status) ) {
			?> <div id="message" class="updated fade"><p><strong><?php _e( 'Sync finished. But nothing need to be synced.', 'wp-domain-sync-cpanel' ) ?></strong></p></div> <?php
		}
	}
	
	if ( !empty( $_POST[ 'action' ] ) ) {
		check_admin_referer( 'domain_sync' );
		$cpanel_host = strtolower( $_POST[ 'cpanel_host' ] );
		switch( $_POST[ 'action' ] ) {
			case "sync":
				//ds_create_all_obj(); //moved to every looping action
				$sync_status = ds_domain_sync_wp_cpanel();
				//print_r($sync_status); exit;
				
				if ( !empty($sync_status) ) {
					?> <div id="message" class="updated fade"><p><strong><?php _e( 'Sync finished. Everything is now synced.', 'wp-domain-sync-cpanel' ) ?></strong></p></div> <?php
				}
				
				if ( empty($sync_status) ) {
					?> <div id="message" class="updated fade"><p><strong><?php _e( 'Sync finished. But nothing need to be synced.', 'wp-domain-sync-cpanel' ) ?></strong></p></div> <?php
				}
				
				break;
			case "edit":
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->dstable} WHERE cpanel_host = %s", $cpanel_host ) );
				if ( $row ) {
					ds_edit_cpanel( $row );
				} else {
					echo "<h3>" . __( 'cPanel not found', 'wp-domain-sync-cpanel' ) . "</h3>";
				}
			break;
			case "save":
				if ((!empty($_POST[ 'cpanel_host' ])) AND 
					(!empty($_POST[ 'cpanel_user' ])) AND 
					(!empty($_POST[ 'cpanel_pass' ])) AND 
					(!empty($_POST[ 'cpanel_port' ])) AND 
					(!empty($_POST[ 'cpanel_theme' ]))
				) {
					$check = new domain($_POST[ 'cpanel_host' ], $_POST[ 'cpanel_user' ], $_POST[ 'cpanel_pass' ], $_POST[ 'cpanel_port' ], true, $_POST[ 'cpanel_theme' ],'');
					if(!$check->checkLogin()) {
						?> <div id="message" class="updated-nag"><p><strong><?php _e( 'The cPanel config you have entered is invalid. Please check and enter again.' ) ?></strong></p></div> <?php
					} else {
						if ( ($_POST[ 'orig_cpanel_host' ] == '') and (null == $wpdb->get_var( $wpdb->prepare( "SELECT cpanel_host FROM {$wpdb->dstable} WHERE cpanel_host = %s", $cpanel_host ) )) ) {
							$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->dstable} ( `cpanel_host`, `cpanel_user`, `cpanel_pass`, `cpanel_port`, `cpanel_theme` ) VALUES ( %s, %s, %s, %d, %s )", $cpanel_host, $_POST[ 'cpanel_user' ], $_POST[ 'cpanel_pass' ], $_POST[ 'cpanel_port' ], $_POST[ 'cpanel_theme' ] ) );
							//sync immediately when added
							ds_alias_sync_wp_cpanel($check);
							
							echo "<p><strong>" . __( 'cPanel Added and Synced', 'wp-domain-sync-cpanel' ) . "</strong></p>";
						} else {
							$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->dstable} SET cpanel_host = %s, cpanel_user = %s, cpanel_pass = %s, cpanel_port = %d, cpanel_theme = %s WHERE cpanel_host = %s", $cpanel_host, $_POST[ 'cpanel_user' ], $_POST[ 'cpanel_pass' ], $_POST[ 'cpanel_port' ], $_POST[ 'cpanel_theme' ], $_POST[ 'orig_cpanel_host' ] ) );
							
							//sync immediately when edited
							ds_alias_sync_wp_cpanel($check);
							
							echo "<p><strong>" . __( 'cPanel Updated and Synced', 'wp-domain-sync-cpanel' ) . "</strong></p>";
						}
					}
				}
			break;
			case "del":
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dstable} WHERE cpanel_host = %s", $cpanel_host ) );
				echo "<p><strong>" . __( 'cPanel Deleted', 'wp-domain-sync-cpanel' ) . "</strong></p>";
			break;
		}
		
	}

	ds_edit_cpanel();
	$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->dstable} ORDER BY cpanel_id DESC LIMIT 0,20" );
	ds_cpanel_listing( $rows );
	//echo '<p>' . sprintf( __( '<strong>Note:</strong> %s', 'wp-domain-sync-cpanel' ), ds_idn_warning() ) . "</p>";
}


function ds_idn_warning() {
	return sprintf( __( 'You need to press the \'Sync All Now\' button everytime you have a new cPanel host. All existing cPanel is already synced.', 'wp-domain-sync-cpanel' ));
}


function ds_network_page() {
	add_submenu_page('settings.php', 'Domain Sync cPanel', 'Domain Sync cPanel', 'manage_options', 'ds_domains_admin', 'ds_domains_admin');
}





//add_action('admin_init', 'ds_check');
//add_action('ds_domains_admin', 'ds_create_all_obj');
add_action('dm_handle_actions_add', 'ds_domain_add', 10, 1); //hooks to action dm_handle_actions_add from MU Multidomain
add_action('dm_handle_actions_del', 'ds_domain_del', 10, 1); //hooks to action dm_handle_actions_del from MU Multidomain

if(($_POST['_wp_http_referer'] == '/wp-admin/network/settings.php?page=dm_domains_admin') and (!empty($_POST['action']))) {
	add_action('dm_domains_admin', 'ds_domain_sync_admin'); //hooks to action dm_domains_admin from MU Multidomain
	do_action('dm_domains_admin'); //this one must be executed here since this is not yet executed in MU Multidomain
}

add_action( 'network_admin_menu', 'ds_network_page' );

