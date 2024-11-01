<?php
//namespace wp-content\plugins\fbchat;  
/** 
 * Main wp install and render plugin
 * @package FBCHAT::wp-content::plugins::fbchat
 * @author 2Punti - Marco Biagioni
 * @version $Id: fbchat.php 5 04/01/2012 13:51:40Z marco $     
 * @copyright (C) 2011 - 2PUNTI SRL
 * @license GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html  
 */

/*
Plugin Name: FBChat
Plugin URI: http://www.2punti.eu
Description: WP Facebook Chat Plugin
Author: 2punti srl
Version: 1.0
Author URI: http://www.2punti.eu
*/
 

// SITE SECTION OUTPUT SCRIPTS
function fbchat() { 
	$siteUrl = get_option('siteurl') . '/wp-content/plugins/wpfacebookchat-free/component/';
	$jsInject = <<<JS
	<script type="text/javascript">
		var jfbc_baseURI = '$siteUrl';
	</script>
JS;
	echo $jsInject;
	
	// CONFIG LOAD DA DB OPTIONS
	$fbchatQuery = "SELECT * FROM fbchat_config";
	$fbchatConfig = mysql_fetch_object(mysql_query($fbchatQuery));

	echo '<link type="text/css" href="' . $siteUrl . 'css/' . $fbchatConfig->template . '" rel="stylesheet" charset="utf-8">'; 
	echo '<script type="text/javascript" src="' . $siteUrl . 'js/jquery.js" charset="utf-8"></script>';
	if($fbchatConfig->noconflict) {
		echo '<script type="text/javascript" src="' . $siteUrl . 'js/jquery.noconflict.js" charset="utf-8"></script>'; 
	}
	echo '<script type="text/javascript" src="' . $siteUrl . 'lang/'. $fbchatConfig->language . '" charset="utf-8"></script>'; 
	echo '<script type="text/javascript" src="' . $siteUrl . 'js/fbchat.js" charset="utf-8"></script>'; 
}

// Now we set that function up to execute when the admin_notices action is called 
add_action( 'wp_head', 'fbchat' );  

function fbchat_install() {
	global $wpdb, $table_prefix;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$sqlCreateMainTable = "DROP TABLE IF EXISTS `fbchat`;
	CREATE TABLE IF NOT EXISTS `fbchat` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`from` int(10) unsigned NOT NULL,
	`to` int(10) unsigned NOT NULL,
	`message` text NOT NULL,
	`sent` int(10) unsigned NOT NULL default '0',
	`read` tinyint(1) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id`),
	KEY `to` (`to`),
	KEY `from` (`from`), 
	KEY `read` (`read`),
	KEY `sent` (`sent`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
 	dbDelta($sqlCreateMainTable);
 
	$sqlCreateStatusTable = "DROP TABLE IF EXISTS `fbchat_status`;
	CREATE TABLE IF NOT EXISTS `fbchat_status` (
	`userid` int(10) unsigned NOT NULL,
	`message` text,
	`status` enum('available','away','busy','invisible','offline') default NULL, 
	PRIMARY KEY  (`userid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;"; 
	dbDelta($sqlCreateStatusTable);
	
	$sqlCreateConfigTable = "DROP TABLE IF EXISTS `fbchat_config`;
	 CREATE TABLE IF NOT EXISTS `fbchat_config` (
	`chatrefresh` int(11) NOT NULL DEFAULT 2,
	`refresh_buddylist` int(11) NOT NULL DEFAULT 5,
	`online_timeout` int(11) NOT NULL DEFAULT 600,  
	`lastmessagetime` int(11) NOT NULL DEFAULT 300,
	`gcenabled` tinyint(4) NOT NULL DEFAULT 1,
	`probability` int(11) NOT NULL DEFAULT 50,
	`maxlifetime` int(11) NOT NULL DEFAULT 3600,
	`language` varchar(255) NOT NULL DEFAULT 'english.js',
	`template` varchar(255) NOT NULL DEFAULT 'fbchat_gray.css',
	`noconflict` tinyint(4) NOT NULL DEFAULT 1
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	dbDelta($sqlCreateConfigTable);
	
	$usertable = $table_prefix .'users';
	$fbchatQueryAlter = "ALTER TABLE `$usertable` ADD COLUMN `lastactivity` INT default 0;";
  	mysql_query($fbchatQueryAlter);
} 
register_activation_hook(__FILE__,'fbchat_install');
 
function fbchat_install_data() {
	global $wpdb;  
	$table_name = 'fbchat_config';
	$wpdb->insert( $table_name, array( 'chatrefresh' => 2));
}
register_activation_hook(__FILE__,'fbchat_install_data'); 

function fbchat_uninstall() {
	global $wpdb, $table_prefix;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$sqlCreateMainTable = "DROP TABLE IF EXISTS `fbchat`;";
 	mysql_query($sqlCreateMainTable);
 
	$sqlCreateStatusTable = "DROP TABLE IF EXISTS `fbchat_status`;"; 
	mysql_query($sqlCreateStatusTable);
	
	$sqlCreateConfigTable = "DROP TABLE IF EXISTS `fbchat_config`;";
	mysql_query($sqlCreateConfigTable);
	
	$usertable = $table_prefix .'users';
	$fbchatQueryAlter = "ALTER TABLE `$usertable` DROP `lastactivity`";
  	mysql_query($fbchatQueryAlter);
}
register_uninstall_hook( __FILE__, 'fbchat_uninstall' );

// ADMIN SECTION
if ( is_admin() )
	require_once dirname( __FILE__ ) . '/admin.php';
?>
