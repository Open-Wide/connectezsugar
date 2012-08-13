<?php 

class Module_Schema {
	
	private $module_name = '';
	private $cli;
	public $ez_class_name = '';
	public $ez_class_identifier = '';
	public $ez_class_id = '';
	private $relations = array( );
	private $attributes = array( );

	public function __construct( $module_name, $cli ) {
		$this->module_name         = $module_name;
		$this->cli                 = $cli;
		$this->ez_class_name       = $this->get_ez_class_name( $this->module_name );
		$this->ez_class_identifier = $this->get_ez_class_identifier( $this->module_name );
		$this->ez_class_id         = eZContentClass::classIDByIdentifier( $this->ez_class_identifier );
	}
	
	public function get_relations( ) {
		return $this->relations;
	}
	
	public function load_fields( ) {
		// @TODO
	}
	
	public function load_relations( ) {
		$this->load_relations_sugar_schema( );
		$this->load_relations_ez_sugar_mapping( );
		$this->valid_ez_attributes( );
	}
	
	public function load_specific_schema( ) {
		$this->load_specific_attributes( );
		$this->valid_ez_attributes( );
	}
		
	public function get_ez_class_name( $module_name ) {
		$ini = eZIni::instance( 'sugarcrm.ini' );
		if ( $ini->hasVariable( 'Mapping', 'mapping_names' ) ) {
			$mapping = $ini->variable( 'Mapping', 'mapping_names' );
			if ( isset( $mapping[ $module_name ] ) ) {
				return $mapping[ $module_name ];
			}
		}
		return FALSE;
	}
	
	public function get_ez_class_identifier( $module_name ) {
		$ini = eZIni::instance( 'sugarcrm.ini' );
		if ( $ini->hasVariable( 'Mapping', 'mapping_identifiers' ) ) {
			$mapping = $ini->variable( 'Mapping', 'mapping_identifiers' );
			if ( isset( $mapping[ $module_name ] ) ) {
				return $mapping[ $module_name ];
			}
		}
		return FALSE;
	}
	
	
	
	/* PRIVATE METHODS */
	private function load_relations_sugar_schema( ) {
		$ini = eZIni::instance( 'mappingezsugar.ini' );
		if ( $ini->hasVariable( $this->module_name, 'relations_names' ) ) {
			foreach( $ini->variable( $this->module_name, 'relations_names' ) as $related_module_name => $relation_name ) {
				$related_class_name       = $this->get_ez_class_name( $related_module_name );
				$related_class_identifier = $this->get_ez_class_identifier( $related_module_name );
				$this->relations[ $relation_name ] = array( 
					'related_module_name'      => $related_module_name,
					'related_class_name'       => $related_class_name,
					'related_class_identifier' => $related_class_identifier,
					'related_class_id'		   => eZContentClass::classIDByIdentifier( $related_class_identifier ),
					'type' 		     		   => 'relation',
					'name' 			      	   => $relation_name,
				 );
			}
		}
	}
		
