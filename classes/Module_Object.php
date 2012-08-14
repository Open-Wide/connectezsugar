<?php 

class Module_Object {
	
	private $module_name = '';
	private $sugar_id;
	private $schema;
	private $cli;
	private $ez_object_id;
	private $ez_object;
	private $sugar_connector;
	
	
	/**
	 * Constructor
	*/
	public function __construct($module_name, $sugar_id, $schema, $cli ) {
		$this->module_name     = $module_name;
		$this->sugar_id        = $sugar_id;
		$this->schema    = $schema;
		$this->cli             = $cli;
		$this->ez_object_id    = $this->get_ez_object_id( $this->schema->ez_class_identifier, $this->sugar_id );
		$this->ez_object       = eZContentObject::fetch( $this->ez_object_id );
		$this->sugar_connector = new SugarConnector();
	}

	public function __destruct() {
		unset( $this->sugar_connector, $this->schema, $this->ez_object );
		eZContentObject::clearCache();
	}
	
	
	/**
	 * Public
	*/
	public function import_relations() {
		foreach ($this->schema->get_relations() as $relation) {
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
	
	public function import_relation($relation) {
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
				throw new Exception( 'Erreur de eZ Publish, impossible d\'ajouter une relation entre ' . $this->schema->ez_class_identifier . '#' . $this->ez_object_id .' et ' . $relation[ 'related_class_identifier' ] . '#' . $related_ez_object_id );
			}
			$this->cli->notice( 'Add relation between ' . $this->module_name . '#'. $this->ez_object_id . ' and ' . $relation[ 'related_module_name' ] . '#' . $related_ez_object_id );
		}
		foreach ( $diff_related_ids[ 'to_remove' ] as $related_ez_object_id ) {
			if ( $this->ez_object->removeContentObjectRelation( $related_ez_object_id ) === FALSE ) {
				throw new Exception( 'Erreur de eZ Publish, impossible de supprimer une relation entre ' . $this->schema->ez_class_identifier . '#' . $this->ez_object_id .' et ' . $relation[ 'related_class_identifier' ] . '#' . $related_ez_object_id );
			}
			$this->cli->notice( 'Remove relation between ' . $this->module_name . '#'. $this->ez_object_id . ' and ' . $relation[ 'related_module_name' ] . '#' . $related_ez_object_id );
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
				throw new Exception( 'Erreur de eZ Publish, impossible de mettre à jour l\'attribut relation "' . $relation[ 'attribute_name' ] . '" de ' . $this->schema->ez_class_identifier . '#' . $this->ez_object_id );
			}
			$this->cli->notice( 'Attribut de ' . $this->schema->ez_class_identifier . '#' . $this->ez_object_id . ' mis à jour' );
        }
	}
	
	/**
	 * EXPORT SugarCRM
	*/
	
	/**
	*
	* Synchro Ez Publish vers SugarCRM
	*/
	public function export() {
		$this->export_data();
	}
	
	private function export_data() {
		$this->cli->notice('TODO export_data');
		$entry = array( );
        $dataMap = $this->ez_object->fetchDataMap( FALSE );
		foreach ( $this->schema->editable_attributes as $field ) {
			if ( isset( $dataMap[ $field ] ) ) {
				$entry[ $field ] = $dataMap[ $field ]->value( );
			}
		}
		$this->cli->notice( print_r( $entry ) );
		$this->cli->notice( 'TODO set_entry' );
		//$this->sugar_connector->set_entry( $this->module_name, $entry );
	}
}
?>