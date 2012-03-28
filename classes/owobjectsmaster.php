<?php

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

class owObjectsMaster
{
	/*
	 * CONSTANTES
	 */
	
	const LOGFILE = "var/log/owObjectsMaster";
	const INIPATH = "extension/connectezsugar/settings/";
	const INIFILE = "owobjectsmaster.ini.append.php";
	
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
	 * @return object[owObjectsMaster] OR false
	 */
	public static function instance($properties = array())
	{
		self::initLogger();
		self::definition();
		
		if( !self::getIniData() )
			return false;
			
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
								'DefaultCanTranslate'	=> array( 'block' => "Translation", 'var' => "DefaultCanTranslate" ),
								'DefaultIsSearchable'	=> array( 'block' => "Search", 'var' => "DefaultIsSearchable" ),
								'ClassParentNodeID'		=> array( 'block' => "Tree", 'var' => "ClassParentNodeID"),
								'DefaultClassGroup'		=> array( 'block' => "Class", 'var' => "DefaultClassGroup"),
								'ClassGroup'			=> array( 'block' => "Class", 'var' => "ClassGroup"),
								'DefaultClassIsContainer'=> array( 'block' => "Class", 'var' => "DefaultClassIsContainer"),
								'ClassIsContainer'		=> array( 'block' => "Class", 'var' => "ClassIsContainer"),
							); 
		self::$inidata_list = $inidata_list;
		
		
		// properties_list ***
		$properties_list = array(	'class_name',
									'class_attributes',
									'class_id',
									'class_identifier',
									'class_object_name',
									'class_options',
									'object_name',
									'object_attributes',
									'object_remote_id',
									'content_object'
								);
		self::$properties_list = $properties_list;
		
		
		// parameters_per_function ***
		$parameters_per_function = array('createClassEz' => array(	'class_name' 		=> true,
																	'class_identifier' 	=> false,
																	'class_attributes' 	=> true,
																	'class_object_name'	=> false,
																	'class_options'		=> false
																),
										'updateClassEz' => array(	'class_id'			=> true,
																	'class_attributes' 	=> true,
																	'class_name' 		=> false,
																	'class_identifier' 	=> false,
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
																),
										'verifyObjectAttributes' => array(	'class_id' 			=> true,
																			'object_attributes' => true
																		),
										'verifyClassAttributes' => array(	'class_id' 			=> true,
																			'class_attributes' => true
																		),
										'verifyClassAttributes' => array(	'class_id' 			=> true,
																			'class_attributes' => true
																		),
										'setObjectRelationByName' => array(	'class_identifier' => true,
																			'content_object' => true
																		),
										'setObjectRelationByIds' => array(	'class_identifier' => true,
																			'content_object' => true
																		),
										'setObjectRelationByRemoteIds' => array('class_identifier' => true,
																				'content_object' => true
																		),
																		
										);
		self::$parameters_per_function = $parameters_per_function;
										
										
		// tableau complet *******
		$definition = array('properties_list' => $properties_list,
							'parameters_per_function' => $parameters_per_function,
							'inidata_list' => $inidata_list
							);
		self::$definition = $definition;
										
