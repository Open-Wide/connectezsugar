<?php 

class Module_Sugar_Schema {
	
	private $module_name = '';
	private $cli;
	public $ez_class_name = '';
	public $ez_class_identifier = '';
	public $ez_class_id = '';
	private $relations = array();

	public function __construct($module_name, $cli) {
		$this->module_name = $module_name;
		$this->cli         = $cli;
	}
	
	public function get_relations() {
		return $this->relations;
	}
	
	public function load_fields() {
	}
	
	public function load_relations() {
		$this->load_relations_sugar_schema();
		$this->load_relations_ez_sugar_mapping();
		$this->ez_class_name       = $this->get_ez_class_name( $this->module_name );
		$this->ez_class_identifier = $this->get_ez_class_identifier( $this->module_name );
		$this->ez_class_id         = eZContentClass::classIDByIdentifier( $this->ez_class_identifier );
		$this->valid_ez_class_relations( );
	}
		
	public function get_ez_class_name( $module_name ) {
		$ini = eZIni::instance( 'sugarcrm.ini' );
		if ($ini->hasVariable('Mapping', 'mapping_names')) {
			$mapping = $ini->variable('Mapping', 'mapping_names');
			if ( isset( $mapping[ $module_name ] ) ) {
				return $mapping[ $module_name ];
			}
		}
		return FALSE;
	}
	
	public function get_ez_class_identifier( $module_name ) {
		$ini = eZIni::instance( 'sugarcrm.ini' );
		if ($ini->hasVariable('Mapping', 'mapping_identifiers')) {
			$mapping = $ini->variable('Mapping', 'mapping_identifiers');
			if ( isset( $mapping[ $module_name ] ) ) {
				return $mapping[ $module_name ];
			}
		}
		return FALSE;
	}
	
	
	
	/* PRIVATE METHODS */
	
	private function load_relations_sugar_schema() {
		$ini = eZIni::instance( 'mappingezsugar.ini' );
		if ($ini->hasVariable($this->module_name, 'relations_names')) {
			foreach( $ini->variable($this->module_name, 'relations_names') as $related_module_name => $relation_name ) {
				$related_class_name       = $this->get_ez_class_name( $related_module_name );
				$related_class_identifier = $this->get_ez_class_identifier( $related_module_name );
				$this->relations[ $relation_name ] = array(
					'related_module_name'      => $related_module_name,
					'related_class_name'       => $related_class_name,
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
		if ( $ini->hasVariable( $this->module_name, 'relation_to_attribute_type' ) ) {
			$attribute_type = $ini->variable( $this->module_name, 'relation_to_attribute_type' );
		}
		if ($ini->hasVariable($this->module_name, 'relation_to_attribute')) {
			foreach( $ini->variable($this->module_name, 'relation_to_attribute') as $relation_name => $attribute_name ) {
				if ( ! isset( $this->relations[ $relation_name ] ) ) {
					throw new Exception( 'Essaie de convertir en attribut une relation non-définie : "'.$relation_name.'" de "'.$this->module_name.'" vers "'.$attribute.'"' );
				} else {
					if ( isset( $attribute_type[ $relation_name ] ) ) {
						$attribute_type = $attribute_type[ $relation_name ];
					} else {
						$attribute_type = 'one';
					}
					$this->relations[ $relation_name ][ 'type' ] = 'attribute';
					$this->relations[ $relation_name ][ 'attribute_name' ] = $attribute_name;
					$this->relations[ $relation_name ][ 'attribute_type' ] = $attribute_type;
				}
			}
		}
	}
		
	private function valid_ez_class_relations() {
		$ez_class = eZContentClass::fetch( $this->ez_class_id );
		foreach ( $this->relations as $relation ) {
			if ( $relation[ 'type' ] == 'attribute' ) {
				$attribute = $ez_class->fetchAttributeByIdentifier( $relation[ 'attribute_name' ], FALSE ); 
				$this->cli->notice( $attribute[ 'id' ] . $attribute[ 'identifier' ] );
				if ( $attribute === NULL ) {
					if ( $relation[ 'attribute_type' ] == 'list' ) {
						// @TODO
						$this->cli->warning( 'TODO: Création d\'attribut relation object list' ); 
					} else {
						$this->create_object_relation_attribute( $ez_class, $relation );
					}
					$this->cli->notice( 'Attribut ' . $relation[ 'attribute_name' ] . ' ajouté à la classe ' . $this->ez_class_name );
				}
			}
		}
	}
		
	private function create_object_relation_attribute( $ez_class, $relation ) {
		$version = $ez_class->attribute( 'version' ); 
		$attribute = eZContentClassAttribute::create( $this->ez_class_id, 'ezobjectrelation' );
		
		// Definition
		$attribute->setAttribute( 'version'      , $version);
		$attribute->setAttribute( 'name'         , $relation[ 'related_class_name' ] );
		$attribute->setAttribute( 'is_required'  , 0 );
		$attribute->setAttribute( 'is_searchable', 0 );
		$attribute->setAttribute( 'can_translate', 0 );
		$attribute->setAttribute( 'identifier'   , $relation[ 'attribute_name' ] );
		
		// Store
		$dataType = $attribute->dataType();
		$dataType->initializeClassAttribute( $attribute );
		$attribute->store();
	}
}
?>