<?php 

include_once( 'extension/connectezsugar/classes/Module_Schema.php' );
include_once( 'extension/connectezsugar/classes/Module_Object_Accessor.php' );
include_once( 'extension/connectezsugar/classes/Module_Object.php' );

class Module extends Module_Object_Accessor {
	
	protected $module_name = '';
	protected $cli;
	public $save_date_synchro;
	public $simulation;
	
	public function __construct( $module_name, $cli, $simulation ) {
		$this->module_name       = $module_name;
		$this->cli               = $cli;
		$this->simulation        = ($simulation != 'sync'); // Si check ou simul => mode simulation
		$this->warning( '[' . $this->module_name . '] - mode Simulation : ' . ($this->simulation ? 'ON' : 'OFF') );
		
		parent::__construct( );
		
		if ( $simulation == 'check' ) {
			$this->check( );
		} elseif ($simulation == 'check_relations' ) {
			$this->check_relations();
		}
	}

	public function __destruct() {
		parent::__destruct( );
		unset( $this->cli );
	}
	
	// Import de toutes les relations, sans check sur la date de dernière modification
	public function import_module_relations_all( ) {
		
		$this->warning( 'import_module_relations_all [memory=' . memory_get_usage_hr() . ']' );
		
		$schema = new Module_Schema( $this->module_name, $this->cli );
		$schema->load_relations( );
		
		while ( $remote_ids = $this->get_remote_ids( $relation ) ) {
			$i = 1;
			$count_remote_ids = count( $remote_ids );
			foreach ( $remote_ids as $remote_id ) {
				try {
					$object = new Module_Object( $this->module_name, $remote_id, $schema, $this->cli, $this->simulation, ( $i++ . '/' . $count_remote_ids ) );
					$object->import_relations( );
					unset( $object );
				} catch( Exception $e ) {
					$this->error( $e->getMessage( ), $e->getCode() );
				}
			}
		}
		$this->logs[ ] = '------------------';
		eZDebug::writeDebug( implode( "\n", $this->logs ) );
	}
	
	// Import des relations depuis la date de dernière modification côté CRM
	public function import_module_relations() {
		
		$this->warning( 'import_module_relations [memory=' . memory_get_usage_hr() . ']' );
		
		$schema = new Module_Schema( $this->module_name, $this->cli );
		$schema->load_relations( );
		
		$timestamp = self::get_last_synchro_date_time( 'import_module_relations' );
		
		foreach ( $schema->get_relations() as $relation ) {
			$this->warning( 'Relations ' . $this->module_name . ' / ' . $relation[ 'related_module_name' ] );
			while ( $remote_ids = $this->get_remote_ids( $relation, $timestamp ) ) {
				$i = 1;
				$count_remote_ids = count( $remote_ids );
				foreach ( $remote_ids as $remote_id ) {
					try {
						$object = new Module_Object( $this->module_name, $remote_id, $schema, $this->cli, $this->simulation, ( $i++ . '/' . $count_remote_ids ) );
						$object->import_relation( $relation );
						unset( $object );
					} catch( Exception $e ) {
						$this->error( $e->getMessage( ), $e->getCode() );
					}
				}
			}
		}
		if ( !$this->simulation ) {
			$this->set_last_synchro_date_time( 'import_module_relations' );
		}
		$this->logs[ ] = '------------------';
		eZDebug::writeDebug( implode( "\n", $this->logs ) );
	}
	
