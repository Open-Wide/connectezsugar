<?php 

include_once( 'extension/connectezsugar/classes/Module_Schema.php' );
include_once( 'extension/connectezsugar/classes/Module_Object_Accessor.php' );
include_once( 'extension/connectezsugar/classes/Module_Object.php' );

class Module extends Module_Object_Accessor {
	
	protected $module_name = '';
	protected $cli;
	const SET_DATE_SYNCHRO = false; // @TODO Mettre à false pour faire des tests
	
	public function __construct( $module_name, $cli ) {
		$this->module_name = $module_name;
		$this->cli         = $cli;
		parent::__construct( );
	}

	public function __destruct() {
		parent::__destruct( );
		unset( $this->cli );
	}
	
	// Import de toutes les relations, sans check sur la date de dernière modification
	public function import_module_relations_all( ) {
		
		$this->cli->warning( 'import_module_relations_all [memory=' . memory_get_usage_hr() . ']' );
		
		$schema = new Module_Schema( $this->module_name, $this->cli );
		$schema->load_relations( );
		while ( $sugar_ids = $this->get_sugar_ids( ) ) {
			foreach ( $sugar_ids as $sugar_id ) {
				try {
					$object = new Module_Object( $this->module_name, $sugar_id, $schema, $this->cli );
					$object->import_relations( );
					unset( $object );
				} catch( Exception $e ) {
					$this->cli->error( $e->getMessage( ) );
				}
			}
		}
	}
	
	// Import des relations depuis la date de dernière modification côté SugarCRM
	public function import_module_relations() {
		
		$this->cli->warning( 'import_module_relations [memory=' . memory_get_usage_hr() . ']' );
		
		$schema = new Module_Schema( $this->module_name, $this->cli );
		$schema->load_relations( );
		
		foreach ( $schema->get_relations() as $relation ) {
			while ( $sugar_ids = $this->get_sugar_ids_from_updated_relation( $relation/*, mktime(16, 58, 16, 8, 1, 2012)*/ ) ) {
				foreach ( $sugar_ids as $sugar_id ) {
					try {
						$object = new Module_Object( $this->module_name, $sugar_id, $schema, $this->cli );
						$object->import_relation( $relation );
						unset( $object );
					} catch( Exception $e ) {
						$this->cli->error( $e->getMessage( ) );
					}
				}
			}
		}
		if ( self::SET_DATE_SYNCHRO ) {
			$this->set_last_synchro_date_time( 'import_module_relations' );
		}
	}
	
	/*
	 * EXPORT vers SugarCRM
	 */
	
	protected function get_ez_remote_ids_since_last_sync( $mode ) {
		return $this->get_ez_remote_ids( $this->get_last_synchro_date_time( $mode ) );
	}
	
	// Liste des ID SugarCRM dont l'objet eZ a été modifié => à envoyer à Sugar
	protected function get_ez_remote_ids( $timestamp = false ) {
		
		$ez_remote_ids 	  = array();
		$ini 		   	  = eZIni::instance( 'otcp.ini' );
		$class_identifier = Module_Schema::get_ez_class_identifier( $this->module_name );
		
		// Hack pour otcp_accommodation : la valeur de crmDirectories vaut acco au lieu de accomodation ?!?
		if ( $this->module_name == 'otcp_accommodation') {
			$identifier = 'acco';
		} else {
			$identifier = $class_identifier;
		}
		
		$params = array(
			'parent_node_id'     => $ini->variable( 'crmDirectories', $identifier ),
			'class_filter_type'  => 'include',
			'class_filter_array' => array( $class_identifier ),
		);
		if ($timestamp !== false) {
			$params[ 'attribute_filter' ] = array(
				array( 'modified', '>=', $timestamp )
			);
		}
		$nodes = eZFunctionHandler::execute( 'content', 'list', $params );
		foreach ($nodes as $node) {
			$remote_id = $node->object()->remoteID( );
			// On a un ID du genre "room_e7bd0519-2802-69ce-1f2f-501941f1e8e0", on supprime le préfixe "room_" pour ne conserver que l'ID
			list($ez_class_identifier, $sugar_id) = explode('_', $remote_id, 2);
			$ez_remote_ids[ ] = $sugar_id;
		}
		$this->cli->warning( 'Nb d\'objets ' . $class_identifier . ' modifiés dans eZ depuis le ' . strftime('%Y-%m-%d %H:%M:%S', $timestamp) . ' : ' . count($ez_remote_ids) );
		
		return $ez_remote_ids;
	}
	
