<?php

class owObjectsMaster
{
	/*
	 * CONSTANTES
	 */
	
	const LOGFILE = "var/log/owObjectsMaster.log";
	const INIPATH = "extension/connectezsugar/settings/";
	const INIFILE = "sugarcrm.ini.append.php";
	
	/*
	 * PROPRIÉTÉS
	 */
	
	// STATIQUES
	private static $definition = array();
	private static $properties_list;
	private static $parameters_per_function;
	private static $inidata_list;
	protected static $inidata = array();
	protected static $logger;
	
	// D'INSTANCE
	protected $properties = array();
	
	/*
	 * MÉTHODES STATIQUES
	 */
	
	/*
	 * instancie un nouveau objet de cette class
	 * @return object[owObjectsMaster]
	 */
	public static function instance($properties = array())
	{
		self::$logger = owLogger::CreateForAdd(self::LOGFILE);
		self::definition();
		self::getIniData();
		
		if(count($properties) > 0)
		{
			$object = new owObjectsMaster();
			$object->init($properties);
			
			return $object;
		}
		
		return new owObjectsMaster();
	}
	
	public static function definition()
	{
		// inidata_list ***
		$inidata_list = array(	'AdminID'				=> array( 'block' => "Users", 'var' => "AdminID" ),
								'DefaultParentNodeID'	=> array( 'block' => "Tree", 'var' => "DefaultParentNodeID" ),
								'DefaultSectionID'		=> array( 'block' => "Tree", 'var' => "DefaultSectionID" ),
							); 
		self::$inidata_list = $inidata_list;
		
		// properties_list ***
		$properties_list = array(	'class_name',
									'class_attributes',
									'class_id',
									'class_identifier',
									'class_object_name',
									'object_name',
									'object_attributes',
									'object_remote_id',
									'content_object'
								);
		self::$properties_list = $properties_list;
		
		// parameters_per_function ***
		$parameters_per_function = array(	'createClassEz' => array(	'class_name' 		=> true,
																		'class_attributes' 	=> true,
																		'class_object_name'	=> false
																	),
											'createObjectEz' => array(	'class_id' 			=> true,
																		'object_attributes' => true,
																		'object_remote_id'	=> false,
																		'object_name'		=> false
																	),
											'updateObjectEz' => array(	'class_id' 			=> true,
																		'object_attributes' => true,
																		'content_object'	=> true,
																		'object_name'		=> false
																	)
										);
		
		// tableau complet *******
		$definition = array('properties_list' => $properties_list,
							'parameters_per_function' => $parameters_per_function,
							'inidata_list' => $inidata_list
							);
		self::$definition = $definition;
										
		return $definition;
	}
	
	
	
	
	/*
	 * retourne la valeur d'une propriété statique si elle existe
	 * @param $name string
	 * @return self::$name mixed or null
	 */
	public static function getStaticProperty($name)
	{
		if(isset(self::$$name))
			return self::$$name;
	}
	
	
	/*
	 * va chercher les settings dans le fichiers self::INIFILE
	 * et les enregistre dans self::$inidata
	 * @param none
	 * @return void
	 */
	public static function getIniData()
	{
		// init du fichier de log
		self::$logger = owLogger::CreateForAdd(self::LOGFILE);
		
		// load definition si ce n'est pas dèjà fait
		if(count(self::$definition) == 0)
			self::definition();
		
		// verifie si self::INIFILE existe dans self::INIPATH
	   	$initest = eZINI::exists(self::INIFILE, self::INIPATH);
		if($initest)
		{
			$ini = eZINI::instance(self::INIFILE, self::INIPATH);
			
			// recupere toutes les variables du fichier ini definie dans self::$inidata_list 
			foreach(self::$inidata_list as $name => $args)
			{
				self::$inidata[$name] = $ini->variable($args['block'], $args['var']);
			}
			
			// si une des variables n'existe pas on renvoie false et on ecrie dans le $log
			foreach( self::$inidata as $k => $var )
			{
				$err = 0;
				if( !$var )
				{
					$error = "la variable demandées : " . $k . ", n'existe pas !";
					self::$logger->writeTimedString("Erreur getIniData() : " . $error);
					$err++;
				}
			}
			
			if( $err > 0 )
				return false;
			
			return true;
		}
		else
		{
			$error = self::INIFILE . " IN " . self::INIPATH . " NON TROUVÉ !";
			self::$logger->writeTimedString("Erreur getIniData() : " . $error);
			return false;
		}
	}
	
