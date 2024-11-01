<?php 
//namespace wp-content\plugins\fbchat; 
/**
 * Main admin wp plugin file
 * @package FBCHAT::MESSAGES::wp-content::plugins::fbchat
 * @author 2Punti - Marco Biagioni
 * @version $Id: admin.php 68 02/01/2012 21:21:20Z marco $     
 * @copyright (C) 2011 - 2PUNTI SRL
 * @license GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html  
 */

// Aggiunge il link settings
function fbchat_plugin_action_links($links, $file) {
	if ($file == plugin_basename ( dirname ( __FILE__ ) . '/fbchat.php' )) {
		$links [] = '<a href="admin.php?page=fbchat-key-config">' . __ ( 'Settings' ) . '</a>';
	}
	
	return $links;
}
add_filter ( 'plugin_action_links', 'fbchat_plugin_action_links', 10, 2 );

// Renderizza il form di configurazione e salva i dati sul DB
function fbchat_conf() {
	// Live Site
	$siteUrl = get_option('siteurl') . '/wp-content/plugins/wpfacebookchat-free/component/'; 
	// CONFIG LOAD DA DB OPTIONS
	$fbchatQuery = "SELECT * FROM fbchat_config";
	$fbchatConfig = mysql_fetch_object(mysql_query($fbchatQuery));
 
	// Save config da POST submit
	if ( isset($_POST['submit']) ) {
		$update = "UPDATE fbchat_config SET";
		$fields = array();
		foreach ($fbchatConfig as $paramName=>&$paramValue) {
			$fields[] = "\n `$paramName` = '$_POST[$paramName]'";
			// Istant assignment
			$paramValue = $_POST[$paramName];
		} 
		$fbchatQuery = $update . implode(',', $fields);
		if(mysql_query($fbchatQuery)) {
			echo '<div id="message" class="updated"><p>Settings saved</p></div>'; 
		} else {
			echo '<div id="message" class="updated"><p>Error saving settings</p></div>'; 
		}
	}
	 
	// Config form generation
	if(is_object($fbchatConfig)) {
		$config = null;
		foreach ($fbchatConfig as $paramNameForm=>$paramValueForm) {
			$labelValues = fbchatTransformFunctionLabel($paramNameForm);
			$config .= "<div style='height:30px;'><label title='" . $labelValues[1] . "' style='float:left;width:240px'>" . $labelValues[0] . "</label>";
			$config .= fbchatTransformFunctionInput($paramNameForm, $paramValueForm);
			$config .= "</div>";
		} 
	} 
	?>
	<fieldset style="border:1px solid #CCC;padding:20px;margin-top:30px;">
		<legend style="width: 420px;">
			<img src="<?php echo $siteUrl;?>images/config_icon.png" alt="config_icon"/><label style="font-weight: bold;margin-top:30px;display: block;float: right;font-size: 14px;">WPFacebookChat Free settings</label>
		</legend>
		<form action="" method="post" id="fbchat-conf" >
			<?php echo $config;?>
			<label style='float:left;width:240px'>&nbsp;</label><input type="submit" name="submit"  value="<?php _e('Save settings'); ?>"/> 
		</form>
	</fieldset>
	<? 
}
  
function fbchat_load_menu() { 
	add_submenu_page ( 'plugins.php', __ ( 'WPfbchat settings' ), __ ( 'WPfbchat settings' ), 'manage_options', 'fbchat-key-config', 'fbchat_conf' ); 
}
add_action ( 'admin_menu', 'fbchat_load_menu' ); 

/**
 * Funzione di trasformazione HTML controls, restituisce il controllo richiesto
 * @param string $control
 * @param mixed $value
 * @return string
 */
function fbchatTransformFunctionInput($control, $value) {
	// Main switch
	switch ($control) {
		case 'forceavailable':
		case 'gcenabled':
		case 'noconflict':
		case 'openchatmode':
		case 'openpopupmode':
		case 'cropmode':
		case 'avatarupload':
			$checked = (bool)$value ? 'checked' : '';
			$nochecked = !(bool)$value ? 'checked' : '';
			$str = "<input type='radio' name='$control' $nochecked value='0'/>&nbsp;No";
			$str .= "&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;  <input type='radio' name='$control' $checked value='1'/>&nbsp;Yes";
			break;
			
		case 'language':
			$optionsArray = fbchatSelectLists('lang');
			foreach ($optionsArray as $option) {
				$checked = $option === $value ? 'selected="selected"' : '';
				$options .= '<option ' . $checked . ' value="' . $option . '">' . $option . '</option>';
			}
			$str = '<select name="language">' . $options . '</select>';
			break;
			
		case 'template':
			$optionsArray = fbchatSelectLists('css');
			foreach ($optionsArray as $option) {
				$checked = $option === $value ? 'selected="selected"' : '';
				$options .= '<option ' . $checked . ' value="' . $option . '">' . $option . '</option>';
			}
			$str = '<select name="template">' . $options . '</select>';
			break;
			
		default:
			$str = "<input type='text' name='$control' value='$value'/>";	
	} 
	return $str;
}