	// Export des objets eZ vers SugarCRM (valeurs des champs éditables)
	public function export_module_objects() {
		
		$this->cli->warning( 'export_module_objects [memory=' . memory_get_usage_hr() . ']' );
		
		$schema = new Module_Schema( $this->module_name, $this->cli );
		$schema->load_editable_attributes( );
		$ez_remote_ids = $this->get_ez_remote_ids_since_last_sync( 'export_module' );
		
		foreach ($ez_remote_ids as $ez_remote_id) {
			try {
				$object = new Module_Object( $this->module_name, $ez_remote_id, $schema, $this->cli );
				$object->export( );
				unset( $object );
			} catch( Exception $e ) {
				$this->cli->error( $e->getMessage( ) );
			}
		}
		if ( self::SET_DATE_SYNCHRO ) {
			$this->set_last_synchro_date_time( 'export_module' );
		}
	}
	
	/*
	 * IMPORT depuis SugarCRM
	 */
	
	public function import_module_objects() {
		
		//@TODO Création classe eZ si absente (import initial)
		
		$this->cli->warning( 'import_module_objects [memory=' . memory_get_usage_hr() . ']' );

		$schema = new Module_Schema( $this->module_name, $this->cli );
		
		$this->import_module_objects_update( $schema );
		$this->import_module_objects_delete( $schema );
		
		if ( self::SET_DATE_SYNCHRO ) {
			$this->set_last_synchro_date_time( 'import_module' );
		}
	}
	
	// Ajout / Mises à jour des objets modifiés/ajoutés dans Sugar
	private function import_module_objects_update( $schema ) {
		$sugar_ids 		 = $this->get_sugar_ids_since_last_sync( 'import_module' );
		$select_fields   = $this->load_include_fields( );
		$select_fields[] = 'deleted';
		
		foreach ( $sugar_ids as $sugar_id ) {
			$sugardata = $this->sugar_connector->get_entry( $this->module_name, $sugar_id, $select_fields );
			if (isset ( $sugardata[ 'data' ] ) ) {
				try {
					$object = new Module_Object( $this->module_name, $sugar_id, $schema, $this->cli );
					$object->update( $sugardata );
					unset( $object );
				} catch ( Exception $e) {
					$this->cli->error( $e->getMessage( ) );
				}
			} else {
				$this->cli->error('Aucune donnée récupérée par get_entry() pour ID=' . $sugar_id);
			}
		}
	}
	
	// Suppression des objets supprimés dans Sugar
	private function import_module_objects_delete( $schema ) {
		$sugar_ids_deleted = $this->get_sugar_ids_since_last_sync( 'import_module', true );
		if ( count ( $sugar_ids_deleted ) ) {
			foreach ( $sugar_ids_deleted as $sugar_id_deleted ) {
				try {
					$object = new Module_Object( $this->module_name, $sugar_id_deleted, $schema, $this->cli );
					$object->delete( );
					unset( $object );
				} catch ( Exception $e) {
					$this->cli->error( $e->getMessage( ) );
				}
			}
		}
	}
	
	// Chargement de la liste des champs à synchroniser SugarCRM vers eZ
	protected function load_include_fields( ) {
		$ini = eZIni::instance( 'mappingezsugar.ini' );
		$include_fields = array( );
		if ( $ini->hasVariable( $this->module_name, 'include_fields' ) ) {
			foreach( $ini->variable( $this->module_name, 'include_fields' ) as $include_field ) {
				$include_fields[ ] = $include_field;
			}
		}
		return $include_fields;
	}
	
	protected function get_sugar_ids_since_last_sync( $mode, $deleted = false ) {
		return $this->get_sugar_ids_from_updated_attributes( $this->get_last_synchro_date_time( $mode ), $deleted );
	}
	
	// Liste des IDs SugarCRM dont l'objet a été modifié depuis la dernière synchro
	protected function get_sugar_ids_from_updated_attributes( $timestamp, $deleted ) {
		$sugar_ids 	   = array( ); // Tableau qui contiendra les ID SugarCRM modifiés, donc à récupérer côté eZ
		$select_fields = array( 'id' );
		$offset 	   = 0;
		$max_results   = 99999;
		$query 		   = $this->module_name . '.date_modified >= "' . strftime( '%Y-%m-%d %H:%M:%S', $timestamp ) . '"';
		$order_by	   = '';
		$sugardata     = $this->sugar_connector->get_entry_list( $this->module_name, $select_fields, $offset, $max_results, $query, $order_by, $deleted );
		
		if ( isset( $sugardata['data'] ) ) {
			foreach ( $sugardata['data'] as $data ) {
				$sugar_ids[ ] = $data['id'];
			}
		}
		
		$this->cli->warning( 'Nb d\'objets ' . $this->module_name . ' ' . ( $deleted ? 'supprimés' : 'modifiés' ) . ' dans SugarCRM depuis le ' . strftime('%Y-%m-%d %H:%M:%S', $timestamp) . ' : ' . count($sugar_ids) );
		
		return $sugar_ids;
	}
}
?>