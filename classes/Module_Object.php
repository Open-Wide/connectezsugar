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
	
	private function get_ez_object_id($ez_class_identifier, $sugar_id) {
		$remote_id = $ez_class_identifier . "_" . $sugar_id;
		$ez_object_id = owObjectsMaster::objectIDByRemoteID($remote_id);
		if ($ez_object_id === false) {
			throw new Exception( 'object_id introuvable pour remote_id=' . $remote_id);
		}
		return $ez_object_id;
	}
	
	public function import_relations() {
		foreach ($this->sugar_schema->get_relations() as $relation) {
			$this->import_relation($relation);
		}
	}
	
	private function import_relation($relation) {
		$diff_related_ids = $this->diff_relations_common( $relation );
		if ( $relation[ 'type' ] == 'relation' ) {
			$this->import_relation_common($diff_related_ids);
		} else if ( $relation[ 'type' ] == 'attribute' ) {
			// @TODO
			$this->import_relation_common($diff_related_ids);
		}
	}
	
	private function import_relation_common($diff_related_ids) {
		foreach ( $diff_related_ids[ 'to_add' ] as $related_ez_object_id ) {
			if ( $this->ez_object->addContentObjectRelation( $related_ez_object_id ) === FALSE ) {
				throw new Exception( 'Erreur de eZ Publish, impossible d\'ajouter une relation entre ' . $this->sugar_schema->ez_class_identifier . '#' . $this->ez_object_id .' et ' . $relation[ 'related_class_identifier' ] . '#' . $related_ez_object_id );
			}
			echo 'Add relation between ' . $this->ez_object->attribute( 'name' ) . ' and ' . $related_ez_object_id . PHP_EOL;
		}
		foreach ( $diff_related_ids[ 'to_remove' ] as $related_ez_object_id ) {
			if ( $this->ez_object->removeContentObjectRelation( $related_ez_object_id ) === FALSE ) {
				throw new Exception( 'Erreur de eZ Publish, impossible de supprimer une relation entre ' . $this->sugar_schema->ez_class_identifier . '#' . $this->ez_object_id .' et ' . $relation[ 'related_class_identifier' ] . '#' . $related_ez_object_id );
			}
			echo 'Remove relation between ' . $this->ez_object->attribute( 'name' ) . ' and ' . $related_ez_object_id . PHP_EOL;
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
		$values = $this->sugar_connector->get_relationships($this->module_name, $this->sugar_id, $relation[ 'related_module_name' ]);
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
		// @TODO: Attribute relation 
		echo 'TODO: Relation attribute' . PHP_EOL;
	}
	
	/**
	 * EXPORT SugarCRM
	*/
	public function export_relations() {
		foreach ($this->sugar_schema->get_relations() as $relation) {
			$this->export_relation($relation);
		}
	}
	
	private function export_relation() {
		if ( $relation[ 'type' ] == 'relation' ) {
			$this->export_relation_common($relation);
		} else if ( $relation[ 'type' ] == 'attribute' ) {
			
		}
	}
	
	
	/**
	*
	* Synchro Ez Publish vers SugarCRM -> à reprendre !
	*
	* @param array $params Tableau de paramètres
	*        - cli Instance SmartCli
	*        - entry_list_ids Liste des ID SugarCRM déjà synchronisés
	*/
	public function export($params = array()) {
		$cli 	        = $params['cli'];
		$entry_list_ids = $params['entry_list_ids'];
		
		$class_name = $this->defClassName($this->module_name);
		$class_identifier = $this->defClassIdentifier($this->module_name);
		
		$ini = eZIni::instance( 'otcp.ini' );
		
		// On remplit un tableau avec les listes d'identifiants SugarCRM pour faciliter la comparaison avec les ID externes eZ modifiés
		$ez_init_ids = array();
		foreach ($entry_list_ids as $entry) {
			$id_sugar = $class_identifier . '_' . $entry['id']; // Ex : "visit_ea7548f0-1e5e-e4ba-0cfc-501941e09129"
			if (!in_array($id_sugar, $ez_init_ids)) {
				$ez_init_ids[] = $id_sugar;
			}
		}
		
		// Liste des nodes eZ modifiées depuis la dernière synchro
		$nodes = eZFunctionHandler::execute(
			'content',
			'list',
			array(
				'parent_node_id'     => $ini->variable( 'crmDirectories', $class_identifier ),
				'class_filter_type'  => 'include',
				'class_filter_array' => array($class_identifier),
				'attribute_filter'   => array(
					array('modified', '>=', $this->get_last_synchro_date_time())
				),
			)
		);
		$ez_remote_ids_to_sync = array(); // Tableau qui contiendra les ID SugarCRM dont l'objet eZ a été modifié => à envoyer à Sugar
		foreach ($nodes as $node) {
			$remote_id = $node->object()->remoteID();
			if (!in_array($remote_id, $ez_init_ids)) {
				// Si l'ID ne figure pas dans le tableau des ID déjà synchronisés Sugar => eZ
				$ez_remote_ids_to_sync[] = $remote_id;
			}
		}
		$cli->notice(print_r($ez_init_ids));
		$cli->notice(print_r($ez_remote_ids_to_sync));
		// ->ContentObjectID
		//->ContentObject->ContentObjectAttributes[5]['fre-FR'][0]
		
		/*
		* @TODO
		* Parcourir la liste des fields du module depuis le fichier ini de mapping
		* Envoyer la structure à set_entry ou set_entries
		*/
	}
	
	private function get_last_synchro_date_time() {
		$inisynchro = eZINI::instance('synchro.ini');
		$date = strtotime($inisynchro->variable('Synchro','lastSynchroDatetime'));
		$date = mktime(0, 0, 0, 8, 9, 2012); // Pour le test
		return $date;
	}
}
?>