		return $definition;
	}
	
	
	
	public static function initLogger()
	{
		if( !is_object(self::$logger) )
			self::$logger = owLogger::CreateForAdd(self::LOGFILE . date("d-m-Y") . ".log");
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
	
	
	public static function lastLogContent()
	{
		self::initLogger();
		return self::$logger->getLogContentFromCurrentStartTime();
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
		self::initLogger();
		
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
				if( $ini->hasVariable($args['block'], $args['var']) )
					self::$inidata[$name] = $ini->variable($args['block'], $args['var']);
				else
					self::$inidata[$name] = false;
			}
			
			// si une des variables n'existe pas on renvoie false et on ecrie dans le $log
			$err = 0;
			foreach( self::$inidata as $k => $var )
			{
				if( $var === false )
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
		self::initLogger();
		
		$db = eZDB::instance();
        $remoteID =$db->escapeString( $remoteID );
        $resultArray = $db->arrayQuery( 'SELECT id FROM ezcontentobject WHERE remote_id=\'' . $remoteID . '\'' );
		
        if ( count( $resultArray ) != 1 )
        {
        	$error = "Object non trouvé avec remote_id : $remoteID ";
			self::$logger->writeTimedString("Erreur objectIDByRemoteID() : " . $error);
			return false;
        } 
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
		self::initLogger();
		
		$db = eZDB::instance();
		settype($id,'int');
        $resultArray = $db->arrayQuery( "SELECT main_node_id FROM ezcontentobject_tree WHERE contentobject_id=" . $id );
		
        if ( count( $resultArray ) == 0 )
        {
        	$error = "Node non trouvé avec object_id : $id ";
			self::$logger->writeTimedString("Erreur objectMainNodeIDByID() : " . $error);
			return false;
        } 
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
		self::initLogger();
        
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
     * fonction pour remplacer tous les character accentué par de non accentué
     */
	public static function unaccent($texte) 
	{
	    $accent='ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËéèêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ';
	    $noaccent='AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn';
	    
		if (mb_detect_encoding($texte, 'UTF-8', true))
		{
			$texte = utf8_decode($texte);
		}
		
	    $texte = strtr($texte,utf8_decode($accent),$noaccent);
	    
	    return $texte;
	}
    
    
	/*
	 * transforme la chaine en parametres selon des regles de normalisation d'un identifier EZ
	 * -- enleve les espaces en début et fin de chaine
	 * -- tout en minuscule
	 * -- remplace les espaces par des underscores
	 * -- self::unaccent()
	 * @param $string string
	 * @return $string string
	 */
	public static function normalizeIdentifier($string)
	{
		// si le parametre n'est pas de type string on ne fait rien
		if(!is_string($string))
			return $string;
		
		// enleve les espaces en début et fin de chaine
		$string = trim($string);
		
		// tout en minuscule
		$string = mb_strtolower($string, 'UTF-8');
		
		// remplace les espaces par des underscores
		$espaces = array(" ", "  ", "   ", "    ", "     "); // de 1 à 5 espaces...
		$string = str_replace($espaces, "_", $string);
		
		$string = self::unaccent($string);
		
		$string = self::limitIdentifier($string);
		
		return $string;
	}
	
	/*
	 * applique la fonction self::normalizeIdentifier($string)
	 * à toutes les clés du tableau donné en parametre
	 * 
	 */
	public static function normalizeIdentifiers($array)
	{
		// si le parametre n'est pas de type array on ne fait rien
		if(!is_array($array))
			return $array;
			
		$normalarray = array();
		foreach($array as $key => $value)
		{
			$normalkey = self::normalizeIdentifier($key);
			if( is_array($value) and isset( $value['identifier'] ))
			{
				$value['identifier'] = $normalkey;
			}
			$normalarray[$normalkey] = $value;
		}
		
		return $normalarray;
	}
	
	// EZ coupe automatiquement un identifiant à 50 caracteres
	public static function limitIdentifier($string)
	{
		// si le parametre n'est pas de type string on ne fait rien
		if(!is_string($string))
			return $string;
			
		$string = substr($string,0,50);
		
		return $string;
	}
	
	public static function limitIdentifiers($array)
	{
		// si le parametre n'est pas de type array on ne fait rien
		if(!is_array($array))
			return $array;
			
		$normalarray = array();
		foreach($array as $key => $value)
		{
			$normalkey = self::limitIdentifier($key);
			if( is_array($value) and isset( $value['identifier'] ))
			{
				$value['identifier'] = $normalkey;
			}
			$normalarray[$normalkey] = $value;
		}
		
		return $normalarray;
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
			{
				$this->properties[$name] = $value;
			}
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
	
	public function getProperty($name)
	{
		if( isset($this->properties[$name]) )
			return $this->properties[$name];
		else
			return null;
	}
	
	public function setProperty($name,$value)
	{
		if(in_array($name,self::$properties_list))
		{
			$this->properties[$name] = $value;
		}
		else
		{	
			$error = "Propriété " . $name . " non trouvé parmi les propriétés d'un objet " . get_class();
			self::$logger->writeTimedString($error);
		}
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
					if(!isset($this->properties[$name]))
					{
						$error = $function_name . " : " . $name . " n'est pas reinsegné !";
						self::$logger->writeTimedString($error);
						return false;
					}
				}
				elseif( isset($args[$name]) )
				{
					// set property
					$this->properties[$name] = $args[$name];
				}
			}
		}
		
		return true;
		
	}
	

	
	public function createClassGroup($name)
	{	
		$classGroup = eZContentClassGroup::fetchByName($name);
		if( !is_null($classGroup) )
		{
			$warning = "Un groupe de classes avec le nom:$name existe déjà";
			self::$logger->writeTimedString($warning);
			return $classGroup;
		}
		
		$classGroup = eZContentClassGroup::create(self::$inidata['AdminID']);
		$classGroup->setAttribute('name',$name);
		$classGroup->store();
		
		return $classGroup;
	}
	
	
	/*
	 * $class_attributes[identifier(string)] = array( name=>(string),datatype=>(string),required=>(bool) );
	 */
	public function createClassEz($args = null)
	{
		//exit("createClassEz stopped!");
		
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("createClassEz", $args);
		if(!$verify)
			return false;
		
		// crée une nouvelle class EZ
		$userID = self::$inidata['AdminID'];
		$newClass =  eZContentClass::create( $userID );
		
		// identifier de la class
		// si le class_identifier n'est pas definie il est crée par la méthode 'self::normalizeIdentifier'
		// sur la base du nom de la class
		if(!isset($this->properties['class_identifier']))
			$this->properties['class_identifier'] = self::normalizeIdentifier($this->properties['class_name']);
		
		// modele de nom pour les objets
		$contentobject_name = ( isset($this->properties['class_object_name']) )? $this->properties['class_object_name'] : "new " . $this->properties['class_name'];
		
		// is_container ( 1 par default )
		if( isset($this->properties['class_options']['is_container']) )
			$is_container =  (int)$this->properties['class_options']['is_container'];
		elseif( isset(self::$inidata['ClassIsContainer'][$this->properties['class_identifier']]) )
			$is_container = (int)self::$inidata['ClassIsContainer'][$this->properties['class_identifier']];
		elseif( isset(self::$inidata['DefaultClassIsContainer']) )
			$is_container = (int)self::$inidata['DefaultClassIsContainer'];
		else
			$is_container = 1;
		
		$newClass->setAttribute( 'version', 0);
		$newClass->setAttribute( 'name', $this->properties['class_name']);
		$newClass->setAttribute( 'identifier', $this->properties['class_identifier'] );
		$newClass->setAttribute( 'is_container', $is_container );
		$newClass->setAttribute( 'contentobject_name', "<" . $contentobject_name . ">" );
		 
		$newClass->store();
		
		// id de la class
		$ClassID = $newClass->ID;
		$this->properties['class_id'] = $ClassID;
		// version de la class
		$ClassVersion = $newClass->attribute( 'version' );
		
		// class_group ( "Content" par default )
		if( isset($this->properties['class_options']['class_group']) )
			$classGroup_name =  $this->properties['class_options']['class_group'];
		elseif( isset(self::$inidata['ClassGroup'][$this->properties['class_identifier']]) )
			$classGroup_name = self::$inidata['ClassGroup'][$this->properties['class_identifier']];
		elseif( isset(self::$inidata['DefaultClassGroup']) )
			$classGroup_name = self::$inidata['DefaultClassGroup'];
		else
			$classGroup_name = "Content";

		$classGroup = eZContentClassGroup::fetchByName($classGroup_name);
		
		if( is_null($classGroup) )
		{
			$warning = "Le nom de groupe de classes donné ($classGroup_name) n'existe pas !";
			self::$logger->writeTimedString($warning);
			$classGroup_name = "Content";
			$classGroup_ID = 1;
		}
		else
			$classGroup_ID = $classGroup->attribute('id');
			
		$ingroup = eZContentClassClassGroup::create($ClassID, $ClassVersion, $classGroup_ID, $classGroup_name);
		$ingroup->store();
		
		// crée les attributs de la class EZ
		// $class_attributes[identifier(string)] = array( name=>(string),datatype=>(string),required=>(bool),[is_searchable=>(int),can_translate=>(int)] );
		foreach( $this->properties['class_attributes'] as $identifier => $attrs )
		{
			
			$this->createClassAttribute($attrs,$ClassVersion);
			
		}
		
		return true;
			
	}
	
	
	// @TODO : upadate de la class
	public function updateClassEz($args = null)
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("updateClassEz", $args);
		if(!$verify)
			return false;
			
		$this->properties['class_attributes'];
			
	}
	
	
	protected function initDatatype($new_attribute,$attr)
	{	
		if( $attr['datatype'] == "ezselection" )
		{
			if( isset($attr['multi']) && $attr['multi'] == 1 )
				$isMultiple = 1;
			else
				$isMultiple = 0;
			
			$new_attribute->setAttribute( 'data_int1', $isMultiple );
			
			$doc = new DOMDocument( '1.0', 'utf-8' );
			$root = $doc->createElement( "ezselection" );
			$doc->appendChild( $root );
			
			$options = $doc->createElement( "options" );
			
			$root->appendChild( $options );

			foreach ( $attr['options'] as $option )
			{
			    $optionNode = $doc->createElement( "option" );
			    $optionNode->setAttribute( 'id', $option['id'] );
			    $optionNode->setAttribute( 'name', $option['name'] );
			
			    $options->appendChild( $optionNode );
			}
			
			$xml = $doc->saveXML();
			
			$new_attribute->setAttribute( 'data_text5', $xml );
			$new_attribute->setAttribute( 'data_type_string', 'ezselection' );
		}
		
		
		
		return $new_attribute;
	}
	
	
	/*
	 * createClassAttribute
	 * @!! attention quand on appele cette fonction il faut que certains proprieté soient reinsegnées !!!
	 * 
	 * @param $attr array('identifier','name','datatype','required','is_searchable'=1,'can_translate'=1)
	 */
	public function createClassAttribute($attr,$ClassVersion = 0)
	{
		// create attribute de type $attrs['datatype']
		$new_attribute = eZContentClassAttribute::create( $this->properties['class_id'], $attr['datatype'] );
		
		$new_attribute = $this->initDatatype($new_attribute,$attr);
		
		// version
		$new_attribute->setAttribute( 'version', $ClassVersion);
		// name
		$new_attribute->setAttribute( 'name', $attr['name'] );
		// is_required
		$new_attribute->setAttribute( 'is_required', $attr['required'] );
		// is_searchable
		$is_searchable = ( isset($attrs['is_searchable']) )? (int)$attr['is_searchable'] : (int)self::$inidata['DefaultIsSearchable'];
		$new_attribute->setAttribute( 'is_searchable', $is_searchable );
		// can_translate
		$can_translate = ( isset($attrs['can_translate']) )? (int)$attr['can_translate'] : (int)self::$inidata['DefaultCanTranslate'];
		$new_attribute->setAttribute( 'can_translate', $can_translate );
		// identifier
		$new_attribute->setAttribute( 'identifier', $attr['identifier'] );
		
		// initialise datatype
		$dataType = $new_attribute->dataType();
		$dataType->initializeClassAttribute( $new_attribute );
		// store attribute
		$new_attribute->store();
		
		return $new_attribute;

	}
	
	
	/*
	 * Verify la coherance entre le tableu $this->properties['class_attributes'] et la structure de la class 
	 * 
	 */
	protected function verifyClassAttributes($args = null)
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("verifyClassAttributes", $args);
		if(!$verify)
			return false;
		
		if(!eZContentClass::exists($this->properties['class_id']))
			return false;
		
		$class = eZContentClass::fetch($this->properties['class_id']);
		
		$unrecognized = array();
		
		foreach($this->properties['class_attributes'] as $attr => $value)
		{
			if( is_null( $class->fetchAttributeByIdentifier($attr,false) ) )
			{
				$error[] = "verifyClassAttributes() : " . $attr . " non trouvé parmi les attributes de la class " . $class->attribute('identifier');
			}
		}
		
		if(isset($error))
		{
			self::$logger->writeTimedString($error);
			return false;
		}
		
		return true;
	}
	
	
	/*
	 * @TODO : verifier la coherance de la valeur de l'attribut avec la valeur attende par la mèthode fromString() du datatype 
	 */
	protected function verifyObjectAttributes($args = null)
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("verifyObjectAttributes", $args);
		if(!$verify)
			return false;
		
		if(!eZContentClass::exists($this->properties['class_id']))
			return false;
		
		$class = eZContentClass::fetch($this->properties['class_id']);
		
		foreach($this->properties['object_attributes'] as $attr => $value)
		{
			$attribute = $class->fetchAttributeByIdentifier($attr,false);
			
			if( is_null( $attribute ) )
			{
				$error = "verifyObjectAttributes() : " . $attr . " non trouvé parmi les attributes de la class " . $class->attribute('identifier');
				self::$logger->writeTimedString($error);
				return false;
			}
			
			if( $attribute['data_type_string'] == "ezboolean" || $attribute['data_type_string'] == "ezinteger" )
			{
				if(is_null($value) || empty($value) )
				{
					$this->properties['object_attributes'][$attr] = "0";
					//evd($this->properties['object_attributes'][$attr]);
				}
					
			}
				
		}
		
		return true;
	}
	
	public function createObjectEz($args = null)
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("createObjectEz", $args);
		if(!$verify)
			return false;
	
		// verifie si le tableau des attributes de l'objet est coherant aux attributes de la class
		$verify = $this->verifyObjectAttributes();
		if(!$verify)
			return false;
		
		//evd($this->properties['class_attributes']);
			
		// retrouve l'identifiant de la class si n'est pas déjà dans les propriété de l'instance
		if( !isset($this->properties['class_identifier']) )
			$this->properties['class_identifier'] = eZContentClass::classIdentifierByID($this->properties['class_id']);
		
		// retrouve le parent_node_id pour l'objet
		if( isset(self::$inidata['ClassParentNodeID'][$this->properties['class_identifier']]) )
			$parent_node_id = self::$inidata['ClassParentNodeID'][$this->properties['class_identifier']];
		else
			$parent_node_id = self::$inidata['DefaultParentNodeID'];
			
    	$params = array();
		$params['class_identifier'] = $this->properties['class_identifier']; 
		$params['creator_id'] = self::$inidata['AdminID'];
		$params['parent_node_id'] = $parent_node_id;
		$params['section_id'] = self::$inidata['DefaultSectionID'];
		if(isset($this->properties['object_remote_id']))
			$params['remote_id'] = $this->properties['object_remote_id'];
     	$params['attributes'] = $this->properties['object_attributes'];
		
		// PUBLISH OBJECT
		$contentObject = eZContentFunctions::createAndPublishObject( $params ); //evd($contentObject);
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
		$verify = $this->verifyObjectAttributes();
		if(!$verify)
			return false;
			
		// retrouve l'identifiant de la class si n'est pas déjà dans les propriété de l'instance
		if( !isset($this->properties['class_identifier']) )
			$this->properties['class_identifier'] = eZContentClass::classIdentifierByID($this->properties['class_id']);
			
			
		$params = array();
		$params['class_identifier'] = $this->properties['class_identifier']; 
		$params['creator_id'] = self::$inidata['AdminID'];
		$params['parent_node_id'] = self::$inidata['DefaultParentNodeID'];
		$params['section_id'] = self::$inidata['DefaultSectionID'];
     	$params['attributes'] = $this->properties['object_attributes'];
		
     	
		// PUBLISH OBJECT
		$contentObject = self::updateAndPublishObject( $this->properties['content_object'], $params );
		if(!$contentObject)
			return false;
			
		// renomme l'objet si un nom est indiqué
		if(isset($this->properties['object_name']))
			$this->properties['content_object']->rename($this->properties['object_name']);
		
			
		return true;
	}
	
	
	/*
	 * 
	 */
	public function setObjectRelationByName( $args = null, $related_class_identifier, $related_name, $attr_name )
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("setObjectRelationByName", $args);
		if(!$verify)
			return false;
		
		$related_class_id = eZContentClass::classIDByIdentifier($related_class_identifier);
		
		if( !$related_class_id )
		{
			self::$logger->writeTimedString("class avec identifier $related_class_identifier non trouvé.");
			return false;
		}
		else
		{
			$object_search = eZContentObjectTreeNode::subTreeByNodeID( array( 	'Depth' => 1,
																				'ClassFilterType' => 'include',
																				'ClassFilterArray' => array($related_class_identifier),
																				'AttributeFilter' => array(array("$related_class_identifier/$attr_name", '=', $related_name) ),
																				),
																			     self::$inidata['ClassParentNodeID'][$related_class_identifier] );
		
			if( count($object_search) == 0 )
			{
				self::$logger->writeTimedString("Acun objet trouvé avec le nom : $related_name.");
				return false;
			}
			else
			{
				$related_object_id = $object_search[0]->attribute('contentobject_id');
				
				$relatedObjectsList = $this->properties['content_object']->relatedContentObjectList();
				
				if( count($relatedObjectsList) != 0 )
				{
					foreach($relatedObjectsList as $relatedObject)
					{
						if( $relatedObject->ID == $related_object_id )
						{
							self::$logger->writeTimedString("L'objet avec ID $related_object_id est déjà en relation avec l'objet avec ID : " . $this->properties['content_object']->ID);
							return false;
						}
					}
				}
			}
			

			$addObjectRelation = $this->properties['content_object']->addContentObjectRelation($related_object_id);
			$notice['addContentObjectRelation'] = $addObjectRelation;
			if( $addObjectRelation !== false )
				return true;

			
		}
			
	}
	
	
	public function setObjectRelationByIds( $args = null, $related_class_identifier, $related_ids )
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("setObjectRelationByIds", $args);
		if(!$verify)
			return false;
		
		$related_class_id = eZContentClass::classIDByIdentifier($related_class_identifier);
		
		if( !$related_class_id )
		{
			self::$logger->writeTimedString("class avec identifier $related_class_identifier non trouvé.");
			return false;
		}
		else
		{
			foreach( $related_ids as $key => $value )
			{
				$addObjectRelation = $this->properties['content_object']->addContentObjectRelation($value['id']);
				if( $addObjectRelation !== false )
					$result[$value['id']] = true;
				else
					$result[$value['id']] = false;
			}	
	
			return $result;
		}
	}
	
	
	public function setObjectRelationByRemoteIds( $args = null, $related_class_identifier, $related_ids )
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("setObjectRelationByRemoteIds", $args);
		if(!$verify)
			return false;
		
		$related_class_id = eZContentClass::classIDByIdentifier($related_class_identifier);
		
		if( !$related_class_id )
		{
			self::$logger->writeTimedString("class avec identifier $related_class_identifier non trouvé.");
			return false;
		}
		else
		{
			foreach( $related_ids as $remote_id )
			{
				$object_id = self::objectIDByRemoteID($remote_id);
				if( !$object_id )
					$result[$remote_id] = false;
				else
				{
					$addObjectRelation = $this->properties['content_object']->addContentObjectRelation($object_id);
					if( $addObjectRelation !== false )
						$result[$remote_id] = true;
					else
						$result[$remote_id] = false;
				}
				
			}	
	
			return $result;
		}
	}
	
	
	
	
} // fin de class

?>