	/*
	 * retourne l'ID d'un content_object pour un remote_id donné
	 * @param $remoteID string
	 * @return $id string OR false si ne trouve pas 
	 */
	public static function objectIDByRemoteID($remoteID)
	{
		$db = eZDB::instance();
        $remoteID =$db->escapeString( $remoteID );
        $resultArray = $db->arrayQuery( 'SELECT id FROM ezcontentobject WHERE remote_id=\'' . $remoteID . '\'' );
		
        if ( count( $resultArray ) != 1 )
            return false;
        else
            return $resultArray[0]['id'];

	}
	
	/*
	 * retourne l'ID d'un content_object pour un remote_id donné
	 * @param $remoteID string
	 * @return $id string OR false si ne trouve pas 
	 */
	public static function objectMainNodeIDByID($id)
	{
		$db = eZDB::instance();
		settype($id,'int');
        $resultArray = $db->arrayQuery( "SELECT main_node_id FROM ezcontentobject_tree WHERE contentobject_id=" . $id );
		
        if ( count( $resultArray ) == 0 )
            return false;
        else
            return $resultArray[0]['main_node_id'];

	}
	
	/*
     Fetch all attributes of all versions belongs to a contentObject.
    */
    public static function allContentObjectAttributes( $contentObjectID, $asObject = true )
    {
        return eZPersistentObject::fetchObjectList( eZContentObjectAttribute::definition(),
                                                    null,
                                                    array("contentobject_id" => $contentObjectID ),
                                                    null,
                                                    null,
                                                    $asObject );
    }
	
