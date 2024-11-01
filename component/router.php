<?php
//namespace wp-content\plugins\fbchat\component; 
/**
 * Main request router
 * @package FBCHAT::wp-content::plugins::fbchat 
 * @subpackage component
 * @author 2Punti - Marco Biagioni
 * @version $Id: router.php 2 02/01/2012 21:21:20Z marco $     
 * @copyright (C) 2011 - 2PUNTI SRL
 * @license GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html  
 */
 
define('BASE_PATH',dirname(__FILE__));
include_once "../../../../wp-load.php"; 
 
// CREAZIONE USER OBJECT
global $current_user, $table_prefix, $wpdb;
get_currentuserinfo(); 

// Init
$fbchatUserid = null; 
$fbchatUsername = null;
$fbchatUseremail = null; 
if (!empty($current_user->ID)) {
	$fbchatUserid = $current_user->ID;
	$fbchatUsername = $current_user->display_name;
	$fbchatUseremail = $current_user->user_email;
	$fbchatUserDisplayname = $current_user->display_name;
}
  
session_start ();  
ini_set ( 'log_errors', 0 );
ini_set ( 'display_errors', 0 ); 
 
/* CREAZIONE DATABASE OBJECT*/  
define('DB_SERVER',					DB_HOST									);
define('DB_PORT',					'3306'									);
define('DB_USERNAME',				DB_USER									);
define('TABLE_PREFIX',				$table_prefix							);
define('DB_USERTABLE',				'users'									);
define('DB_USERTABLE_USERID',		'ID'									); 
define('DB_USERTABLE_NAME',			'display_name'							);
define('DB_USERTABLE_LASTACTIVITY',	'lastactivity'							);

$fbchatDbConn = mysql_connect ( DB_SERVER . ':' . DB_PORT, DB_USERNAME, DB_PASSWORD );
if (! $fbchatDbConn) {
	echo "<h3>Unable to connect to database. Please check details in configuration file.</h3>";
	exit ();
}
mysql_selectdb ( DB_NAME, $fbchatDbConn );
mysql_query ( "SET NAMES utf8" );
mysql_query ( "SET CHARACTER SET utf8" );
mysql_query ( "SET COLLATION_CONNECTION = 'utf8_general_ci'" );
 
$fbchatSql = ("UPDATE `".TABLE_PREFIX.DB_USERTABLE.
		"` SET ".DB_USERTABLE_LASTACTIVITY." = '".current_time('timestamp').
		"' WHERE ".DB_USERTABLE_USERID." = '".(int)$fbchatUserid."'");
mysql_query($fbchatSql);


// CONFIG LOAD DA DB OPTIONS
$fbchatQuery = "SELECT * FROM fbchat_config";
$fbchatConfig = mysql_fetch_object(mysql_query($fbchatQuery));
 	 
$fbchatEntrypoint = $_REQUEST['entrypoint'];
switch ($fbchatEntrypoint) {
	case 'receive' : 
		require_once 'model/data.php';
		require_once 'model/receiver.php'; 
		break;
	
	case 'send' :
		require_once 'model/sender.php';
		break; 
}