	// Check de la cohérence des data (différences entre eZ et le CRM)
	public function check( ) {
		
		$this->notice( 'Check de la cohérence des bases eZ et le CRM ... work in progress ...' );
		
		$remote_ids    = array( ); // Tableau qui contiendra les ID CRM modifiés, donc à récupérer côté eZ
		$select_fields = array( 'id' );
		$offset 	   = 0;
		$max_results   = 99999;
		$query 		   = '';
		$order_by	   = '';
		$deleted	   = false;
		
		$nb_par_lot = 250;
		$remotedata = array('data' => array());
		for ( $offset = 0; $offset < $max_results; $offset += $nb_par_lot) {
			$data = $this->connector->get_entry_list( $this->module_name, $select_fields, $offset, $nb_par_lot, $query, $order_by, $deleted );
			if ( is_array( $data ) && is_array( $data[ 'data' ] ) && count( $data[ 'data' ] ) > 0 ) {
				$remotedata['data'] = array_merge( $remotedata['data'], $data['data'] );
				$this->notice('[OFFSET ' . $offset . '] get_entry_list [memory=' . memory_get_usage_hr() . ']');
				unset($data);
			} else {
				if ( !is_array( $data ) || !is_array( $data['data'] ) ) {
					$this->warning(print_r($data));
					$this->error( 'arret de la fonction check() avec offset = ' . $offset . ' [memory=' . memory_get_usage_hr() . ']');
					return false;
				}
				$this->notice('get_entry_list terminé');
				// Plus de données à renvoyer, on sort de la boucle
				break;
			}
		}
		
		$count_remote_data = 0;
		if (is_array($remotedata) && isset($remotedata[ 'data' ])) {
			$count_remote_data = count( $remotedata[ 'data' ] );
		} else {
			$this->warning(print_r($remotedata));
		}
		
		if ( $count_remote_data == 0) {
			$this->error( 'arret de la fonction check() => nb objets = 0  avec max_results = ' . $max_results );
			return false;
		}
		
		$this->warning( 'Nb d\'objets ' . $this->module_name . ' dans le CRM : ' . $count_remote_data );
		
		$ez_remote_ids 	  = array();
		$ini 		   	  = eZIni::instance( 'otcp.ini' );
		$class_identifier = Module_Schema::get_ez_class_identifier( $this->module_name );
		
		// Hack pour otcp_accommodation : la valeur de crmDirectories vaut acco au lieu de accomodation ?!?
		if ( $this->module_name == 'otcp_accommodation' ) {
			$identifier = 'acco';
		} else {
			$identifier = $class_identifier;
		}
		$params = array(
			'parent_node_id'     => $ini->variable( 'crmDirectories', $identifier ),
			'class_filter_type'  => 'include',
			'class_filter_array' => array( $class_identifier ),
		);
		$nodes = eZFunctionHandler::execute( 'content', 'list', $params );
		
		$this->warning( 'Nb d\'objets ' . $this->module_name . ' dans eZPublish : ' . count( $nodes ) );
		
		if ( count( $nodes ) != $count_remote_data ) {
			
			$this->notice( 'Chargement des nodes eZ ...' );
			foreach ( $nodes as $node ) {
				$remote_id = $node->object()->remoteID( );
				// On a un ID du genre "room_e7bd0519-2802-69ce-1f2f-501941f1e8e0", on supprime le préfixe "room_" pour ne conserver que l'ID
				list($ez_class_identifier, $remote_id) = explode('_', $remote_id, 2);
				$ez_remote_ids[ ] = $remote_id;
			}
			$this->notice( 'Chargement des remote_ids CRM ...' );
			foreach ( $remotedata[ 'data' ] as $data ) {
				$remote_ids[ ] = $data[ 'id' ];
			}
			
			$diff = array_diff( $remote_ids, $ez_remote_ids );
			if ( count( $diff ) ) {
				$this->warning( count( $diff ) . ' éléments dans le CRM absents dans eZ' );
				$cpt = 1;
				foreach ( $diff as $diff_remote_id ) {
					$select_fields = array( 'name', 'deleted' );
					$remotedata = $this->connector->get_entry( $this->module_name, $diff_remote_id, $select_fields );
					if ( isset( $remotedata['data'] ) && isset( $remotedata['data'][0] ) ) {
						$this->notice( '[' . $cpt . '] ' . $diff_remote_id . ' - ' . $remotedata['data'][0]['value'] );
					}
					$cpt++;
				}
			}
			
			// Au cas où, check de l'existence d'objets dans eZ mais pas dans le CRM
			$diff = array_diff( $ez_remote_ids, $remote_ids );
			if ( count( $diff ) ) {
				$this->warning( count( $diff ) . ' éléments dans eZ absents dans le CRM !!' );
				$cpt = 1;
				foreach ( $diff as $diff_remote_id ) {
					$object_found = eZContentObject::fetchByRemoteID( $class_identifier.'_'.$diff_remote_id );
					if ( $object_found ) {
						$this->notice( '[' . $cpt . '] ' . $diff_remote_id . ' | Suppression de ' . $object_found->mainNodeID() . ' - ' . $object_found->Name );
						$object_found->purge();
					} else {
						$this->notice( '[' . $cpt . '] ' . $diff_remote_id . ' Objet introuvable dans le CRM');
					}
					$cpt++;
				}
			}
		}
		
	}
	