	/*
	 * mèthode d'instance de la class eZContentObject
	 * readapté et changé en mèthode statique de cette class
	 * utile pour nettoyer la base de donné quand un objet n'est peut plus être retrouvé avec un fetch()
	 * @param $delID integer
	 * @return bool
	 */
	public static function objectPurge($delID)
    {
        // transforme en integer l'ID donnée si il ne l'est pas
    	settype($delID,'int');
        
        // init du fichier de log
		self::$logger = owLogger::CreateForAdd(self::LOGFILE);
        
        // empeche d'utiliser cette mèthode si l'objet existe encore !
        // utiliser plutôt eZContentObjectOperations::remove($id);
        // utilise la mèthode seulement dans le cas ou l'objet n'existe plus mais il y reste des traces dans la base de donné ! 
        if( is_object(eZcontentObject::fetch($delID)) )
        {
        	$msg = "eZcontentObject::fetch($delID) retourne un objet.\n Utilisez plutôt eZContentObjectOperations::remove(\$id) !";
        	self::$logger->writeTimedString("Notice objectPurge() : " . $msg);
        	return false;
        }
        
        $db = eZDB::instance();
		
        $resultArray = $db->arrayQuery( "SELECT name FROM ezcontentobject WHERE id=" . $delID );
        
        if ( count( $resultArray ) == 0 )
        {
        	$msg = "Aucun enregistrement avec id=" . $delID . " a été trouvé dans la table ezcontentobject !";
        	self::$logger->writeTimedString("Notice objectPurge() : " . $msg);
        	return false;
        }
        else
            $contentobject_name = $resultArray[0]['name'];
        
        // Who deletes which content should be logged.
        eZAudit::writeAudit( 'content-delete', array( 'Object ID' => $delID, 'Content Name' => $contentobject_name,
                                                      'Comment' => 'Purged the contentobject avec id ' . $delID . ' : owObjectsMaster::objectPurge()' ) );

        $db->begin();

        $contentobjectAttributes = self::allContentObjectAttributes( $delID );

        foreach ( $contentobjectAttributes as $contentobjectAttribute )
        {
            $dataType = $contentobjectAttribute->dataType();
            if ( !$dataType )
                continue;
            $dataType->deleteStoredObjectAttribute( $contentobjectAttribute );
        }

        eZInformationCollection::removeContentObject( $delID );

        eZContentObjectTrashNode::purgeForObject( $delID );

        $db->query( "DELETE FROM ezcontentobject_tree
             WHERE contentobject_id='$delID'" );

        $db->query( "DELETE FROM ezcontentobject_attribute
             WHERE contentobject_id='$delID'" );

        $db->query( "DELETE FROM ezcontentobject_version
             WHERE contentobject_id='$delID'" );

        $db->query( "DELETE FROM ezcontentobject_name
             WHERE contentobject_id='$delID'" );

        $db->query( "DELETE FROM ezcobj_state_link WHERE contentobject_id=$delID" );

        $db->query( "DELETE FROM ezcontentobject
             WHERE id='$delID'" );

        $db->query( "DELETE FROM eznode_assignment
             WHERE contentobject_id = '$delID'" );

        $db->query( "DELETE FROM ezuser_role
             WHERE contentobject_id = '$delID'" );

        $db->query( "DELETE FROM ezuser_discountrule
             WHERE contentobject_id = '$delID'" );

        eZContentObject::fixReverseRelations( $delID, 'remove' );

        // si on passe par cette mèthode c'est parce que on a plus l'objet !!!!!
        //eZSearch::removeObject( $this );

        // Check if deleted object is in basket/wishlist
        $sql = 'SELECT DISTINCT ezproductcollection_item.productcollection_id
                FROM   ezbasket, ezwishlist, ezproductcollection_item
                WHERE  ( ezproductcollection_item.productcollection_id=ezbasket.productcollection_id OR
                         ezproductcollection_item.productcollection_id=ezwishlist.productcollection_id ) AND
                       ezproductcollection_item.contentobject_id=' . $delID;
        $rows = $db->arrayQuery( $sql );
        if ( count( $rows ) > 0 )
        {
            $countElements = 50;
            $deletedArray = array();
            // Create array of productCollectionID will be removed from ezwishlist and ezproductcollection_item
            foreach ( $rows as $row )
            {
                $deletedArray[] = $row['productcollection_id'];
            }
            // Split $deletedArray into several arrays with $countElements values
            $splitted = array_chunk( $deletedArray, $countElements );
            // Remove eZProductCollectionItem and eZWishList
            foreach ( $splitted as $value )
            {
                eZPersistentObject::removeObject( eZProductCollectionItem::definition(), array( 'productcollection_id' => array( $value, '' ) ) );
                eZPersistentObject::removeObject( eZWishList::definition(), array( 'productcollection_id' => array( $value, '' ) ) );
            }
        }
        $db->query( 'UPDATE ezproductcollection_item
                     SET contentobject_id = 0
                     WHERE  contentobject_id = ' . $delID );

        // Cleanup relations in two steps to avoid locking table for to long
        $db->query( "DELETE FROM ezcontentobject_link
                     WHERE from_contentobject_id = '$delID'" );

        $db->query( "DELETE FROM ezcontentobject_link
                     WHERE to_contentobject_id = '$delID'" );

        // Cleanup properties: LastVisit, Creator, Owner
        $db->query( "DELETE FROM ezuservisit
             WHERE user_id = '$delID'" );

        $db->query( "UPDATE ezcontentobject_version
             SET creator_id = 0
             WHERE creator_id = '$delID'" );

        $db->query( "UPDATE ezcontentobject
             SET owner_id = 0
             WHERE owner_id = '$delID'" );

        if ( isset( $GLOBALS["eZWorkflowTypeObjects"] ) and is_array( $GLOBALS["eZWorkflowTypeObjects"] ) )
        {
            $registeredTypes =& $GLOBALS["eZWorkflowTypeObjects"];
        }
        else
        {
            $registeredTypes = eZWorkflowType::fetchRegisteredTypes();
        }

        // Cleanup ezworkflow_event etc...
        foreach ( array_keys( $registeredTypes ) as $registeredTypeKey )
        {
            $registeredType = $registeredTypes[$registeredTypeKey];
            $registeredType->cleanupAfterRemoving( array( 'DeleteContentObject' => $delID ) );
        }

        $db->commit();
        
        return true;
    }
	
    
	/*
	 * CONSTRUCTEUR
	 * on ne peut pas faire un new owObjectsMaster() en dehors de la class ou des classes qui l'etende
	 * on est obligé de passer par la mèthode statique owObjectsMaster::instance()
	*/
	protected function owObjectsMaster()
	{
		
	}
	
	
	/*
	 * MÉTHODES D'INSTANCE
	 */
	
	protected function init($properties)
	{		
		foreach( $properties as $name => $value )
		{
			if(in_array($name,self::$properties_list))
				$this->properties[$name] = $value;
			else
			{	
				$error = "Propriété " . $name . " non trouvé parmi les propriétés d'un objet " . get_class();
				self::$logger->writeTimedString($error);
			}
		}
	}
	
	public function getProperties()
	{
		return $this->properties;
	}
	
	public function setProperties($properties)
	{
		$this->init($properties);
	}
	
	protected function verifyArgsForFunction($function_name, $args)
	{
		// load definition si ce n'est pas dèjà fait
		if(count(self::$definition) == 0)
			self::definition();

		// parametres necessaires à la function
		$parameters = self::$parameters_per_function[$function_name];
		
		// verifie si on a passé le parametre $args à la function $function_name
		if(is_null($args)) // si non
		{
			// verifie properties
			foreach($parameters as $name => $required)
			{
				if( !isset($this->properties[$name]) and $required )
				{
					$error = $function_name . " : " . $name . " n'est pas reinsegné !";
					self::$logger->writeTimedString($error);
					return false;
				}
			}
		}
		else // si oui
		{
			// verifie args
			foreach($parameters as $name => $required)
			{
				if( !isset($args[$name]) and $required )
				{
					$error = $function_name . " : " . $name . " n'est pas reinsegné !";
					self::$logger->writeTimedString($error);
					return false;
				}
				
				// set property
				$this->properties[$name] = $args[$name];
			}
		}
		
		return true;
		
	}
	
	public function createClassEz($args = null)
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("createClassEz", $args);
		if(!$verify)
			return false;
		
		// crée une nouvelle class EZ
		$userID = self::$inidata['AdminID'];
		$newClass =  eZContentClass::create( $userID );	
		
		// modele de nom pour les objets
		$contentobject_name = ( isset($this->properties['class_object_name']) )? $this->properties['class_object_name'] : "new " . $this->properties['class_name'];
		
		// @ TODO pouvoir decider pour :
		// 'is_container' (en dur pour l'instant!!)
		$newClass->setAttribute( 'version', 0);
		$newClass->setAttribute( 'name', $this->properties['class_name']);
		$newClass->setAttribute( 'identifier', strtolower($this->properties['class_name']) );
		$newClass->setAttribute( 'is_container', 1 ); // en dur pour l'instant !!
		$newClass->setAttribute( 'contentobject_name', "<" . $contentobject_name . ">" );
		 
		$newClass->store();
		
		// id de la class
		$ClassID = $newClass->ID;
		// version de la class
		$ClassVersion = $newClass->attribute( 'version' );
		
		// @ TODO pouvoir decider pour le group de classes
		// pour l'instant met la class dans le group "Content" (en dur !!)
		$ingroup = eZContentClassClassGroup::create( $ClassID,$ClassVersion,1,"Content" );
		$ingroup->store();
		
		// crée les attributs de la class EZ
		foreach( $this->properties['class_attributes'] as $name => $attrs )
		{
			$new_attribute = eZContentClassAttribute::create( $ClassID, $attrs['datatype'] );
			 
			$new_attribute->setAttribute( 'version', $ClassVersion);
			$new_attribute->setAttribute( 'name', $name );
			$new_attribute->setAttribute( 'is_required', $attrs['required'] );
			$new_attribute->setAttribute( 'is_searchable', 1 );
			$new_attribute->setAttribute( 'can_translate', 1 );
			$new_attribute->setAttribute( 'identifier', strtolower($name) );
			 
			$dataType = $new_attribute->dataType();
			$dataType->initializeClassAttribute( $new_attribute );
			$new_attribute->store();
		}
		
		return true;
			
	}
	
	// @TODO : upadate de la class
	public function updateClassEz($args = null)
	{
		
	}
	
	protected function verifyObjectAttributes()
	{
		/*if(!eZContentClass::exists($this->properties['class_id']))
			return false;
		$class = eZContentClass::fetch($this->properties['class_id']);
		$class_datamap = $class->dataMap();
		exit(var_dump($class_datamap));*/
	}
	
	public function createObjectEz($args = null)
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("createObjectEz", $args);
		if(!$verify)
			return false;
	
		// verifie si le tableau des attributes de l'objet est coherant aux attributes de la class
		$this->verifyObjectAttributes();
		if(!$verify)
			return false;
		
		// retrouve l'identifiant de la class
		$class_identifier = eZContentClass::classIdentifierByID($this->properties['class_id']);
		
    	$params = array();
		$params['class_identifier'] = $class_identifier; 
		$params['creator_id'] = self::$inidata['AdminID'];
		$params['parent_node_id'] = self::$inidata['DefaultParentNodeID'];
		$params['section_id'] = self::$inidata['DefaultSectionID'];
		if(isset($this->properties['object_remote_id']))
			$params['remote_id'] = $this->properties['object_remote_id'];
     	$params['attributes'] = $this->properties['object_attributes'];
		
		// PUBLISH OBJECT
		$contentObject = eZContentFunctions::createAndPublishObject( $params );
		if(!$contentObject)
			return false;
			
		// renomme l'objet si un nom est indiqué
		if(isset($this->properties['object_name']))
			$contentObject->rename($this->properties['object_name']);
		
		return true;
	}
	
	public function updateObjectEz($args = null)
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("updateObjectEz", $args);
		if(!$verify)
			return false;
		
		// verifie si le tableau des attributes de l'objet est coherant aux attributes de la class
		$this->verifyObjectAttributes();
		if(!$verify)
			return false;
			
		// retrouve l'identifiant de la class
		$class_identifier = eZContentClass::classIdentifierByID($this->properties['class_id']);
			
		$params = array();
		$params['class_identifier'] = $class_identifier; 
		$params['creator_id'] = self::$inidata['AdminID'];
		$params['parent_node_id'] = self::$inidata['DefaultParentNodeID'];
		$params['section_id'] = self::$inidata['DefaultSectionID'];	
     	$params['attributes'] = $this->properties['object_attributes'];
		

		// PUBLISH OBJECT
		$contentObject = eZContentFunctions::updateAndPublishObject( $this->properties['content_object'], $params );
		if(!$contentObject)
			return false;

		// renomme l'objet si un nom est indiqué
		if(isset($this->properties['object_name']))
			$contentObject->rename($this->properties['object_name']);
			
		return true;
	}
	
	
} // fin de class

?>
