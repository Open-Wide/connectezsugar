<?php 

class Module_Object_Accessor {
	
	protected $offset = 0;
	protected $last_related_module;
	protected $paquet = 500;
	protected $sugar_connector;
	const INIPATH = 'extension/connectezsugar/settings/';
	
	public $logs = array();
	
	public function __construct( ) {
		$this->sugar_connector = new SugarConnector();
		if ( ! $this->sugar_connector->login() ) {
			throw new Exception( 'Impossible de se connecter avec le Sugar Connector' );
		}
	}

	public function __destruct( ) {
		unset( $this->sugar_connector );
	}
	
	protected function get_last_synchro_date_time( $block_name ) {
		$ini_synchro = eZINI::instance( 'synchro.ini.append.php', self::INIPATH );
		$ini_synchro->resetCache(); // Plusieurs scripts sont lancés successivement, on vide donc le cache à chaque appel
		$datetime = $ini_synchro->variable( $block_name, 'last_synchro_' . $this->module_name );
		return strtotime( $datetime );
	}
	
	protected function set_last_synchro_date_time( $block_name ) {
		$datetime    = date("Y-m-d H:i:s", time());
		$ini_synchro = eZINI::instance( 'synchro.ini.append.php', self::INIPATH );
		
		$ini_synchro->setVariable($block_name, 'last_synchro_' . $this->module_name, $datetime);
		if ( $ini_synchro->save() ) {
			$this->notice( 'Sauvegarde de la date de synchro pour ' . $block_name . '_' . $this->module_name . ' : ' . $datetime );
			$ini_synchro->resetCache(); // Plusieurs scripts sont lancés successivement, on vide donc le cache à chaque sauvegarde pour ne pas réécrire des valeurs incorrectes
			return $datetime;
		} else {
			$this->error( 'Erreur lors de la sauvegarde de la date de synchro pour ' . $block_name . '_' . $this->module_name );
			return false;
		}
	}
	
	protected function get_sugar_ids( $relation, $timestamp = '' ) {
		
		$related_module = $relation[ 'related_module_name' ];
		
		// OFFSET MANAGEMENT
		if ( $this->last_related_module != $related_module ) {
			$this->offset = 0;
		}
		$this->last_related_module = $related_module;
		
		if ($timestamp) {
			$from_date = date('Y-m-d H:i:s', $timestamp); //'2012-08-10 23:00:00';
			$to_date   = date('Y-m-d H:i:s');
		} else {
			$from_date = '1970-01-01 00:00:00';
			$to_date   = date('Y-m-d H:i:s');
		}
		
		$max_results = 99999;
		$deleted = true;
		
		$entries = $this->sugar_connector->sync_get_relationships( $this->module_name, $related_module, $from_date, $to_date, $this->offset, $max_results, $deleted);
		
		// ERROR MANAGEMENT
		if (
			! is_array( $entries ) ||
			! isset( $entries[ 'data' ] ) ||
			( isset($entries['error'] ) && $entries['error']['number'] !== '0' )
		) {
			if (isset( $entries[ 'error' ] ) && $entries[ 'error' ][ 'number' ] !== '0' ) {
				throw new Exception( 'Erreur du Sugar connecteur sur la liste des entrées du module ' . $this->module_name . ' : ' . $entries[ 'error' ][ 'number' ] . ' - ' . $entries[ 'error' ][ 'name' ] . ' - ' . $entries[ 'error' ][ 'description' ] );
			} else {
				throw new Exception( 'Erreur du Sugar connecteur sur la liste des entrées du module ' . $this->module_name );
			}
		}
		
		$entries_decoded     = unserialize( base64_decode( $entries[ 'data' ] ) );
		$relation_field_name = $this->get_relation_field_name( $relation[ 'name' ] );
		
		// TREATMENT
		$sugar_ids = array( );
		foreach ( $entries_decoded as $entry ) {
			if ( isset( $entry[ 'name_value_list' ] ) && isset( $entry[ 'name_value_list' ][ $relation_field_name ] ) && isset( $entry[ 'name_value_list' ][ $relation_field_name ][ 'value' ] ) ) {
				if ( !in_array( $entry[ 'name_value_list' ][ $relation_field_name ][ 'value' ], $sugar_ids ) ) {
					$sugar_ids[ ] = $entry[ 'name_value_list' ][ $relation_field_name ][ 'value' ];
				}
			} else {
				$this->warning( 'Entry invalide' );
			}
		}
		$this->notice( 'offset=' . $this->offset . ' - entries=' . count( $sugar_ids ) );
		$this->offset += $this->paquet;
		return $sugar_ids;
	}
	
	
	/*
	 * DATABASE RELATION METHODS
	 */
	protected function get_relation_field_name( $relation_name ) {
		if ( substr_count ( $relation_name, '_one_' ) == 0 ) {
			$suffixe     = '_ida';
		} else {
			$suffixe     = '_idb';
		}
		
		if ( $this->sugar_connector->testFieldNameRelation == 'true' ) {
			// Nommage des champs de relation de la forme "many_visi_5019p_visit_ida"
			return self::get_valid_db_name( $relation_name . $this->module_name . $suffixe, TRUE ); // Local / Recette
		} else {
			// Nommage des champs de relation de la forme "many_visi_many_contotcp_visit_ida"
			return self::get_valid_db_name( $relation_name . $this->module_name . $suffixe, FALSE, 50 ); // Prod
		}
	}
	
	// cf fonction identique côté sugarCRM getValidDBName()
	static function get_valid_db_name($name, $ensureUnique = false, $maxLen = 30) {
		
	    // first strip any invalid characters - all but alphanumerics and -
	    $name   = preg_replace ( '/[^\w-]+/i', '', $name ) ;
	    $len    = strlen ( $name ) ;
	    $result = $name;
	    if ( $ensureUnique ) {
	        $md5str = md5( $name );
	        $tail   = substr ( $name, -11 ) ;
	        $temp   = substr( $md5str , strlen( $md5str ) - 4 );
	        $result = substr ( $name, 0, 10 ) . $temp . $tail ;
	    } else if ( $len > ( $maxLen - 5 ) ) {
	        $result = substr ( $name, 0, 11 ) . substr ( $name, 11 - $maxLen + 5 );
	    }
	    return strtolower ( $result ) ;
	}
	
	
	
	protected function notice( $str ) {
		$this->logs[ ] = 'NOTICE : ' . $str;
		if ( !is_null( $this->cli ) ) {
			$this->cli->notice( $str );
		}
	}
	
	protected function warning( $str ) {
		$this->logs[ ] = 'WARNING : ' . $str;
		if ( !is_null( $this->cli ) ) {
			$this->cli->warning( $str );
		}
	}
	
	protected function error( $str ) {
		$this->logs[ ] = 'ERROR : ' . $str;
		if ( !is_null( $this->cli ) ) {
			$this->cli->error( $str );
		}
	}
}
?>