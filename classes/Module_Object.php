<?php 

class Module_Object {
	
	private $module_name = '';
	private $sugar_id;
	private $sugar_schema;
	private $ez_object_id;
	private $ez_object;
	private $sugar_connector;
	
	public function __construct($module_name, $sugar_id, $sugar_schema ) {
		$this->module_name     = $module_name;
		$this->sugar_id        = $sugar_id;
		$this->sugar_schema    = $sugar_schema;
		$this->ez_object_id    = $this->get_ez_object_id( $this->sugar_schema->ez_class_identifier, $this->sugar_id );
		$this->ez_object       = eZContentObject::fetch( $this->ez_object_id );
		$this->sugar_connector = new SugarConnector();
	}

	public function __destruct() {
		unset( $this->sugar_connector, $this->sugar_schema, $this->ez_object );
	}
	
	public function synchro() {
		$this->synchro_relations();
	}
	
	private function get_ez_object_id($ez_class_identifier, $sugar_id) {
		$remote_id = $ez_class_identifier . "_" . $sugar_id;
		$ez_object_id = owObjectsMaster::objectIDByRemoteID($remote_id);
		if ($ez_object_id === false) {
			throw new Exception( 'object_id introuvable pour remote_id=' . $remote_id);
		}
		return $ez_object_id;
	}
	
	private function synchro_relations() {
		foreach ($this->sugar_schema->get_relations() as $relation) {
			$this->synchro_relation($relation);
		}
	}
	
	private function synchro_relation($relation) {
		if ( $relation[ 'type' ] == 'relation' ) {
			$this->synchro_relation_common($relation);
		} else if ( $relation[ 'type' ] == 'attribute' ) {
			
		}
	}
	
	private function synchro_relation_common($relation) {
		$values = $this->sugar_connector->get_relationships($this->module_name, $this->sugar_id, $relation[ 'related_module_name' ]);
		if ( ! is_array( $values ) || ( isset($values['error'] ) && $values['error']['number'] !== '0' ) ) {
			throw new Exception( 'Erreur du Sugar connecteur sur la liste des relations entre ' . $this->module_name . '#' . $this->sugar_id .' et ' . $relation[ 'related_module_name' ] );
		}
		// @TODO: Diff 
		echo 'TODO: Diff relation' . PHP_EOL;
		foreach ( $values[ 'data' ] as $value ) {
			$related_ez_object_id = $this->get_ez_object_id($relation['related_class_identifier'], $value['id']);
			if ( $this->ez_object->addContentObjectRelation( $related_ez_object_id ) === FALSE ) {
				throw new Exception( 'Erreur de eZ Publish, impossible de setter une relation entre ' . $this->sugar_schema->ez_class_identifier . '#' . $this->ez_object_id .' et ' . $relation[ 'related_class_identifier' ] . '#' . $related_ez_object_id );
			}
		}
	}
	
	private function synchro_relation_attribute($relation) {
		// @TODO: Attribute relation 
		echo 'TODO: Relation attribute' . PHP_EOL;
	}
}
?>