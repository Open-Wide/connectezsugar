<?php 

class Module_Object_Accessor {
	
	protected $offset = 0;
	protected $last_query;
	protected $paquet = 500;
	protected $sugar_connector;
	
	public function __construct( ) {
		$this->sugar_connector = new SugarConnector();
		if ( ! $this->sugar_connector->login() ) {
			throw new Exception( 'Impossible de se connecter avec le Sugar Connector' );
		}
	}

	public function __destruct( ) {
		unset( $this->sugar_connector );
	}
	
	protected function get_last_synchro_date_time() {
		$inisynchro = eZINI::instance('synchro.ini');
		return strtotime( $inisynchro->variable('Synchro','lastSynchroDatetime') );
	}
	
	public function get_sugar_ids_from_updated_relation( $relation, $since = TRUE ) {
		//@TODO : Requêter sur les tables de relation au lieu de la table de l'objet (ex : many_rest_many_cont_c au lieu de otcp_restaurant)
		$query = '';
		if ( $since !== FALSE ) {
			if ( $since === TRUE ) {
				$timestamp = $this->get_last_synchro_date_time( );
			} else {
				$timestamp = $since;
			}
			$relation_table = $relation[ 'name' ] . '_c';
			$field_name     = $this->get_relation_field_name( $relation[ 'name' ] );
			$sub_query      = 'SELECT ' . $field_name . ' FROM ' . $relation_table . ' WHERE date_modified >= "' . strftime('%Y-%m-%d %H:%M:%S', $timestamp) . '"';
			$query          = $this->module_name . '.id IN (' . $sub_query . ')';
		}
		return $this->get_sugar_ids( $query );
	}
	
	protected function get_sugar_ids( $query = '' ) {
		
		// OFFSET MANAGEMENT
		if ( $this->last_query != $query ) {
			$this->offset = 0;
		}
		$this->last_query = $query;
		
		// SOAP REQUEST
		if ($query) {
			$this->cli->notice( 'offset = ' . $this->offset . ' - query = ' . $query );
		}
		$entries = $this->sugar_connector->get_entry_list( $this->module_name, array( 'id' ), $this->offset, $this->paquet, $query );
		
		// ERROR MANAGEMENT
		if (
			! is_array( $entries ) ||
			! is_array( $entries[ 'data' ] ) || 
			( isset($entries['error'] ) && $entries['error']['number'] !== '0' )
		) {
			throw new Exception( 'Erreur du Sugar connecteur sur la liste des entrées du module ' . $this->module_name );
		}
		
		// TREATMENT
		$sugar_ids = array( );
		$this->cli->notice( 'get_entry_list = ' . count( $entries[ 'data' ] ) );
		foreach ( $entries[ 'data' ] as $entry ) {
			$sugar_ids[ ] = $entry[ 'id' ];
		}
		$this->cli->notice( '-- Checkpoint: ' . $this->offset );
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
		return self::get_valid_db_name( $relation_name . $this->module_name . $suffixe, TRUE );
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
}
?>