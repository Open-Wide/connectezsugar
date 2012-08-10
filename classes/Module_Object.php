<?php 

class Module_Object {
	
	private $module_name = '';
	private $sugar_id;
	private $sugar_schema;
	private $cli;
	private $ez_object_id;
	private $ez_object;
	private $sugar_connector;
	
	
	/**
	 * Constructor
	*/
	public function __construct($module_name, $sugar_id, $sugar_schema, $cli ) {
		$this->module_name     = $module_name;
		$this->sugar_id        = $sugar_id;
		$this->sugar_schema    = $sugar_schema;
		$this->cli             = $cli;
		$this->ez_object_id    = $this->get_ez_object_id( $this->sugar_schema->ez_class_identifier, $this->sugar_id );
		$this->ez_object       = eZContentObject::fetch( $this->ez_object_id );
		$this->sugar_connector = new SugarConnector();
	}

	public function __destruct() {
		unset( $this->sugar_connector, $this->sugar_schema, $this->ez_object );
		eZContentObject::clearCache();
	}
	
	
	/**
	 * Public
	*/
	public function import_relations() {
		foreach ($this->sugar_schema->get_relations() as $relation) {
			$this->import_relation($relation);
		}
	}
	
	
	/**
	 * Private Import
	*/
	private function get_ez_object_id($ez_class_identifier, $sugar_id) {
		$remote_id = $ez_class_identifier . "_" . $sugar_id;
		$ez_object_id = owObjectsMaster::objectIDByRemoteID($remote_id);
		if ($ez_object_id === false) {
			throw new Exception( 'object_id introuvable pour remote_id=' . $remote_id);
		}
		return $ez_object_id;
	}
	
	private function import_relation($relation) {
		if ( $relation[ 'type' ] == 'relation' ) {
			$this->import_relation_common($relation);
		} else if ( $relation[ 'type' ] == 'attribute' ) {
			$this->import_relation_attribute($relation);
		}
	}
	
	private function import_relation_common($relation) {
		$diff_related_ids = $this->diff_relations_common( $relation );
		foreach ( $diff_related_ids[ 'to_add' ] as $related_ez_object_id ) {
			if ( $this->ez_object->addContentObjectRelation( $related_ez_object_id ) === FALSE ) {
				throw new Exception( 'Erreur de eZ Publish, impossible d\'ajouter une relation entre ' . $this->sugar_schema->ez_class_identifier . '#' . $this->ez_object_id .' et ' . $relation[ 'related_class_identifier' ] . '#' . $related_ez_object_id );
			}
			$this->cli( 'Add relation between ' . $this->ez_object->attribute( 'name' ) . ' and ' . $related_ez_object_id );
		}
		foreach ( $diff_related_ids[ 'to_remove' ] as $related_ez_object_id ) {
			if ( $this->ez_object->removeContentObjectRelation( $related_ez_object_id ) === FALSE ) {
				throw new Exception( 'Erreur de eZ Publish, impossible de supprimer une relation entre ' . $this->sugar_schema->ez_class_identifier . '#' . $this->ez_object_id .' et ' . $relation[ 'related_class_identifier' ] . '#' . $related_ez_object_id );
			}
			$this->cli( 'Remove relation between ' . $this->ez_object->attribute( 'name' ) . ' and ' . $related_ez_object_id );
		}
	}
	
	private function diff_relations_common( $relation ) {
		$new_related_ez_object_ids     = $this->get_related_ez_object_ids_by_sugar( $relation );
		$current_related_ez_object_ids = $this->get_related_ez_object_ids_by_ez( $relation );
		return array(
			'to_add'    => array_diff( $new_related_ez_object_ids    , $current_related_ez_object_ids ),
			'to_remove' => array_diff( $current_related_ez_object_ids, $new_related_ez_object_ids     ),
		);
	}
	
	private function get_related_ez_object_ids_by_sugar( $relation ) {
		$related_ez_object_ids = array( );
		try {
			$values = $this->sugar_connector->get_relationships($this->module_name, $this->sugar_id, $relation[ 'related_module_name' ]);
		} catch ( Exception $e ) {
			throw new Exception( 'Exception du Sugar connecteur sur la liste des relations entre ' . $this->module_name . '#' . $this->sugar_id .' et ' . $relation[ 'related_module_name' ] );
		}
		if ( ! is_array( $values ) || ( isset($values['error'] ) && $values['error']['number'] !== '0' ) ) {
			throw new Exception( 'Erreur du Sugar connecteur sur la liste des relations entre ' . $this->module_name . '#' . $this->sugar_id .' et ' . $relation[ 'related_module_name' ] );
		}
		foreach ( $values[ 'data' ] as $value ) {
			$related_ez_object_ids[ ] = $this->get_ez_object_id($relation['related_class_identifier'], $value['id']);
		}
		return $related_ez_object_ids;
	}
	
	private function get_related_ez_object_ids_by_ez( $relation ) {
		// Loic tu vas morfler si ça marche pas !!
		$related_ez_object_ids = array( );
		$params = array(
			'object_id'            => $this->ez_object_id,
		    'load_data_map'        => false,
		);
		if ( isset( $relation[ 'attribute_identifier' ] ) ) {
			$params[ 'attribute_identifier' ] = $relation[ 'attribute_identifier' ];
		}
		$related_objects = eZFunctionHandler::execute('content', 'related_objects', $params);
		foreach ($related_objects as $related_object) {
			if ($related_object->ClassIdentifier == $relation['related_class_identifier']) {
				$related_ez_object_ids[ ] = $related_object->ID;
			}
		}
		return $related_ez_object_ids;
	}
	
