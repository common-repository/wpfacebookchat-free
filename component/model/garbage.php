<?php
//namespace wp-content\plugins\fbchat\component\model;  
/**  
 * @package FBChat::wp-content::plugins::fbchat 
 * @subpackage component
 * @subpackage model
 * @author 2Punti - Marco Biagioni
 * @version $Id: garbage.php 42 01/01/2011 16:31:20Z marco $     
 * @copyright (C) 2011 - 2PUNTI SRL
 * @license GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html  
 */

/**
 * Classe che gestisce il garbage collector dei messaggi obsoleti nel database 
 * @package FBChat::wp-content::plugins::fbchat 
 * @subpackage component
 * @subpackage model 
 * @since 1.0
 */ 
class fbchatGarbage {
	
	/**
	 * Memorizza se il garbage è attivato 
	 * @access private
	 * @var Boolean
	 */
	private $enabled;
	/**
	 * Rappresenta il massimo tempo oltre cui considerare un messaggio obsoleto 
	 * @access private
	 * @var int
	 */
	private $maxLifeTime;
	/**
	 * Decide la probabilità che il garbage collector venga avviato
	 * @access private
	 * @var int
	 */
	private $probability;
	/**
	 * @property Boolean $divisor - Il divisore probabilistico 
	 * @access private
	 * @var int
	 */
	private $divisor;
	/**
	 * Memorizza un reference all'oggetto database
	 * @access private
	 * @var Object &
	 */
	private $DBO;
	
	/**
	 * Esegue il calcolo probabilistico vero e proprio
	 * @return Boolean
	 */
	private function probabilityFn() {
		$randomNumber = rand ( 0, $this->divisor );
		if (0 < $randomNumber && $randomNumber <= $this->probability) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Costruisce la query di DELETE dal DB in caso di match
	 * @return String
	 */
	private function buildQuery() {
		$time_attuale = time ();
		$soglia = $time_attuale - $this->maxLifeTime;
		//Assicuriamoci di cancellare messsaggi letti
		$fbchatQuery = "DELETE FROM fbchat WHERE (`sent` < " . ( int ) $soglia . " AND `read` = 1)";
		return $fbchatQuery;
	}
	
	/**
	 * Setta i parametri di configurazione nelle private properties
	 * @param Object& $configParams
	 * @return Object&
	 */
	public function __construct(&$configParams) {
		global $wpdb;
		/** 
		 * Inizializzazione oggetto garbage collector 
		 */
		$this->DBO = &$wpdb;
		$this->probability = ( int ) $configParams->probability;
		$this->maxLifeTime = ( int ) $configParams->maxlifetime;
		$this->enabled = ( int ) $configParams->gcenabled;
		$this->divisor = 100;
	}
	
	/**
	 * Esegue il garbage collector process dando avvio solo se il calcolo probabilistico ha esito positivo 
	 * @return Boolean
	 */
	public function execGC() {
		$match = $this->probabilityFn ();
		if ($match && $this->enabled) {
			$fbchatQuery = $this->buildQuery (); 
			if (!$this->DBO->query ( $fbchatQuery )) {
				return false;
			}
			return true;
		} else {
			return false;
		}
	} 
} 