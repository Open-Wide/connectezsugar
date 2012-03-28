<?php
/*
Cronjob pour l'update des classes EZ pour la synchronisation avec SUGAR

@author Pasquesi Massimiliano <massimiliano.pasquesi@openwide.fr>
*/

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);

// debut du script
$cli->beginout("update_classes.php");

// init de $continue
$continue = true;

// connexion à SUGAR
$sugarConnector = new SugarConnector();
$connection = $sugarConnector->login();
 
if( !$connection )
{
	$cli->error("sugarConnector->login() renvoie false! ");
	$cli->dgnotice( show(SugarConnector::lastLogContent()) );
	$continue = false;
}

if($continue)
{
	// modules SUGAR à synchroniser
	$modules_list = SugarSynchro::getModuleListToSynchro();
	
	foreach($modules_list as $sugarmodule)
	{
		$continue = true;

		$cli->emptyline();
		$cli->gnotice("*******************************************");
		$cli->notice("Sugar Module : $sugarmodule");
		$cli->gnotice("*******************************************");
		
		// initialise les tableaux $ez_properties et $sugar_properties
		$ez_properties = array();
		$sugar_properties = array();
		
		// reinsegne la propriété 'sugar_module'
		$sugar_properties['sugar_module'] = $sugarmodule;
		
		// instance de la class SugarSynchro
		$sugarSynchro = SugarSynchro::instance($sugar_properties);
		
		if( !is_object($sugarSynchro) )
		{
			$cli->error("SugarSynchro::instance() ne renvoie pas un objet");
			$cli->dgnotice( show(SugarSynchro::lastLogContent()) );
			$continue = false;
		}
		
		if($continue)
		{
			// definie le nom de la class EZ
			$class_name = $sugarSynchro->defClassName($sugarmodule);
			// definie l'identifier de la class EZ
			$class_identifier = $sugarSynchro->defClassIdentifier($sugarmodule);
			
			// reinsegne la propriété 'class_name' 'class_identifier'
			$ez_properties['class_name'] = $class_name;
			$ez_properties['class_identifier'] = $class_identifier;
			
			// get des attributes de la table sugar à synchroniser
			// ex.: $sugar_attributes = array(	'attr_1' => array( 'name' => 'Attr 1', 'datatype' => 'ezstring', 'required' => 1 ),
			//									'attr_2' => array( 'name' => 'Attr 2', 'datatype' => 'eztext', 'required' => 0 ) );
			$sugar_attributes = $sugarSynchro->getSugarFields($sugar_properties);
			if(!$sugar_attributes)
				$cli->error("\$sugarSynchro->getSugarFields(\$sugar_properties) return false !!!");

			// reinsegne la propriété 'class_attributes'
			$class_attributes = $sugarSynchro->synchronizeFieldsNames($sugar_attributes);
			if(!$class_attributes)
				$cli->error("\$sugarSynchro->synchronizeFieldsNames(\$sugar_attributes) return false !!!");
					
			if( is_array($sugar_attributes) and is_array($class_attributes) )
				$continue = true;
			else
			{
				$cli->warning("SugarSynchro.log");
				$cli->dgnotice( show(SugarSynchro::lastLogContent()) );
				$continue = false;
			}
		}
		
		if($continue)
		{
			//$class_attributes = owObjectsMaster::normalizeIdentifiers($sugar_attributes);
			$ez_properties['class_attributes'] = $class_attributes;
			
			$ezclassID = eZContentClass::classIDByIdentifier($class_identifier);
			if(!$ezclassID)
			{
				$cli->gnotice("class de contenu EZ " . $class_identifier . " non trouvé.\n Procede à la creation de la class.");
				$action = "create";
			}
			else
			{
				// reinsegne les propriétés 'class_id'
				$ez_properties['class_id'] = $ezclassID;
				$action = "update";
			}
		}
		
		if($continue)
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
		
		if($continue)
		{
			if( $action == "create" )
			{
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
				//$cli->dgnotice($objectsMaster->getProperties());
				
				// crée la nouvelle class de contenu EZ
				$createClass = $objectsMaster->createClassEz($ez_properties);
				if($createClass)
				{
					// get de l'id de la class crée
					$ezclassID = eZContentClass::classIDByIdentifier($class_identifier);
					// reinsegne la propriété 'class_id'
					$ez_properties['class_id'] = $ezclassID;
					// terminal output message
					$cli->gnotice("La class " . $class_identifier . " a été creé avec succes!");
					$cli->gnotice("ezclassID : ". $ezclassID);
					// continue l'execution du script
					$continue = true;
				}	
				else
				{
					$cli->error(printf("createClassEz() return %s", show($createClass)));
					$cli->dgnotice( show(owObjectsMaster::lastLogContent()) );
					$continue = false;
				}
				
			}
			elseif( $action == "update" )
			{
				// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
				// UPDATE DE LA CLASS EZ ***
				// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
				
				// verifie si la strucuture du model SUGAR n'a pas changé
				$verif  = $sugarSynchro->verifyClassAttributes($ez_properties);
				if(!$verif)
					$continue = false;
				elseif( is_array($verif) )
				{
					// si un attribute SUGAR n'existe pas chez EZ on le crée
					foreach($verif as $attr)
					{
						$newattr = $objectsMaster->createClassAttribute($attr);
						
						// !IMPORTANT! : met à jour tous les objects existants avec le nouveau attribut
						$newattr->initializeObjectAttributes();
						
						$cli->gnotice("nouveu attribut avec identifier " . $attr['identifier'] . " crée pou la class " . $ez_properties['class_identifier']);
						$cli->gnotice(show($ez_properties['class_attributes'][$attr['identifier']]));
						
						// pour le compte rendu du script
						$newclassesattributes[$sugarmodule][] = $attr['identifier'];
					}
				}
				else
				{
					$cli->gnotice("Aucun changement pour la class " . $ez_properties['class_identifier']);
				}
				
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
	if( !isset($newclassesattributes[$module_name]) )
		$mo_count = 0;
	else
		$mo_count =  count($newclassesattributes[$module_name]);
	$cli->colorout('cyan-bg', $module_name, $indent );
	$cli->colorout( 'dark-cyan', "nombre de nouveaux attributs pour le module " . $module_name . " : " . $mo_count, $indent );
}

// fin du script
$cli->endout("update_classes.php");

?>