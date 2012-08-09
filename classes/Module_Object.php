<?php 

class Module_Object {
	
	private $module_name = '';
	private $sugar_id;
	private $sugar_schema;
	private $ez_object_id;
	private $sugar_connector;
	
	public function __construct($module_name, $sugar_id, $sugar_schema ) {
		$this->module_name     = $module_name;
		$this->sugar_id        = $sugar_id;
		$this->sugar_schema    = $sugar_schema;
		$this->ez_object_id    = $this->get_ez_object_id( );
		$this->sugar_connector = new SugarConnector();
	}

	public function __destruct() {
		unset( $this->sugar_connector, $this->sugar_schema );
	}
	
	public function get_ez_object_id() {
		$remote_id = $this->sugar_schema->ez_class_identifier . "_" . $this->sugar_id;
		$ez_object_id = owObjectsMaster::objectIDByRemoteID($remote_id);
		if ($ez_object_id === false) {
			throw new Exception( 'object_id introuvable pour remote_id=' . $remote_id);
		}
		return $ez_object_id;
	}
	
	public function synchro() {
		$this->synchro_relations();
	}
	
	public function synchro_relations() {
		foreach ($this->sugar_schema->get_relations() as $relation) {
			$this->synchro_relation($relation);
		}
	}
	
	public function synchro_relation($relation) {
		$values = $this->sugar_connector->get_relationships($this->module_name, $this->sugar_id, $relation[ 'related_module_name' ]);
		if ( ! is_array( $values ) || ( isset($values['error'] ) && $values['error']['number'] !== '0' ) ) {
			throw new Exception( 'Erreur du Sugar connecteur sur la liste des relations entre ' . $this->module_name . '#' . $this->sugar_id .' et ' . $relation[ 'related_module_name' ] );
		}
		foreach ( $values[ 'data' ] as $value ) {
			print_r( $value );
		}
	}
}
?>