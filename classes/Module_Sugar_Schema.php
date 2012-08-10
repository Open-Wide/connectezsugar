<?php 

class Module_Sugar_Schema {
	
	private $module_name = '';
	public $ez_class_name = '';
	public $ez_class_identifier = '';
	private $relations = array();

	public function __construct($module_name) {
		$this->module_name = $module_name;
		
		$this->load_relations();
	}
	
	public function get_relations() {
		return $this->relations;
	}
	
	private function load_relations() {
		$this->load_relations_sugar_schema();
		$this->load_relations_ez_sugar_mapping();
		$this->ez_class_name = $this->get_ez_class_name( $this->module_name );
		$this->ez_class_identifier = $this->get_ez_class_identifier( $this->module_name );
		$this->valid_ez_class( );
	}
	
	private function load_relations_sugar_schema() {
		$ini = eZIni::instance( 'mappingezsugar.ini' );
		if ($ini->hasVariable($this->module_name, 'relations_names')) {
			foreach( $ini->variable($this->module_name, 'relations_names') as $related_module_name => $relation_name ) {
				$related_class_identifier = $this->get_ez_class_identifier( $related_module_name );
				$this->relations[ $relation_name ] = array(
					'related_module_name'      => $related_module_name,
					'related_class_identifier' => $related_class_identifier,
					'related_class_id'		   => eZContentClass::classIDByIdentifier($related_class_identifier),
					'type' 		     		   => 'relation',
					'name' 			      	   => $relation_name,
				);
			}
		}
	}
		
	private function load_relations_ez_sugar_mapping() {
		$ini = eZIni::instance( 'mappingezsugarschema.ini' );
		if ($ini->hasVariable($this->module_name, 'relation_to_attribute')) {
			foreach( $ini->variable($this->module_name, 'relation_to_attribute') as $relation_name => $attribute ) {
				if ( ! isset( $this->relations[ $relation_name ] ) ) {
					throw new Exception( 'Essaie de convertir en attribut une relation non-définie : "'.$relation_name.'" de "'.$this->module_name.'" vers "'.$attribute.'"' );
				} else {
					$this->relations[ $relation_name ][ 'type' ] = 'attribute';
					$this->relations[ $relation_name ][ 'attribute_name' ] = $attribute;
				}
			}
		}
	}
		
	private function get_ez_class_name( $module_name ) {
		$ini = eZIni::instance( 'sugarcrm.ini' );
		if ($ini->hasVariable('Mapping', 'mapping_names')) {
			$mapping = $ini->variable('Mapping', 'mapping_names');
			if ( isset( $mapping[ $module_name ] ) ) {
				return $mapping[ $module_name ];
			}
		}
		return FALSE;
	}
	
	private function get_ez_class_identifier( $module_name ) {
		$ini = eZIni::instance( 'sugarcrm.ini' );
		if ($ini->hasVariable('Mapping', 'mapping_identifiers')) {
			$mapping = $ini->variable('Mapping', 'mapping_identifiers');
			if ( isset( $mapping[ $module_name ] ) ) {
				return $mapping[ $module_name ];
			}
		}
		return FALSE;
	}
		
	private function valid_ez_class() {
		// @TODO: Attribute class update 
		echo 'TODO: Attribute class update' . PHP_EOL;
	}
}
?>