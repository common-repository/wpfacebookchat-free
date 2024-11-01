<?php
//namespace wp-content\plugins\fbchat\component\model;  
/** 
 * Gestore dei messaggi e dei dati 
 * @package FBCHAT::MESSAGES::wp-content::plugins::fbchat 
 * @subpackage component
 * @subpackage model
 * @author 2Punti - Marco Biagioni
 * @version $Id: data.php 49 04/01/2012 13:51:40Z marco $     
 * @copyright (C) 2011 - 2PUNTI SRL
 * @license GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html  
 */

function getStatus() {
	global $fbchatResponse, $fbchatUserid, $fbchatStatus; 

	$sql = ("SELECT fbchat_status.message, fbchat_status.status FROM fbchat_status WHERE userid = '".(int)$fbchatUserid."'");
 	$fbchatQuery = mysql_query($sql); 
	$chat = mysql_fetch_array($fbchatQuery);
 
	if (empty($chat['status'])) {
		$chat['status'] = 'available';
	} else {
		if ($chat['status'] == 'offline') {
			$_SESSION['jfbcchat_sessionvars']['buddylist'] = 0;
		}
	}
	
	if (empty($chat['message'])) {
		$chat['message'] = "-";
	}

	$fbchatStatus = array('message' => $chat['message'], 'status' => $chat['status']);
	$fbchatResponse['userstatus'] = $fbchatStatus;
}
 
function getBuddyList($parms) {
	global $fbchatResponse, $fbchatUserid, $fbchatUsername, $fbchatUseremail, $fbchatUserDisplayname;  

	$time = current_time('timestamp');
	$buddyList = array();
	
	if ((empty($_SESSION['jfbcchat_buddytime'])) || ($_POST['initialize'] == 1)  || 
		(!empty($_SESSION['jfbcchat_buddytime']) && ($time-$_SESSION['jfbcchat_buddytime'] >= $parms->refresh_buddylist))) { 
			$sql = ("SELECT DISTINCT ".
					"\n u.".DB_USERTABLE_USERID. " AS userid, ".
					"\n u.".DB_USERTABLE_NAME. " AS username, ".
					"\n u.".DB_USERTABLE_LASTACTIVITY. " AS lastactivity, CONCAT(".
					"\n u.".DB_USERTABLE_USERID.",'|',u.user_email,'|',u.display_name) AS avatar, ".
					"\n u.user_nicename AS link, fbs.message, fbs.status,".
					"\n MAX(fb.sent) AS lastmessagetime".
					"\n FROM ". TABLE_PREFIX.DB_USERTABLE ." AS u".
					"\n LEFT JOIN fbchat AS fb ON u.id = fb.from".
					"\n LEFT JOIN fbchat_status AS fbs ON u.".DB_USERTABLE_USERID." = fbs.userid".
					"\n WHERE ".
					"\n u.".DB_USERTABLE_USERID." <> '". (int)$fbchatUserid.
					"' AND ('".$time."'-u.".DB_USERTABLE_LASTACTIVITY." < '". ($parms->online_timeout).
					"')  GROUP BY u." . DB_USERTABLE_USERID .
					"\n ORDER BY u.".DB_USERTABLE_NAME." asc");
			$fbchatQuery = mysql_query($sql); 
			$error = mysql_error();
			while ($chat = mysql_fetch_array($fbchatQuery, MYSQL_ASSOC)) {   
					if (($time-$chat['lastmessagetime'] > $parms->lastmessagetime) && ($chat['status'] == 'available' || is_null($chat['status']))) {
						$chat['status'] = 'offline'; 
					} else {
						if(is_null($chat['status'])) {
							$chat['status'] = 'available'; 
						}
					}
					if ($chat['message'] == null) {
						$chat['message'] = '';
					}
					  
					if (!empty($chat['username'])) {
						$buddyList[] = array('id' => $chat['userid'], 
											'name' => $chat['username'], 
											'status' => $chat['status'], 
											'message' => $chat['message'],   
											'time' => $chat['lastactivity'],
											'lastmessagetime' => $chat['lastmessagetime']);
					} 
			}
 
			//Riaggiorniamo il time in sessione dell'ultimo refresh lista utenti
			$_SESSION['jfbcchat_buddytime'] = $time;
 
			if (!empty($buddyList)) {
				$fbchatResponse['buddylist'] = $buddyList; 
			} else {
				$fbchatResponse['buddylist'] = false;
			}

			if(!empty($parms)) { 
		 		$fbchatResponse['paramslist'] = $parms; 
			}
			$fbchatResponse['my_username'] = $fbchatUsername; 
	} 
}
 
function fetchMessages($parms) {
	global $fbchatResponse, $fbchatUserid, $fbchatUseremail, $fbchatUserDisplayname, $fbchatMessages;

	//Sezione Garbage
	if((bool)$parms->gcenabled){
		require_once 'garbage.php'; 
		$gc = new fbchatGarbage($parms);
		//Exec GC Probability
		$execGC = $gc->execGC();
		//Back to JS domain
		$fbchatResponse['execGC'] = $execGC;
	}
	
	$timestamp = 0;
 	$lastNewMessageID = null;
	
 	$sql = "SELECT fbchat.id, fbchat.from, fbchat.to, fbchat.message, fbchat.sent, fbchat.read, u.id AS userid,".
 			"\n CONCAT(u.".DB_USERTABLE_USERID.",'|',"."u.user_email,'|',u.display_name) AS avatar, u.user_nicename AS profilelink".
			"\n FROM fbchat".
 			"\n LEFT JOIN " . TABLE_PREFIX.DB_USERTABLE . " AS u".
 			"\n ON fbchat.from = u.id".
 			"\n WHERE (fbchat.to = '".(int)$fbchatUserid. "' OR fbchat.from = '".(int)$fbchatUserid. "' )".
 			"\n AND fbchat.read != 1".
 			"\n ORDER BY fbchat.id";
	$fbchatQuery = mysql_query($sql);
	$error = mysql_error();
	while ($chat = mysql_fetch_array($fbchatQuery, MYSQL_ASSOC)) { 
		$self = 0;
		$old = 0;
		// Inversione from per i self messages
		if ($chat['from'] == $fbchatUserid) {
			$chat['from'] = $chat['to'];
			$self = 1;
			$old = 1;
		} 
		$fbchatMessages[] = array('id' => $chat['id'], 
							'from' => $chat['from'], 
							'message' => $chat['message'], 
							'self' => $self, 
							'old' => $old);

		//Mette i nuovi messaggi provenienti dal mittente in sessione se non propri, vecchi e già letti
		if ($self == 0 && $old == 0 && $chat['read'] != 1) {
			$_SESSION['jfbcchat_user_'.$chat['from']][] = array('id' => $chat['id'], 
																'from' => $chat['from'],  
																'message' => $chat['message'], 
																'self' => 0, 
																'old' => 1);
		} 
		$lastNewMessageID = $chat['id'];
	}	
 
	//Adesso aggiorna lo stato dei messaggi come letti	
	if ($lastNewMessageID) {
		$sql = ("UPDATE fbchat SET fbchat.read = '1'".
				"\n WHERE fbchat.to = '".(int)$fbchatUserid.
				"' AND fbchat.id <= '".(int)$lastNewMessageID."'");
		$fbchatQuery = mysql_query($sql); 
			
	}
} 