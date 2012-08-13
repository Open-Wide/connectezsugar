<?php 

include_once( 'extension/connectezsugar/classes/Module_Schema.php' );
include_once( 'extension/connectezsugar/classes/Module_Object.php' );

class Module {
	
	private $module_name = '';
	private $cli;
	private $offset = 0;
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
		if ( $this->offset > $max ) {
			return NULL;
		}
		$entries = $this->sugar_connector->get_entry_list( $this->module_name, array( 'id' ), $this->offset, $paquet );
		if (
			! is_array( $entries ) ||
			! is_array( $entries[ 'data' ] ) || 
			( isset($entries['error'] ) && $entries['error']['number'] !== '0' )
		) {
			throw new Exception( 'Erreur du Sugar connecteur sur la liste des entrées du module ' . $this->module_name );
		}
		foreach ( $entries[ 'data' ] as $entry ) {
			$sugar_ids[ ] = $entry[ 'id' ];
		}
		$this->cli->notice( '-- Checkpoint: ' . $this->offset );
		$this->offset += $paquet;
		return $sugar_ids;
	}
	
	/**
	 * EXPORT SugarCRM
	 */
	
	public function export_module_objects() {
		$this->call_module_objects( 'export' );
	}
	
	private function call_module_objects( $method ) {
		$schema = new Module_Schema( $this->module_name, $this->cli );
		$schema->load_relations( );
		while ( $sugar_ids = $this->get_sugar_ids( ) ) {
			foreach ( $sugar_ids as $sugar_id ) {
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
}
?>