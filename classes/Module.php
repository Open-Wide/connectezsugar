<?php 

include_once( 'extension/connectezsugar/classes/Module_Schema.php' );
include_once( 'extension/connectezsugar/classes/Module_Object_Accessor.php' );
include_once( 'extension/connectezsugar/classes/Module_Object.php' );

class Module extends Module_Object_Accessor {
	
	protected $module_name = '';
	protected $cli;
	
	public function __construct($module_name, $cli) {
		$this->module_name = $module_name;
		$this->cli         = $cli;
		parent::__construct( );
	}

	public function __destruct() {
		parent::__destruct( );
		unset( $this->cli );
	}
	
	public function import_module_relations_all( ) {
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
	
	public function import_module_relations() {
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
		//@TODO décommenter lors de la mise en prod 
		$this->set_last_synchro_date_time( 'import_module_relations' );
	}
	
	/*
	 * EXPORT SugarCRM
	 */
	
	protected function get_ez_remote_ids_since_last_sync( $mode ) {
		return $this->get_ez_remote_ids( $this->get_last_synchro_date_time( $mode ) );
	}
	
	protected function get_ez_remote_ids( $timestamp = false ) {
		
		$ez_remote_ids 	  = array(); // Tableau qui contiendra les ID SugarCRM dont l'objet eZ a été modifié => à envoyer à Sugar
		$ini 		   	  = eZIni::instance( 'otcp.ini' );
		$class_identifier = Module_Schema::get_ez_class_identifier( $this->module_name );
		
		$params = array(
			'parent_node_id'     => $ini->variable( 'crmDirectories', $class_identifier ),
			'class_filter_type'  => 'include',
			'class_filter_array' => array( $class_identifier ),
		);
		if ($timestamp !== false) {
			$params[ 'attribute_filter' ] = array(
				array( 'modified', '>=', $timestamp )
			);
		}
		$nodes = eZFunctionHandler::execute( 'content', 'list', $params );
		$ez_init_ids = array( ); // @TODO : utile ?
		foreach ($nodes as $node) {
			$remote_id = $node->object()->remoteID( );
			// Si l'ID ne figure pas dans le tableau des ID déjà synchronisés Sugar => eZ
			if (!in_array( $remote_id, $ez_init_ids )) {
				// On a un ID du genre "room_e7bd0519-2802-69ce-1f2f-501941f1e8e0", on supprime le préfixe "room_" pour ne conserver que l'ID
				list($ez_class_identifier, $sugar_id) = explode('_', $remote_id, 2);
				$ez_remote_ids[ ] = $sugar_id;
			}
		}
		$this->cli->notice( 'Nb d\'objets ' . $class_identifier . ' modifiés dans eZ depuis le ' . strftime('%Y-%m-%d %H:%M:%S', $timestamp) . ' : ' . count($ez_remote_ids) );
		
		return $ez_remote_ids;
	}
	
	public function export_module_objects() {
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
		//@TODO décommenter lors de la mise en prod 
		$this->set_last_synchro_date_time( 'export_module' );
	}
}
?>