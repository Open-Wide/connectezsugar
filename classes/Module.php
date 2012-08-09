<?php 

include_once( 'extension/connectezsugar/classes/Module_Sugar_Schema.php' );
include_once( 'extension/connectezsugar/classes/Module_Object.php' );

class Module {
	
	private $module_name = '';
	private $sugar_connector;
	
	public function __construct($module_name) {
		$this->module_name = $module_name;
		$this->sugar_connector = new SugarConnector();
		if ( ! $this->sugar_connector->login() ) {
			throw new Exception( 'Impossible de se connecter avec le Sugar Connector' );
		}
	}

	public function __destruct() {
		unset( $this->sugar_connector );
	}
	
	public function synchro_module_objects() {
		$schema = new Module_Sugar_Schema($this->module_name);
		// @TODO: Enlever le 2 mis là pour les tests
		foreach ( $this->get_sugar_ids( 2 ) as $sugar_id ) {
			$object = new Module_Object( $this->module_name, $sugar_id, $schema );
			$object->synchro( );
		}
	}
	
	public function get_sugar_ids( $max = 9999 ) {
		$sugar_ids = array( );
		$entries = $this->sugar_connector->get_entry_list( $this->module_name, array( 'id' ), 0, $max );
		if ( ! is_array( $entries ) || ( isset($entries['error'] ) && $entries['error']['number'] !== '0' ) ) {
			throw new Exception( 'Erreur du Sugar connecteur sur la liste des entrées du module ' . $this->module_name );
		}
		foreach ( $entries[ 'data' ] as $entry ) {
			$sugar_ids[ ] = $entry[ 'id' ];
		}
		return $sugar_ids;
	}
}
?>