/**
 * Funzione di trasformazione HTML controls, restituisce il controllo richiesto
 * @param string $control 
 * @return array
 */
function fbchatTransformFunctionLabel($control) {
	// Text translations
	switch ($control) {
		case 'chatrefresh':
			$str = "Chat refresh(seconds)";
			$title = "Set chat messages refresh interval in seconds";
			break;
		
		case 'refresh_buddylist':
			$str = "Userslist refresh(seconds)";
			$title = "Set userslist refresh interval in seconds";
			break;
	
		case 'online_timeout':
			$str = "Online users timeout(seconds)";
			$title = "Estabilish the time interval from last user activity to consider a user logged in and present in chat list, in seconds.";
			break;
			
		case 'forceavailable':
			$str = "Disable offline users interval";
			$title = "Consider a user always online when logged regardless of the interval of setted idle time to consider it offline; if a user chooses explicitly a different state this will take precedence.";
			break;
			
		case 'lastmessagetime':
			$str = "Offline user status time(seconds)";
			$title = "Estabilish the time interval from last sent message to consider a user still present in chat list but in offline status with gray dot, in seconds.";
			break;
			
		case 'audioenabled':
			$str = "Audio On/Off";
			$title = "Enable audio alert for messages incoming and new connected clients ";
			break;
			
		case 'gcenabled':
			$str = "Garbage On/Off";
			$title = "Set if the garbage collector must be active";
			break;
			
		case 'probability':
			$str = "Garbage probability";
			$title = "Set the probability that garbage collector is started";
			break;
			
		case 'maxlifetime':
			$str = "Max messages lifetime(seconds)";
			$title = "Set the max lifetime of the messages to be considered valid before to garbage collect them in seconds: default 1 hour. It must be greater than time to consider user offline to prevent undesired deleted messages.";
			break;
			
		case 'language':
			$str = "Language file";
			$title = "Select a language file for chat translations.";
			break;
			
		case 'template':
			$str = "Chat templates";
			$title = "Provides a list of css templates available for using for the chat frontend. If you add more css files in the components folders, they will be immediately available in the selectbox.";
			break;
			
		case 'noconflict':
			$str = "JQuery noConflict Mode";
			$title = "Manages how to avoid conflicts between JQuery and other javascript libraries on the page such as Mootools. It is possible to change the option in case of errors.";
			break;
			
		case 'openchatmode':
			$str = "Chat popup open mode";
			$title = "Determines how to behave when you click on users in the list, if opening the tab and the messages list or just the tab below.";
			break;
			
		case 'openpopupmode':
			$str = "New messages tabs popup";
			$title = "Determines the behavior when users sends new messages, if the component must open a tab with a small popup containing the number of new messages in the queue, or directly open the chat popup list of messages. You will always hear the sound alert.";
			break;
		
		case 'maxfilesize':
			$str = "Max avatar file size(MB)";
			$title = "This set the max size admitted for avatars uploaded by users in MB. Default 2MB";
			break;
		
		case 'extensions':
			$str = "Avatar file extensions";
			$title = "Specify the admitted file extensions being accepted for avatar processing, comma separated list";
			break;
			
		case 'cropmode':
			$str = "Avatar crop mode";
			$title = "This set how the uploaded avatars will be resized, mantaining the proportions not exceeding the max width or being cropped to max admitted dimensions";
			break;
			
		case 'avatarupload':
			$str = "Enable avatar upload";
			$title = "Estabilish if users can choose to upload personalized avatars";
			break;
			
		default:
			$str = $control;
			$title = $control;
	}
	return array($str, $title);
}


/**
 * Recupero listings per language files e templates files
 * @param string $type
 * @return array
 */
function fbchatSelectLists($type) {
	$directory = dirname(__FILE__) . "/component/" . $type;
    $filenames = array();
    if(is_dir($directory)) {
	    $iterator = new DirectoryIterator($directory);
	    foreach ($iterator as $fileinfo) {
	        if ($fileinfo->isFile() && $fileinfo->getFileName() !== 'index.html') {
	            $filenames[] = $fileinfo->getFilename();
	        }
	    } 
	    return $filenames;
    }
    return array();
}