	private function import_relation_attribute($relation) {
		if ( $relation[ 'attribute_type' ] == 'list' ) {
			$this->import_relation_attribute_list($relation);
		} else {
			$this->import_relation_attribute_one($relation);
		}
	}
	
	private function import_relation_attribute_list($relation) {
		$related_ez_object_ids = $this->get_related_ez_object_ids_by_sugar( $relation );
		$this->update_ez_attribute_value( $relation[ 'attribute_name' ], implode( '-', $related_ez_object_ids ) );
	}
	
	private function import_relation_attribute_one($relation) {
		$related_ez_object_ids = $this->get_related_ez_object_ids_by_sugar( $relation );
		if ( count( $related_ez_object_ids ) > 1 ) {
			$this->cli->warning( 'Warning on va perdre des données de relation, many-many to one-many' );
		}
		if ( count( $related_ez_object_ids ) == 1 ) {
			$attribute_value = $related_ez_object_ids[ 0 ];
		} else {
			// @TODO: Ne réinitialise pas la relation, trouver comment faire
			$attribute_value = '';
		}
		$this->update_ez_attribute_value( $relation[ 'attribute_name' ], $attribute_value );
	}
	
	private function update_ez_attribute_value( $attribute_name, $attribute_value ) {
        $dataMap = $this->ez_object->fetchDataMap( FALSE );
        if ( isset( $dataMap[ $attribute_name ] ) ) {
           	$current_attribute_value = $dataMap[ $attribute_name ]->toString( );
        }
        if ( ! isset( $current_attribute_value ) || $current_attribute_value != $attribute_value ) {
			$paramsUpdate = array( );
			$paramsUpdate['attributes'] = array(
				$attribute_name => $attribute_value,
			);
			$return = eZContentFunctions::updateAndPublishObject( $this->ez_object, $paramsUpdate );
			if ( ! $return ) {
				throw new Exception( 'Erreur de eZ Publish, impossible de mettre à jour l\'attribut relation "' . $relation[ 'attribute_name' ] . '" de ' . $this->sugar_schema->ez_class_identifier . '#' . $this->ez_object_id );
			}
			$this->cli->notice( 'Attribut de ' . $this->sugar_schema->ez_class_identifier . '#' . $this->ez_object_id . ' mis à jour !?' );
        }
	}
	
	/**
	 * EXPORT SugarCRM
	*/
	public function export_relations() {
		foreach ($this->sugar_schema->get_relations() as $relation) {
			$this->export_relation($relation);
		}
	}
	
	private function export_relation() {
		if ( $relation[ 'type' ] == 'relation' ) {
			$this->export_relation_common($relation);
		} else if ( $relation[ 'type' ] == 'attribute' ) {
			
		}
	}
	
	
	/**
	*
	* Synchro Ez Publish vers SugarCRM
	*/
	public function export() {
		//$ez_object_ids = $this->get_ez_object_ids_to_send();
		
		// On souhaite récupérer la liste des champs utiles de l'objet pour envoi à SugarCRM
		
	}
	
	private function get_ez_object_ids_to_send() {
		$class_name = $this->sugar_schema->get_ez_class_name($this->module_name);
		$class_identifier = $this->sugar_schema->get_ez_class_identifier($this->module_name);

		//$entry_list_ids = $this->sugar_connector->getSugarModuleIdListFromLastSynchro();
		//@TODO : Pour ne pas renvoyer les objets eZ déjà mis à jour par l'import depuis Sugar, récupérer la liste des ID d'objets déjà à jour 
		$entry_list_ids = array();
		
		$ini = eZIni::instance( 'otcp.ini' );
		
		// On remplit un tableau avec les listes d'identifiants SugarCRM pour faciliter la comparaison avec les ID externes eZ modifiés
		$ez_init_ids = array();
		foreach ($entry_list_ids as $entry) {
			$id_sugar = $class_identifier . '_' . $entry['id']; // Ex : "visit_ea7548f0-1e5e-e4ba-0cfc-501941e09129"
			if (!in_array($id_sugar, $ez_init_ids)) {
				$ez_init_ids[] = $id_sugar;
			}
		}
		
		// Liste des nodes eZ modifiées depuis la dernière synchro
		$nodes = eZFunctionHandler::execute(
			'content',
			'list',
			array(
				'parent_node_id'     => $ini->variable( 'crmDirectories', $class_identifier ),
				'class_filter_type'  => 'include',
				'class_filter_array' => array($class_identifier),
				'attribute_filter'   => array(
					array('modified', '>=', $this->get_last_synchro_date_time())
				),
			)
		);
		$ez_remote_ids_to_sync = array(); // Tableau qui contiendra les ID SugarCRM dont l'objet eZ a été modifié => à envoyer à Sugar
		foreach ($nodes as $node) {
			$remote_id = $node->object()->remoteID();
			if (!in_array($remote_id, $ez_init_ids)) {
				// Si l'ID ne figure pas dans le tableau des ID déjà synchronisés Sugar => eZ
				$ez_remote_ids_to_sync[] = $remote_id;
			}
		}
		print_r($ez_init_ids);
		print_r($ez_remote_ids_to_sync);
		// ->ContentObjectID
		//->ContentObject->ContentObjectAttributes[5]['fre-FR'][0]
	}
	
	private function get_last_synchro_date_time() {
		$inisynchro = eZINI::instance('synchro.ini');
		$date = strtotime($inisynchro->variable('Synchro','lastSynchroDatetime'));
		$date = mktime(0, 0, 0, 8, 9, 2012); // Pour le test
		return $date;
	}
	
	private function export_object($object_id) {
		
	}
}
?>