	private function load_relations_ez_sugar_mapping( ) {
		$ini = eZIni::instance( 'mappingezsugarschema.ini' );
		if ( $ini->hasVariable( $this->module_name, 'relation_to_attribute_type' ) ) {
			$attribute_type = $ini->variable( $this->module_name, 'relation_to_attribute_type' );
		}
		if ( $ini->hasVariable( $this->module_name, 'relation_to_attribute' ) ) {
			foreach( $ini->variable( $this->module_name, 'relation_to_attribute' ) as $relation_name => $attribute_name ) {
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
					$this->attributes[ $attribute_name ] = array( );
					$this->attributes[ $attribute_name ][ 'identifier' ] = $attribute_name;
					$this->attributes[ $attribute_name ][ 'type' ] = ( $attribute_type == 'list' ) ? 'ezobjectrelationlist' : 'ezobjectrelation';
					$this->attributes[ $attribute_name ][ 'name' ] = $this->relations[ $relation_name ][ 'related_class_name' ];
				}
			}
		}
	}
	
	private function load_specific_attributes( ) {
		$ini = eZIni::instance( 'mappingezsugarschema.ini' );
		if ( $ini->hasVariable( $this->module_name, 'specific_attribute' ) ) {
			foreach( $ini->variable( $this->module_name, 'specific_attribute' ) as $attribute_name ) {
				if ( ! isset( $ini->hasVariable( $this->module_name, 'specific_attribute_' . $attribute_name ) ) ) {
					throw new Exception( 'Pas d\'informations détaillées pour l\'attribut spécifique "' . $attribute_name . '"' );
				} else {
					$this->attributes[ $attribute_name ] =  $ini->variable( $this->module_name, 'specific_attribute_' . $attribute_name );
					$this->attributes[ $attribute_name ][ 'identifier' ] = $attribute_name;
				}
			}
		}
	}
		
	private function valid_ez_attributes( ) {
		$ez_class = eZContentClass::fetch( $this->ez_class_id );
		foreach ( $this->attributes as $attribute ) {
			$fetch = $ez_class->fetchAttributeByIdentifier( $attribute[ 'identifier' ], FALSE );
			if ( $fetch === NULL ) {
				$this->create_class_attribute( $ez_class, $attribute[ 'type' ], $attribute[ 'identifier' ], $attribute[ 'name' ] );
				$this->cli->notice( 'Attribut ' . $attribute[ 'name' ] . ' ajouté à la classe ' . $this->ez_class_name );
			} else {
				$this->cli->notice( 'Attribut relation trouvé : ' . $fetch[ 'id' ] . ' ' . $fetch[ 'identifier' ] );
			}
		}
	}
		
	private function create_class_attribute( $ez_class, $datatype, $identifier, $name ) {
		$version = $ez_class->attribute( 'version' ); 
		$attribute = eZContentClassAttribute::create( $this->ez_class_id, $datatype );
		
		// Definition
		$attribute->setAttribute( 'version'      , $version );
		$attribute->setAttribute( 'name'         , $name );
		$attribute->setAttribute( 'is_required'  , 0 );
		$attribute->setAttribute( 'is_searchable', 1 );
		$attribute->setAttribute( 'can_translate', 0 );
		$attribute->setAttribute( 'identifier'   , $identifier );
		
		// Store
		$dataType = $attribute->dataType( );
		$dataType->initializeClassAttribute( $attribute );
		$attribute->store( );
		$ez_class->sync( );
		$this->update_class( $this->ez_class_id );
	}
	
	// Code from https://raw.github.com/ezsystems/ezscriptmonitor/master/bin/addmissingobjectattributes.php
	private function update_class( $classId ) {
		$db = eZDB::instance( );
	
	    // If the class is not stored yet, store it now
	    $class = eZContentClass::fetch( $classId, true, eZContentClass::VERSION_STATUS_TEMPORARY );
	    if ( $class )
	    {
	        $this->cli->output( "Storing class" );
	        $class->storeDefined( $class->fetchAttributes( ) );
	    }
	
	    // Fetch the stored class
	    $class = eZContentClass::fetch( $classId );
	    if ( !$class )
	    {
	        $this->cli->error( 'Could not fetch class with ID: ' . $classId );
	        return;
	    }
	    $classAttributes = $class->fetchAttributes( );
	    $classAttributeIDs = array( );
	    foreach ( $classAttributes as $classAttribute )
	    {
	        $classAttributeIDs[] = $classAttribute->attribute( 'id' );
	    }
	
	    $objectCount = eZContentObject::fetchSameClassListCount( $classId );
	    $this->cli->output( 'Number of objects to be processed: ' . $objectCount );
	
	    $counter = 0;
	    $offset = 0;
	    $limit = 100;
	    $objects = eZContentObject::fetchSameClassList( $classId, true, $offset, $limit );
	
	    // Add and/or remove attributes for all versions and translations of all objects of this class
	    while ( count( $objects ) > 0 )
	    {
	        // Run a transaction per $limit objects
	        $db->begin( );
	
	        foreach ( $objects as $object )
	        {
	            $contentObjectID = $object->attribute( 'id' );
	            $objectVersions = $object->versions( );
	            foreach ( $objectVersions as $objectVersion )
	            {
	                $versionID = $objectVersion->attribute( 'version' );
	                $translations = $objectVersion->translations( );
	                foreach ( $translations as $translation )
	                {
	                    $translationName = $translation->attribute( 'language_code' );
	
	                    // Class attribute IDs of object attributes ( not necessarily the same as those in the class, hence the manual sql )
	                    $objectClassAttributeIDs = array( );
	                    $rows = $db->arrayQuery( "SELECT id,contentclassattribute_id, data_type_string
	                                              FROM ezcontentobject_attribute
	                                              WHERE contentobject_id = '$contentObjectID' AND
	                                                    version = '$versionID' AND
	                                                    language_code='$translationName'" );
	                    foreach ( $rows as $row )
	                    {
	                        $objectClassAttributeIDs[ $row['id'] ] = $row['contentclassattribute_id'];
	                    }
	
	                    // Quick array diffs
	                    $attributesToRemove = array_diff( $objectClassAttributeIDs, $classAttributeIDs ); // Present in the object, not in the class
	                    $attributesToAdd = array_diff( $classAttributeIDs, $objectClassAttributeIDs ); // Present in the class, not in the object
	
	                    // Remove old attributes
	                    foreach ( $attributesToRemove as $objectAttributeID => $classAttributeID )
	                    {
	                        $objectAttribute = eZContentObjectAttribute::fetch( $objectAttributeID, $versionID );
	                        if ( !is_object( $objectAttribute ) )
	                            continue;
	                        $objectAttribute->remove( $objectAttributeID );
	                    }
	
	                    // Add new attributes
	                    foreach ( $attributesToAdd as $classAttributeID )
	                    {
	                        $objectAttribute = eZContentObjectAttribute::create( $classAttributeID, $contentObjectID, $versionID, $translationName );
	                        if ( !is_object( $objectAttribute ) )
	                            continue;
	                        $objectAttribute->setAttribute( 'language_code', $translationName );
	                        $objectAttribute->initialize( );
	                        $objectAttribute->store( );
	                        $objectAttribute->postInitialize( );
	                    }
	                }
	            }
	
	            // Progress bar and Script Monitor progress
	            $this->cli->output( '.', false );
	            $counter++;
	            if ( $counter % 70 == 0 or $counter >= $objectCount )
	            {
	                $progressPercentage = ( $counter / $objectCount ) * 100;
	                $this->cli->output( sprintf( ' %01.1f %%', $progressPercentage ) );
	            }
	        }
	
	        $db->commit( );
	
	        $offset += $limit;
	        $objects = eZContentObject::fetchSameClassList( $classId, true, $offset, $limit );
	    }
	
	    // Set the object name to the first attribute, if not set
	    $classAttributes = $class->fetchAttributes( );
	
	    // Fetch the first attribute
	    if ( count( $classAttributes ) > 0 && trim( $class->attribute( 'contentobject_name' ) ) == '' )
	    {
	        $db->begin( );
	        $identifier = $classAttributes[0]->attribute( 'identifier' );
	        $identifier = '<' . $identifier . '>';
	        $class->setAttribute( 'contentobject_name', $identifier );
	        $class->store( );
	        $db->commit( );
	    }
	}
}
?>