<?php 
//namespace wp-content\plugins\fbchat\component\model; 
/**
 * Receiver/Responder delle richieste AJAX 
 * @package FBCHAT::MESSAGES::wp-content::plugins::fbchat 
 * @subpackage component
 * @subpackage model
 * @author 2Punti - Marco Biagioni
 * @version $Id: receiver.php 68 02/01/2012 21:21:20Z marco $     
 * @copyright (C) 2011 - 2PUNTI SRL
 * @license GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html  
 */
 
//Inizializzazione variabili CGI
global $fbchatResponse, $fbchatMessages, $fbchatConfig, $fbchatUserid;
$fbchatResponse = array ();
$fbchatMessages = array ();
$fbchatForceParams = @$_REQUEST['getParams'];
$fbchatChatbox = @$_REQUEST['chatbox'];
$fbchatBuddylist = @$_REQUEST['buddylist'];
$fbchatInitialize = @$_REQUEST['initialize'];
$fbchatUpdate_session = @$_REQUEST['updatesession'];
$fbchatPost_sessionvars = @$_REQUEST['sessionvars'];

//Inizializzazione variabili di sessione
$fbchat_user_session_messages = @$_SESSION['jfbcchat_user_'.$fbchatChatbox];
$fbchat_sessionvars = @$_SESSION['jfbcchat_sessionvars'];
//$fbchatOpenChatBoxId - l'id dell'utente dell'ultimo box aperto
$fbchatOpenChatBoxId = @$_SESSION['jfbcchat_sessionvars']['openChatboxId'];
//$fbchatOpenChatBoxIdUserMessages - un array contentente i messaggi dell'utente dell'ultimo box aperto mantenuti in sessione
$fbchatOpenChatBoxIdUserMessages = @$_SESSION['jfbcchat_user_'.$fbchatOpenChatBoxId];
 
if ($fbchatUserid != 0) {
	// Si richiede messaggi per un particolare chatbox
	if (! empty ( $fbchatChatbox )) {
		if (!empty($fbchat_user_session_messages)) {                 
			$fbchatMessages = $fbchat_user_session_messages;
		} 
		// Send and exit
		sendResponse ();
	} else {
		// Se viene richiesta la lista utenti si inserisce nella response
		if (!empty($fbchatBuddylist) && $fbchatBuddylist == 1) { 
			getBuddyList($fbchatConfig); 
		}
		
		// Se è l'initialize ovvero la prima ajax call si recuperano i messaggi dell'ultimo box aperto dalla sessione
	 	if (! empty ( $fbchatInitialize ) && $fbchatInitialize == 1) {
	 		getStatus(); 
			if (!empty($fbchat_sessionvars)) {
				$fbchatResponse['initialize'] = $fbchat_sessionvars;
			
				if (!empty($fbchatOpenChatBoxId) && !empty($fbchatOpenChatBoxIdUserMessages)) {
					$fbchatMessages = array_merge($fbchatMessages,$fbchatOpenChatBoxIdUserMessages);
				}
			}
		} else { 
			// Tutte le seguenti ajax call
			if (empty($fbchat_sessionvars)) {
				$fbchat_sessionvars = array();
			}

			if (!empty($fbchatPost_sessionvars)) {
				ksort($fbchatPost_sessionvars);
			} else {
				$fbchatPost_sessionvars= '';
			}

			// Settaggio in sessione delle session_vars in post dalla JS APP tra cui activeChatBoxes con iduser|nummessagesnew
			if (!empty($fbchatUpdate_session) && $fbchatUpdate_session == 1) { 
				$_SESSION['jfbcchat_sessionvars'] = $fbchatPost_sessionvars;
			}

			if (@$_SESSION['jfbcchat_sessionvars'] != @$_POST['sessionvars']) {
				$fbchatResponse['updatesession'] = @$_SESSION['jfbcchat_sessionvars'];
			}
			
			if($fbchatForceParams){
				$fbchatResponse['paramslist'] = $fbchatConfig;
			}
		}
	 
		//Otteniamo la lista messaggi
		fetchMessages($fbchatConfig);
		sendResponse();
	} 
} else {
	$fbchatResponse ['loggedout'] = '1';
	unset ( $_SESSION ['fbchat'] ); 
	sendResponse();
}

function sendResponse() {
	global $fbchatMessages, $fbchatResponse;
	
	if (! empty ( $fbchatMessages )) {
		$fbchatResponse ['messages'] = $fbchatMessages;
	}
	
	header ( 'Content-type: application/json; charset=utf-8' );
	echo json_encode ( $fbchatResponse );
	exit ();
} 