	public function check_relations_module( $relation, $schema ) {
		$this->warning( 'Relations ' . $this->module_name . ' / ' . $relation[ 'related_module_name' ] . ' type ' . $relation[ 'type' ] . ' work in progress ...');
		
		$i = 1;
		while ( $remote_ids = $this->get_remote_ids( $relation, '' ) ) {
			$count_remote_ids = count( $remote_ids );
			$to_add = array();
			$to_remove = array();
			foreach ( $remote_ids as $remote_id ) {
				try {
					$object = new Module_Object( $this->module_name, $remote_id, $schema, $this->cli, $this->simulation, ( $i++ . '/' . $count_remote_ids ) );
					$diff_relations = $object->check_relation( $relation );
					if ($diff_relations !== false) {
						$to_add = array_merge($to_add, $diff_relations['to_add']);
						$to_remove = array_merge($to_remove, $diff_relations['to_remove']);
					}
					unset( $object );
				} catch( Exception $e ) {
					$this->error( $e->getMessage( ), $e->getCode() );
				}
			}
		}
		$this->warning( count($to_add) . ' items à ajouter ');
		foreach ($to_add as $k => $item) {
			$this->notice('['.($k+1).'] #' . $item['related_object_id'] . ' ' . $item['related_object_name'] . ' (' . $item['related_module_name'] . ') <=> ' . '#' . $item['object_id'] . ' ' . $item['object_name'] . ' (' . $item['module_name'] . ') - ' . $item['object_remote_id']);
		}
		
		$this->warning( count($to_remove) . ' items à supprimer ');
		foreach ($to_remove as $k => $item) {
			$this->notice('['.($k+1).'] #' . $item['related_object_id'] . ' ' . $item['related_object_name'] . ' (' . $item['related_module_name'] . ') <=> ' . '#' . $item['object_id'] . ' ' . $item['object_name'] . ' (' . $item['module_name'] . ') - ' . $item['object_remote_id']);
		}
	}
	
	// Check de la cohérence des data (différences entre eZ et le CRM) au niveau des relations
	public function check_relations( ) {
		
		$schema = new Module_Schema( $this->module_name, $this->cli );
		$schema->load_relations( );
		foreach ( $schema->get_relations() as $relation ) {
			if ($relation[ 'type' ] == 'relation') {
				$this->check_relations_module( $relation, $schema );
			} else {
				$this->warning('Relations ' . $this->module_name . ' / ' . $relation[ 'related_module_name' ] . ' - pas de check relation : type ' . $relation[ 'type' ]);
			}
		}
		
	}
	
	/*
	 * EXPORT vers le CRM
	 */
	
	protected function get_ez_remote_ids_since_last_sync( $mode ) {
		return $this->get_ez_remote_ids( $this->get_last_synchro_date_time( $mode ) );
	}
	
