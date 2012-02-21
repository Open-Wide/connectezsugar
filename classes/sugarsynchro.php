<?php

class SugarSynchro
{
	/*
	 * CONSTANTES
	 */
	const LOGFILE = "var/log/SugarSynchro.log";
	const INIPATH = "extension/connectezsugar/settings/";
	const INIFILE = "sugarcrm.ini.append.php";
	const MAPPINGINIFILE = "mappingezsugar.ini.append.php";
	
	/*
	 * PROPRIÉTÉS
	 */
	// STATIQUES
	private static $definition = array();
	private static $properties_list;
	private static $parameters_per_function;
	private static $inidata_list;
	protected static $inidata = array();
	protected static $mappingdata_list;
	protected static $logger;
	
	// D'INSTANCE
	protected $properties = array();
	protected $sugarConnector;
	protected $sugar_session;
	protected $mappingdata = array();
	
	
	/*
	 * MÉTHODES STATIQUES
	 */
	
	/*
	 * instancie un nouveau objet de cette class
	 * @return object[SugarSynchro]
	 */
	public static function instance($properties = array())
	{
		self::$logger = owLogger::CreateForAdd(self::LOGFILE);
		self::definition();
		self::getIniData();
		
		$instance = new SugarSynchro();
		
		if(count($properties) > 0)
		{
			$instance->init($properties);
		}
		
		// connexion à SUGAR
		// @TODO : user et mdp pour login sont ecrites en clais dans le fichier sugarcrm.ini
		// chercher une autre façon de stockage plus securisé ?
		$instance->sugarConnector = new SugarConnector();
		$instance->sugar_session = $instance->sugarConnector->login();
		
		return $instance;
	}
	
