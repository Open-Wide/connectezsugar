<?php
/*
Cronjob pour la synchronisation des objets EZ depuis SUGAR

@author Pasquesi Massimiliano <massipasquesi@gmail.com>
*/

// fonction pour afficher les variable en console
function show($var)
{
	$show = print_r($var,true);
	return $show; 
}

// encoding
//iconv_set_encoding("output_encoding", "UTF-8");
//iconv_set_encoding("internal_encoding", "UTF-8");
//iconv_set_encoding("input_encoding", "UTF-8");

// init du logger
$logger = owLogger::CreateForAdd("var/log/synchronize_ezsugar_" . date("d-m-Y") . ".log");

// init CLI
$cli = SmartCLI::instance();
/*
$ts = $cli->terminalStyles();
$cli->output(show($ts));
exit();
*/
$cli->setIsQuiet(false);

// debut du script
$cli->beginout("synchronize_ezsugar.php");


// connexion à SUGAR
$sugarConnector=new SugarConnector();
$connection=$sugarConnector->login();
 


// module SUGAR
$modules_list = SugarSynchro::getModuleListToSynchro();

foreach($modules_list as $sugarmodule)
{
	// initialise les tableaux $ez_properties et $sugar_properties
	$ez_properties = array();
	$sugar_properties = array();
	
	// instance de la class SugarSynchro
	$sugarSynchro = SugarSynchro::instance();
	
	// definie le nom de la class EZ
	$class_name = $sugarSynchro->defClassName($sugarmodule);
	// definie l'identifier de la class EZ
	$class_identifier = $sugarSynchro->defClassIdentifier($sugarmodule);
	
	// reinsegne la propriété 'class_name' 'class_identifier'
	$ez_properties['class_name'] = $class_name;
	$ez_properties['class_identifier'] = $class_identifier;
	
	// reinsegne la propriété 'sugar_module'
	$sugar_properties['sugar_module'] = $sugarmodule;
	
	// get des attributes de la table sugar à synchroniser
	// ex.: $sugar_attributes = array(	'attr_1' => array( 'name' => 'Attr 1', 'datatype' => 'ezstring', 'required' => 1 ),
	//									'attr_2' => array( 'name' => 'Attr 2', 'datatype' => 'eztext', 'required' => 0 ) );
	$sugar_attributes = $sugarSynchro->getSugarFields($sugar_properties); //var_dump($sugar_attributes);
	if(!$sugar_attributes)
		$cli->error("\$sugarSynchro->getSugarFields(\$sugar_properties) return false !!!");
	
	// reinsegne la propriété 'class_attributes' après avoir verifié les settings pour le module
	$class_attributes = $sugarSynchro->synchronizeFieldsNames($sugar_attributes); //var_dump($class_attributes);
	if(!$class_attributes)
		$cli->error("\$sugarSynchro->synchronizeFieldsNames(\$sugar_attributes) return false !!!");
			
	if( is_array($sugar_attributes) and is_array($class_attributes) )
		$continue = true;
	else
	{
		$cli->warning("SugarSynchro.log");
		$cli->dgnotice( show(SugarSynchro::lastLogContent()) );
	}
	
	
	if($continue)
	{
		// OPTION 1 : definie $class_attributes après avoir normalisé les identifiants
		//$class_attributes = owObjectsMaster::normalizeIdentifiers($sugar_attributes);
		// OPTION 2 : ne change rien au tableau
		$ez_properties['class_attributes'] = $class_attributes;
		
		// nouvelle instance de owObjectsMaster()
		$objectsMaster = owObjectsMaster::instance($ez_properties);
		if( !is_object($objectsMaster) )
		{
			$cli->error("owObjectsMaster::instance ne renvoie pas un objet");
			$cli->warning("owObjectsMaster.log");
			$cli->dgnotice( show(owObjectsMaster::lastLogContent()) );
			$continue = false;
		}
	}
	
	
	if($continue)
	{
		$ezclassID = eZContentClass::classIDByIdentifier($class_identifier);
		if(!$ezclassID)
		{
			// debug notice
			$cli->gnotice("class de contenu EZ " . $class_identifier . " non trouvé.\n Procede à la creation de la class.");
			
			// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
			// CRÉE UNE NOUVELLE CLASS EZ ***
			// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
			
			// reinsegne la propriété 'class_object_name' si un modele utilisable existe
			if( array_key_exists('name', $class_attributes) )
				$ez_properties['class_object_name'] = "name";
			else
			{
				$ezstringsattr = array();
				foreach( $class_attributes as $key => $attr )
				{
					if( $attr['datatype'] == "ezstring" )
						$ezstringsattr[] = $key;
				}
				
				if(count($ezstringsattr) > 0)
					$ez_properties['class_object_name'] = $ezstringsattr[0];
			}
			
			$objectsMaster->setProperty('class_object_name',$ez_properties['class_object_name']);
			
			//debug notice
			$cli->emptyline();
			$cli->gnotice("objectsMaster->getProperties()");
			$cli->dgnotice(show($objectsMaster->getProperties()));
			
			// crée la nouvelle class de contenu EZ
			$createClass = $objectsMaster->createClassEz($ez_properties);
			if($createClass)
			{
				// get de l'id de la class crée
				$ezclassID = eZContentClass::classIDByIdentifier($class_identifier);
				// reinsegne la propriété 'class_id'
				$ez_properties['class_id'] = $ezclassID;
				// debug notice
				$cli->gnotice("La class " . $class_identifier . " a été creé avec succes!");
				$cli->gnotice("ezclassID : ". $ezclassID);
				// continue l'execution du script
				$continue = true;
				//$continue = false;
			}	
			else
			{
				$cli->error(printf("createClassEz() return %s", show($createClass)));
				$continue = false;
			}
			
			$cli->emptyline();
		}
		else
		{	
			// debug notice
			$cli->gnotice("ezclassID : ". $ezclassID);
			$cli->emptyline();
			
			// reinsegne la propriété 'class_id'
			$ez_properties['class_id'] = $ezclassID;
			
			// verifie si la strucuture du model SUGAR n'a pas changé
			$verif  = $sugarSynchro->verifyClassAttributes($ez_properties);
			if(!$verif)
				$continue = false;
			elseif( is_array($verif) )
			{
				// OPTION 1 : si un attribute SUGAR n'existe pas chez EZ on ne met pas à jour l'objet
				// et on log les divergences. @TODO : envoyer un mail d'alerte
				$msg = "verifyClassAttributes : ERROR : certaines attributes du module SUGAR " .
						$cli->incolor('red-bg', $sugar_properties['sugar_module'], 'error') .
						" ne sont pas trouvés parmi les attributes de la class EZ " .
						$cli->incolor('red-bg', $ez_properties['class_identifier'], 'error');
				$cli->error( $msg );
				$cli->colorout('yellow',"attributes_not_founds :");
				$cli->colorout('yellow',show($verif));
				$continue = false;
				
				// OPTION 2 : si un attribute SUGAR n'existe pas chez EZ on le crée
				/*$objectsMaster->setProperty('class_id',$ezclassID);
				foreach($verif as $attr)
				{
					$newattr[] = $objectsMaster->createClassAttribute($attr);
					$cli->gnotice("nouveu attribut avec identifier " . $attr['identifier'] . " crée pou la class " . $ez_properties['class_identifier']);
				}
				
				$continue = true;*/
			}
			else
			{
				// continue l'execution du script
				$continue = true;
			}
		}
	}
	
	if($continue)
	{
		$get_entry_list = $sugarConnector->get_entry_list($sugar_properties['sugar_module']);
		$entry_list = $get_entry_list['entry_list'];
		$objects_count[$sugar_properties['sugar_module']] = count($entry_list);
		
		foreach($entry_list as $entry)
		{
			$sugarid = $entry['id'];
			
			$remoteID = $ez_properties['class_identifier'] . '_' . $sugarid;
			
			// reinsegne les propriétés 'object_remote_id', 'sugar_id'
			$ez_properties['object_remote_id'] = $remoteID;
			$sugar_properties['sugar_id'] = $sugarid;
			
			// get des valeurs des attributes de la table sugar
			// ex.: $sugar_attributes_values = array('attr_1' => 'test attr 1', 'attr_2' => 'test attr 2');
			$sugar_attributes_values = $sugarSynchro->getSugarFieldsValues($sugar_properties);
			if(!$sugar_attributes_values)
				$cli->error("\$sugarSynchro->getSugarFieldsValues(\$sugar_properties) return false !!!");
		
			$object_attributes = $sugarSynchro->synchronizeFieldsNames($sugar_attributes_values);
			if(!$object_attributes)
				$cli->error("\$sugarSynchro->synchronizeFieldsNames(\$sugar_attributes_values) return false !!!");
			
			if( !$sugar_attributes_values  or !$object_attributes )
			{
				$continue = false;
				$cli->warning("SugarSynchro.log");
				$cli->dgnotice( show(SugarSynchro::lastLogContent()) );
			}
			
			
			if( $continue )
			{
				$ez_properties['object_attributes'] = $object_attributes;
				
				// si $objectsMaster n'existe pas on crée une nouvelle instance
				if(!isset($objectsMaster))
				{
					// nouvelle instance de owObjectsMaster()
					$objectsMaster = owObjectsMaster::instance($ez_properties);
					if( !is_object($objectsMaster) )
					{
						$cli->error("owObjectsMaster::instance ne renvoie pas un objet");
						$cli->warning("owObjectsMaster.log");
						$cli->dgnotice( show(owObjectsMaster::lastLogContent()) );
						$continue = false;
					}
				}
				else
					$objectsMaster->setProperties($ez_properties);
			}
			
				
			if( $continue )
			{
				
				$object = eZContentObject::fetchByRemoteID( $remoteID );
				if( !$object )
				{
					// debug notice
					$cli->gnotice("remote ID " . $remoteID . " non trouvé.\n Procede à la creation de l'objet.");
					$cli->dynotice("sugarSynchro->sugar_attributes_values : ");
					$cli->dgnotice( show($sugarSynchro->getProperty('sugar_attributes_values')) );
					$cli->dynotice("objectsMaster->object_attributes : ");
					$cli->dgnotice( show($objectsMaster->getProperty('object_attributes')) );
					
					
					// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
					// CRÉE UN NOUVEAU OBJET EZ ***
					// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
					
					// @TODO : voir l'emplacement de l'objet selon la class ou autres parametres
					//			arborescence du site EZ ?
					
					// crée un nouveau objet de contenu EZ
					$createObject = $objectsMaster->createObjectEz();
					
					if($createObject)
						$cli->gnotice("L'objet avec remote_id " . $remoteID . " a été creé avec succes!");
					else
						$cli->error("ERROR createObjectEz() : " . show($createObject) );
					
				}
				else
				{
					// debug notice
					$cli->gnotice("remote ID " . $remoteID . " trouvé.\n Procede à la mise à jour de l'objet.");
					
					// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
					// MET À JOUR L'OBJET EZ EXISTANT ***
					// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
					
					// reinsegne la propriété 'content_object'
					$ez_properties['content_object'] = $object;
					$objectsMaster->setProperties($ez_properties);
					
					//debug notice
					$cli->dynotice("sugarSynchro->sugar_attributes_values : ");
					$cli->dgnotice( show($sugarSynchro->getProperty('sugar_attributes_values')) );
					$cli->dynotice("objectsMaster->object_attributes : ");
					$cli->dgnotice( show($objectsMaster->getProperty('object_attributes')) );
					
				    // met à jour l'objet EZ existant
				    $updateObject = $objectsMaster->updateObjectEz();
				    
				    if($updateObject)
						$cli->gnotice("L'objet avec remote_id " . $remoteID . " a été mis à jour avec succes!");
					else
						$cli->error("ERROR updateObjectEz() : " . show($updateObject) );
				}
				
				$cli->emptyline();
			}
		}
	}
}


// compte rendu du script
$cli->emptyline();
$cli->colorout('cyan-bg',"COMPTE RENDU DU SCRIPT");
$cli->colorout('cyan',"nombre de modules traitées : " . count($modules_list) );
$cli->colorout('cyan',"liste des modules traitées : ");
$indent = 1;
foreach($modules_list as $module_name)
{
	if( !isset($objects_count[$module_name]) )
		$mo_count = 0;
	else
		$mo_count =  $objects_count[$module_name];
	$cli->colorout('cyan-bg', $module_name, $indent );
	$cli->colorout( 'dark-cyan', "nombre d'objets traité pour le module " . $module_name . " : " . $mo_count, $indent );
}


// fin du script
$cli->endout("synchronize_ezsugar.php");

?>