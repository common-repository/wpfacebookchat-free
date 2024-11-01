<?php  
//namespace wp-content\plugins\fbchat\component\model;  
/**
 * Sender dei messaggi 
 * @package FBCHAT::MESSAGES::wp-content::plugins::fbchat 
 * @subpackage component
 * @subpackage model
 * @author 2Punti - Marco Biagioni
 * @version $Id: sender.php 54 15/01/2012 15:30:40Z marco $     
 * @copyright (C) 2011 - 2PUNTI SRL
 * @license GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html  
 */


//Inizializzazione variabili CGI
global $fbchatResponse, $fbchatConfig, $fbchatUserid;

//Inizializzazione variabili CGI
$fbchatStatus = $_POST['status'];
$fbchatStatusmessage = $_POST['statusmessage'];
$fbchatMessage = $_POST['message'];
$fbchatMessage = strip_tags($fbchatMessage, '<img>,<br>'); 
$fbchatTo = $_REQUEST['to'];

// Settaggio status dell'utente
if (!empty($fbchatStatus)) {
	$fbchatSql = ("INSERT INTO fbchat_status (userid,status) VALUES ('".$fbchatUserid.
	"','".$fbchatStatus."') ON DUPLICATE KEY UPDATE status = '".$fbchatStatus."'");
	$fbchatQuery = mysql_query($fbchatSql);
	 
	if ($fbchatStatus == 'offline') {
		$_SESSION['jfbcchat_sessionvars']['buddylist'] = 0;
	}

	echo "1";
	exit(0);
}

// Settaggio messaggio status personalizzabile da keyevent 'invio' dal campo testo
if (!empty($fbchatStatusmessage)) { 
		$fbchatSql = ("INSERT INTO fbchat_status (userid,message) VALUES ('".
		$fbchatUserid."','".$fbchatMessage."') ON DUPLICATE KEY UPDATE message = '".
		$fbchatStatusmessage."'");
		$fbchatQuery = mysql_query($fbchatSql);
	  
		echo "1";
		exit(0);
}

// Inserimento/Invio nuovo messaggio a destinatario
if (!empty($fbchatTo) && !empty($fbchatMessage)) { 
	if ($fbchatUserid != '') {    
				$fbchatSql = ("INSERT INTO fbchat (fbchat.from,fbchat.to,fbchat.message,fbchat.sent,fbchat.read) VALUES ('".
				$fbchatUserid."', '".$fbchatTo."','".
				$fbchatMessage."','".current_time('timestamp')."',0)");
				$fbchatQuery = mysql_query($fbchatSql);
				$insertedid = mysql_insert_id();
 
				if (empty($_SESSION['jfbcchat_user_'.$fbchatTo])) {
						$_SESSION['jfbcchat_user_'.$fbchatTo] = array();
					}
			 
			//Memorizziamo in sessione locale mittente il messaggio inviato al to destinatario
		 	$_SESSION['jfbcchat_user_'.$fbchatTo][] = array("id" => $insertedid, "from" => (int)stripslashes($fbchatTo), "message" => stripslashes($fbchatMessage), "self" => 1, "old" => 1) ;
			
			echo $insertedid;
			exit(0);
	} 
}