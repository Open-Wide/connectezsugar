<?php 

include_once( 'extension/connectezsugar/classes/Module_Sugar_Schema.php' );
include_once( 'extension/connectezsugar/classes/Module_Object.php' );

class Module {
	
	private $module_name = '';
	private $cli;
	private $sugar_connector;
	
	public function __construct($module_name, $cli) {
		$this->module_name = $module_name;
		$this->cli         = $cli;
		$this->sugar_connector = new SugarConnector();
		if ( ! $this->sugar_connector->login() ) {
			throw new Exception( 'Impossible de se connecter avec le Sugar Connector' );
		}
	}

	public function __destruct() {
		unset( $this->sugar_connector, $this->cli );
	}
	
	public function import_module_objects() {
		$this->call_module_objects( 'import_relations' );
	}
	
	public function get_sugar_ids( $paquet = 500, $max = 99999 ) {
		$sugar_ids = array( );
		$offset = 0;
		$entries = $this->sugar_connector->get_entry_list( $this->module_name, array( 'id' ), $offset, $paquet );
		while ( count( $entries[ 'data' ] ) ) {
			if ( ! is_array( $entries ) || ( isset($entries['error'] ) && $entries['error']['number'] !== '0' ) ) {
				throw new Exception( 'Erreur du Sugar connecteur sur la liste des entrées du module ' . $this->module_name );
			}
			$this->cli->notice( 'Un paquet, offset : ' . $offset );
			foreach ( $entries[ 'data' ] as $entry ) {
				$sugar_ids[ ] = $entry[ 'id' ];
			}
			$offset += $paquet;
			if ( $offset > $max ) {
				break;
			}
			$entries = $this->sugar_connector->get_entry_list( $this->module_name, array( 'id' ), $offset, $paquet );
		}
		return $sugar_ids;
	}
	
	/**
	 * EXPORT SugarCRM
	 */
	
	public function export_module_objects() {
		$this->call_module_objects( 'export' );
	}
	
	private function call_module_objects( $method ) {
		$schema = new Module_Sugar_Schema($this->module_name, $this->cli);
		$schema->load_relations( );
		// @TEST: Enlever le 2 mis là pour les tests
		foreach ( $this->get_sugar_ids( ) as $sugar_id ) {
			try {
				$object = new Module_Object( $this->module_name, $sugar_id, $schema, $this->cli );
				$object->$method( );
				unset( $object );
			} catch( Exception $e ) {
				$this->cli->error( $e->getMessage( ) );
			}
		}
	}
}
?>