	// Liste des ID CRM dont l'objet eZ a été modifié => à envoyer au CRM
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
			list($ez_class_identifier, $remote_id) = explode('_', $remote_id, 2);
			$ez_remote_ids[ ] = $remote_id;
		}
		$this->warning( 'Nb d\'objets ' . $class_identifier . ' modifiés dans eZ depuis le ' . strftime('%Y-%m-%d %H:%M:%S', $timestamp) . ' : ' . count($ez_remote_ids) );
		
		return $ez_remote_ids;
	}
	
	// Export des objets eZ vers le CRM (valeurs des champs éditables)
	public function export_module_objects() {
		
		$this->warning( 'export_module_objects [memory=' . memory_get_usage_hr() . ']' );
		
		$schema = new Module_Schema( $this->module_name, $this->cli );
		$schema->load_editable_attributes( );
		// Liste des remote_ids dont l'objet eZ a été modifié depuis le dernier import des données du CRM
		// Pas depuis le dernier export, sinon on exporterait toujours toute la liste des objets importées lors de la précédente synchro, l'export étant lancé à chaque fois juste avant l'import
		$ez_remote_ids = $this->get_ez_remote_ids_since_last_sync( 'import_module' );
		
		$i = 1;
		$count_ez_remote_ids = count( $ez_remote_ids );
		foreach ($ez_remote_ids as $ez_remote_id) {
			try {
				$object = new Module_Object( $this->module_name, $ez_remote_id, $schema, $this->cli, $this->simulation, ( $i++ . '/' . $count_ez_remote_ids ) );
				$object->export( );
				unset( $object );
			} catch( Exception $e ) {
				$this->error( $e->getMessage( ), $e->getCode() );
			}
		}
		if ( !$this->simulation ) {
			$this->set_last_synchro_date_time( 'export_module' );
		}
		$this->logs[ ] = '------------------';
		eZDebug::writeDebug( implode( "\n", $this->logs ) );
	}
	
	/*
	 * IMPORT depuis le CRM
	 */
	
	public function import_module_objects() {
		
		//@TODO Création classe eZ si absente (import initial)
		
		$this->warning( 'import_module_objects [memory=' . memory_get_usage_hr() . ']' );

		$schema = new Module_Schema( $this->module_name, $this->cli );
		
		$this->import_module_objects_update( $schema );
		$this->import_module_objects_delete( $schema );
		
		if ( !$this->simulation ) {
			$this->set_last_synchro_date_time( 'import_module' );
		}
		$this->logs[ ] = '------------------';
		eZDebug::writeDebug( implode( "\n", $this->logs ) );
	}
	
	// Ajout / Mises à jour des objets modifiés/ajoutés dans le CRM
	private function import_module_objects_update( $schema ) {
		$remote_ids  	 = $this->get_remote_ids_since_last_sync( 'import_module' );
		$select_fields   = self::load_include_fields( $this->module_name );
		$select_fields[] = 'deleted';
		
		$i = 1;
		$count_remote_ids = count( $remote_ids );
		foreach ( $remote_ids as $remote_id ) {
			$remotedata = $this->connector->get_entry( $this->module_name, $remote_id, $select_fields );
			if (isset ( $remotedata[ 'data' ] ) ) {
				try {
					$object = new Module_Object( $this->module_name, $remote_id, $schema, $this->cli, $this->simulation, ( $i++ . '/' . $count_remote_ids ) );
					$object->update( $remotedata );
					$this->logs = array_merge( $this->logs, $object->logs );
					unset( $object, $remotedata );
				} catch ( Exception $e) {
					$this->error( $e->getMessage( ), $e->getCode() );
				}
			} else {
				$this->error('Aucune donnée récupérée par get_entry() pour ID=' . $remote_id);
			}
		}
	}
	
	// Suppression des objets supprimés dans le CRM
	private function import_module_objects_delete( $schema ) {
		$remote_ids_deleted = $this->get_remote_ids_since_last_sync( 'import_module', true );
		if ( count ( $remote_ids_deleted ) ) {
			$i = 1;
			$count_remote_ids = count( $remote_ids_deleted );
			foreach ( $remote_ids_deleted as $remote_id_deleted ) {
				try {
					$object = new Module_Object( $this->module_name, $remote_id_deleted, $schema, $this->cli, $this->simulation, ( $i++ . '/' . $count_remote_ids ) );
					$this->logs = array_merge( $this->logs, $object->logs );
					$object->delete( );
					unset( $object );
				} catch ( Exception $e) {
					$this->error( $e->getMessage( ), $e->getCode() );
				}
			}
		}
	}
	
	// Chargement de la liste des champs à synchroniser CRM vers eZ
	public static function load_include_fields( $module_name ) {
		$ini = eZIni::instance( 'mappingezsugar.ini' );
		$include_fields = array( );
		if ( $ini->hasVariable( $module_name, 'include_fields' ) ) {
			foreach( $ini->variable( $module_name, 'include_fields' ) as $include_field ) {
				$include_fields[ ] = $include_field;
			}
		}
		return $include_fields;
	}
	
	protected function get_remote_ids_since_last_sync( $mode, $deleted = false ) {
		return $this->get_remote_ids_from_updated_attributes( $this->get_last_synchro_date_time( $mode ), $deleted );
	}
	
	// Liste des IDs CRM dont l'objet a été modifié depuis la dernière synchro
	protected function get_remote_ids_from_updated_attributes( $timestamp, $deleted ) {
		$remote_ids	   = array( ); // Tableau qui contiendra les ID CRM modifiés, donc à récupérer côté eZ
		$select_fields = array( 'id' );
		$offset 	   = 0;
		$max_results   = 99999;
		$query 		   = $this->module_name . '.date_modified >= "' . strftime( '%Y-%m-%d %H:%M:%S', $timestamp ) . '"';
		$order_by	   = '';
		
		$nb_par_lot = 250;
		$remotedata = array('data' => array());
		for ( $offset = 0; $offset < $max_results; $offset += $nb_par_lot) {
			$data = $this->connector->get_entry_list( $this->module_name, $select_fields, $offset, $nb_par_lot, $query, $order_by, $deleted );
			
			if ( is_array( $data ) && is_array( $data[ 'data' ] ) && count( $data[ 'data' ] ) > 0 ) {
				$this->notice('[OFFSET ' . $offset . '] get_entry_list [memory=' . memory_get_usage_hr() . ']');
				$remotedata['data'] = array_merge( $remotedata['data'], $data['data'] );
			} else {
				if ( !is_array( $data ) || !is_array( $data['data'] ) ) {
					$this->error( 'arret de la fonction get_remote_ids_from_updated_attributes() avec offset = ' . $offset . ' [memory=' . memory_get_usage_hr() . ']');
					return false;
				}
				$this->notice('get_entry_list terminé');
				// Plus de données à renvoyer, on sort de la boucle
				break;
			}
			unset($data);
		}
		
		if ( isset( $remotedata['data'] ) ) {
			foreach ( $remotedata['data'] as $data ) {
				$remote_ids[ ] = $data['id'];
			}
		}
		
		$this->warning( 'Nb d\'objets ' . $this->module_name . ' ' . ( $deleted ? 'supprimés' : 'modifiés' ) . ' dans le CRM depuis le ' . strftime('%Y-%m-%d %H:%M:%S', $timestamp) . ' : ' . count($remote_ids) );
		
		return $remote_ids;
	}
}
?>