	public static function definition()
	{
		// inidata_list ***
		$inidata_list = array(	'mapping_names'			=> array( 'block' => "Mapping", 'var' => "mapping_names" ),
								'mapping_identifiers'	=> array( 'block' => "Mapping", 'var' => "mapping_identifiers" ),
								'exclude_fields'		=> array( 'block' => "Mapping", 'var' => "exclude_fields" ),
								'mapping_types'			=> array( 'block' => "Mapping", 'var' => "mapping_types" ),
								'prefixRemove'			=> array( 'block' => "Names", 'var' => "prefixRemove" ),
								'prefixString'			=> array( 'block' => "Names", 'var' => "prefixString" ),
								'modulesListToSynchro'	=> array( 'block' => "Synchro", 'var' => "modulesListToSynchro" ),
							); 
		self::$inidata_list = $inidata_list;
		
		// $mappingdata_list ***
		$mappingdata_list = array(	'sugarez'			=> array( 'var' => "sugarez" ),
									'ezsugar_rename'	=> array( 'var' => "ezsugar_rename" ),
									'exclude_fields'	=> array( 'var' => "exclude_fields" ),
									'include_fields'	=> array( 'var' => "include_fields" ),
									'translate_fields'	=> array( 'var' => "translate_fields" ),
							); 
		self::$mappingdata_list = $mappingdata_list;
		
		// properties_list ***
		$properties_list = array(	'sugar_module',
									'sugar_id',
									'sugar_attributes',
									'sugar_attributes_values',
									'sugar_module_fields',
									'class_id',
									'class_name',
									'class_identifier',
									'class_attributes'
								);
		self::$properties_list = $properties_list;
		
		// parameters_per_function *** 
		$parameters_per_function = array(	'getSugarFields' 		=> array(	'sugar_module' 	=> true
																		),
											'getSugarFieldsValues' => array(	'sugar_module' 		=> true,
																				'sugar_id'			=> true,
																				'sugar_attributes' 	=> true
																		),
											'synchronizeFieldsNames' => array(	'sugar_module' 	=> true
																		),
											'verifyClassAttributes' => array(	'class_id' 			=> true,
																				'class_attributes' 	=> true,
																				'sugar_module'		=> true
																		)
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
				if( $ini->hasVariable($args['block'], $args['var']) )
					self::$inidata[$name] = $ini->variable($args['block'], $args['var']);
				else
					self::$inidata[$name] = false;
			}
			
			// si une des variables n'existe pas on renvoie false et on ecrie dans le $log
			$err = 0;
			foreach( self::$inidata as $k => $var )
			{
				if( !$var )
				{
					$error = "la variable demandées : " . $k . ", n'existe pas !";
					self::$logger->writeTimedString("Erreur getIniData() : " . $error);
					$err++;
				}
			}
			
			//exit(var_dump(self::$inidata));
			
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
	
	
	public static function getModuleListToSynchro()
	{
		if( !isset(self::$inidata) or count(self::$inidata == 0) )
			self::getIniData();
			
		return self::$inidata['modulesListToSynchro'];
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
		return self::$logger->getLogContentFromCurrentStartTime();
	}
	
	
	/*
	 * CONSTRUCTEUR
	*/
	
	protected function SugarSynchro()
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
					$this->properties[$name] = $args[$name]; //var_dump($this->properties);
				}
			}
		}
		
		return true;
		
	}
	
	
	
	public function defClassName($module_name)
	{
		if(isset(self::$inidata['mapping_names'][$module_name]))
		{
			$class_name = self::$inidata['mapping_names'][$module_name];
		}
		elseif(self::$inidata['prefixRemove'] == "true" and strpos($module_name, self::$inidata['prefixString']) !== false )
		{
			$prefixlen = strlen(self::$inidata['prefixString']);
			$class_name = substr($module_name,$prefixlen);
		}
		else
			$class_name = $module_name;
		
		$this->properties['class_name'] = $class_name;
		
		return $class_name;
	}
	
	public function defClassIdentifier($module_name)
	{
		if(isset(self::$inidata['mapping_identifiers'][$module_name]))
		{
			$class_identifier = self::$inidata['mapping_identifiers'][$module_name];
		}
		elseif(isset($this->properties['class_name']))
		{
			$class_identifier = owObjectsMaster::normalizeIdentifier($this->properties['class_name']);
		}
		elseif(self::$inidata['prefixRemove'] == "true" and strpos($module_name, self::$inidata['prefixString']) !== false )
		{
			$prefixlen = strlen(self::$inidata['prefixString']);
			$class_identifier = substr($module_name,$prefixlen);
		}
		else
			$class_identifier = owObjectsMaster::normalizeIdentifier($module_name);
		
		$this->properties['class_identifier'] = $class_identifier;
		
		return $class_identifier;
	}
	
	
	
	/*
	 * get du mapping specifique au module
	 */
	public function getMappingDataForModule($module_name = null)
	{
		if( is_null($module_name)  )
		{
			if( isset($this->properties['sugar_module']) )
				$module_name = $this->properties['sugar_module'];
			else
			{
				$error = "ni le parametre \$module_name, ni \$this->properties['sugar_module'] sont reisegnés !";
				self::$logger->writeTimedString("Erreur getMappingDataForModule() : " . $error);
				$this->mappingdata = false;
				return false;
			}
		}
		
		// verifie si self::MAPPINGINIFILE existe dans self::INIPATH
	   	$initest = eZINI::exists(self::MAPPINGINIFILE, self::INIPATH);
		if($initest)
		{
			$inimap = eZINI::instance(self::MAPPINGINIFILE, self::INIPATH);
			
			if( !$inimap->hasGroup($module_name) )
			{
				$warning = "Pas de block " . $module_name . " trouvé dans " . self::MAPPINGINIFILE;
				self::$logger->writeTimedString("Warning getMappingDataForModule() : " . $warning);
				$this->mappingdata = false;
				return false;	
			}
			
			// recupere toutes les variables du fichier ini definie dans self::$mappingdata_list 
			foreach(self::$mappingdata_list as $name => $args)
			{
				if( $inimap->hasVariable($module_name, $args['var']) )
					$this->mappingdata[$name] = $inimap->variable($module_name, $args['var']);
				else
					$this->mappingdata[$name] = false;
			}
			
			$wrn = 0;
			// si une des variables n'existe on ecrie dans le $log un warning
			foreach( $this->mappingdata as $k => $var )
			{
				if( !$var )
				{
					$notice = "Pour le module " . $module_name . " la variable " . $k . ", n'est pas definie !";
					self::$logger->writeTimedString("Notice getMappingDataForModule() : " . $notice);
					$wrn++;
				}
			}
			
			if( $wrn >= count(self::$mappingdata_list) )
			{
				$this->mappingdata = false;
				return false;
			}
				
			return true;
			
		}
		else
		{
			$error = self::MAPPINGINIFILE . " IN " . self::INIPATH . " NON TROUVÉ !";
			self::$logger->writeTimedString("Erreur getMappingDataForModule() : " . $error);
			$this->mappingdata = false;
			return false;
		}
	}
	
	
	
	protected function testForMapping($module_name = null)
	{
		if(is_array($this->mappingdata) and  count($this->mappingdata) == 0)
		{
			//echo("rentre ici ");
			$testmapping = $this->getMappingDataForModule($module_name);
			//var_dump($testmapping);
		}
		else
			$testmapping = $this->mappingdata;
			
		
		return $testmapping;
	}
	
	/*
	 * A) va chercher le mapping pour le module concerné;
	 * B) applique des filtres
	 * 1er filtre :
	 * - exclude les champs SUGAR qui sont listé dans le tableau exclude_fileds[] generique pour tous les modules
	 * 2eme filtre :
	 * - include seulement les champs listé dans include_fields si defini
	 * sinon
	 * - exclude les champs listé dans exclude_fields si defini;
	 * C) definie le tableau $this->properties['sugar_attributes'].
	 * 
	 */
	protected function filterSugarFields()
	{
		$testmapping = $this->testForMapping();
		
		foreach($this->properties['sugar_module_fields'] as $modulefield)
		{
			$setAttribute = false;
			// exclude les champs listé dans 'exlude_fields' dans 'sugarcrm.ini'
			// ( exclude_fields generique pour tous les modules )
			if( !in_array($modulefield['name'], self::$inidata['exclude_fields']) )
			{
				if($testmapping)
				{	// si include_fields[] et exclude_fields[] sont definie : include_fields a la priorité
					// si 'include_fields[]' est definie pour le module seulement ces champs sont inclues
					if( is_array($this->mappingdata['include_fields']) )
					{
						if( in_array($modulefield['name'], $this->mappingdata['include_fields']) )
							$setAttribute = true;
					}
					// sinon si 'exclude_fields[]' est definie pour le module ces champs sont exclues
					elseif( is_array($this->mappingdata['exclude_fields']) )
					{
						if( !in_array($modulefield['name'], $this->mappingdata['exclude_fields']) )
							$setAttribute = true;
					}
				}
				else
					$setAttribute = true;
			}
			
			if($setAttribute)
			{
				$testSetSugarAttribute = $this->setSugarAttribute($modulefield);
				// si il y a une erreur dans setSugarAttribute() return false
				if(!$testSetSugarAttribute)
					return false;
			}
				
		}
		
		return true;
	}
	
	/* 
	 * definie un element du tableau $this->properties['sugar_attributes']
	 * avec les donnée d'un champ de module SUGAR
	 * formaté pour être enregistré sous EZ
	 * 
	 * @param $modulefield array ( tableau retourné par 'get_module_fields' => 'module_fields' )
	 * @return boolean
	 */
	protected function setSugarAttribute($modulefield)
	{
		// si le type du champ SUGAR n'est pas dans la liste des datatypes (self::$inidata['mapping_types'])
		// ecrit l'erreur dans le log et return false
		if( !isset(self::$inidata['mapping_types'][$modulefield['type']]) )
		{
			$error = $modulefield['type'] . " non trouvé dans la liste mapping_types[] in " . self::INIFILE;
			self::$logger->writeTimedString("Erreur setSugarAttribute() : " . $error);
			return false;
		}
		
		$this->properties['sugar_attributes'][$modulefield['name']] = array('identifier'=> $modulefield['name'],
																			'name' 		=> $modulefield['label'],
																			'datatype'	=> self::$inidata['mapping_types'][$modulefield['type']],
																			'required'	=> (int)$modulefield['required']
																			);
																			
		$testmapping = $this->testForMapping();
		if( $testmapping and isset($this->mappingdata['translate_fields'][$modulefield['name']]) )
		{
			$this->properties['sugar_attributes'][$modulefield['name']]['can_translate'] = $this->mappingdata['translate_fields'][$modulefield['name']];
		}
		
		return true;
	} 
	
	/*
	 * fait une requete pour obtenir les champs d'un module SUGAR,
	 * filtre les champs selon la configuration general et specifique au module,
	 * retourne le tableau filtré $this->properties['sugar_attributes']
	 * 
	 * @param $args array ( voir definition() )
	 * @return $this->properties['sugar_attributes'] array
	 */
	public function getSugarFields($args = null)
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("getSugarFields", $args);
		if(!$verify)
			return false;
		
		$sugardata = $this->sugarConnector->get_module_fields($this->properties['sugar_module']);
		$module_fields = $sugardata['module_fields'];
		$this->properties['sugar_module_fields'] = $module_fields;
		
		// filtre les champs selon la configuration general et specifique au module
		$testFilterSugarFields = $this->filterSugarFields();
		if(!$testFilterSugarFields)
		{
			//exit(var_dump(self::$logger->getLogContentFromCurrentStartTime()));
			return false;
		}
			
		
		return $this->properties['sugar_attributes'];
		
	}
	
	/*
	 * ex.: $attributes_values = array('attr_1' => 'test attr 1', 'attr_2' => 'test attr 2');
	 */
	public function getSugarFieldsValues($args = null)
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("getSugarFieldsValues", $args);
		if(!$verify)
			return false;
			
		$select_fields = array_keys($this->properties['sugar_attributes']);
		$sugardata = $this->sugarConnector->get_entry($this->properties['sugar_module'], $this->properties['sugar_id'], $select_fields);
		
		$entry_list = $sugardata['entry_list'];
		$name_value_list = $entry_list[0]['name_value_list'];
		
		$attributes_values = array();
		foreach($name_value_list as $item)
		{
			$attributes_values[$item['name']] = html_entity_decode($item['value'], ENT_QUOTES, 'UTF-8');
		}
		
		$this->properties['sugar_attributes_values'] = $attributes_values;
		return $this->properties['sugar_attributes_values'];
		
	}
	
	/*
	 * synchronizeFieldsValues
	 * 
	 * @param $input_array array
	 * @return $output_array array OR false
	 */
	public function synchronizeFieldsNames($input_array)
	{
		// si $input_array n'est pas un tableau on ne peut pas proceder au traitement
		if(!is_array($input_array))
		{
			$error = "synchronizeFieldsNames : \$input_array n'est pas un tableau mais : " . gettype($input_array);
			self::$logger->writeTimedString($error);
			return false;
		}
		
		// il faut que la propriété 'sugar_module' soit reinsegné !
		if( !isset($this->properties['sugar_module']) )
		{
			$error = "synchronizeFieldsNames : \$this->properties['sugar_module'] n'est pas reinsegné !";
			self::$logger->writeTimedString($error);
			return false;
		}

		// init $output_array
		$output_array = array();
		
		// si un mapping de correspondences de noms d'attributes existe pour le module on le recupere et
		// construit le tableau de sortie en nommant les attributs selon le mapping
		if($this->checkMappingForModule($this->properties['sugar_module']))
		{
			foreach( $input_array as $name => $values )
			{
				if(isset($this->mappingdata['sugarez'][$name]))
					$attr_identifier = $this->mappingdata['sugarez'][$name];
				// OPTION 2 : ne change pas l'identifier de l'attribut
				else
					$attr_identifier = $name;
				// OPTION 1 : normalise les identifiers
				//else 
					//$attr_identifier = owObjectsMaster::normalizeIdentifier( $name );
				
				$output_array[$attr_identifier] = $values;
			}
		}
		else
		{
			// OPTION 1 : definie $object_attributes après avoir normalisé les identifiants
			//$output_array = owObjectsMaster::normalizeIdentifiers( $input_array );
			
			// OPTION 2 : ne chnage rien au tableau
			$output_array = $input_array;
		}
		
		//exit(var_dump($output_array));
		return $output_array;
	}
	
	/*
	 * Verifie si un mapping de correspondences de noms d'attributes existe pour le module
	 */
	protected function checkMappingForModule($module_name)
	{
		$testmapping = $this->testForMapping($module_name);
		
		if( $testmapping && isset($this->mappingdata['sugarez']) && is_array($this->mappingdata['sugarez']) && count($this->mappingdata['sugarez']) > 0 )
			return true;
		else
			return false;
		
	}
	
	
	
	/*
	 * Verify la coherance entre le tableu $this->properties['class_attributes'] et la structure de la class EZ
	 * @TODO : verifier la coherance de la valeur de l'attribut avec la valeur attende par la mèthode fromString() du datatype 
	 * @param $args (voir self::definition())
	 * @return boolean -> si tout va bien ou si il y a un erreur qui empeche le deroulement de la fonction
	 * @return $changes array -> si des attributes dans le tableau en entrée $this->properties['class_attributes'] ne sont pas trouvé parmi les attributes de la class EZ
	 */
	public function verifyClassAttributes($args = null)
	{
		// verifie si la fonction a les parametres necessaires à son execution
		$verify = $this->verifyArgsForFunction("verifyClassAttributes", $args);
		if(!$verify)
			return false;
		
		if(!eZContentClass::exists($this->properties['class_id']))
			return false;
		
		$class = eZContentClass::fetch($this->properties['class_id']);
		$data_map = $class->dataMap();
		
		$changes = array();
		
		foreach($this->properties['class_attributes'] as $attr => $value)
		{
			if( is_null( $class->fetchAttributeByIdentifier($attr,false) ) )
			{
				$error[] = "ERROR verifyClassAttributes() : " . $attr . " non trouvé parmi les attributes de la class " . $class->attribute('identifier');
				$changes[$attr] = $value;
			}
		}
		
		foreach( $data_map as $identifier => $class_attr )
		{
			if( !array_key_exists($identifier, $this->properties['class_attributes']) )
			{
				$alert[] = "ALERTE verifyClassAttributes() : " . $identifier . " non trouvé parmi les attributes du module SUGAR " . $this->properties['sugar_module'];
			}
		}
		
		if(isset($alert))
			self::$logger->writeTimedString($alert);
		
		if(isset($error))
		{
			self::$logger->writeTimedString($error);
			return $changes;
		}
		
		return true;
	}
	
	
	
}// fin de class

?>
