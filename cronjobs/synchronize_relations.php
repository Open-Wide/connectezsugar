<?php
/*
Cronjob pour la synchronisation des relations d'objets EZ depuis SUGAR

@author Pasquesi Massimiliano <massimiliano.pasquesi@openwide.fr>
*/

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );


// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);

// debut du script
$cli->beginout("synchronize_relations.php");

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
			$checkRelations = $sugarSynchro->checkForRelations();
			if( $checkRelations === false )
			{
				$cli->warning("Aucun tableau relations_names[] trouvé pour le module " . $sugar_properties['sugar_module']);
				$continue = false;
			}
		}
		
		if($continue)
		{
			// definie le nom de la class EZ
			$class_name = $sugarSynchro->defClassName($sugarmodule);
			// definie l'identifier de la class EZ
			$class_identifier = $sugarSynchro->defClassIdentifier($sugarmodule);
			
			// reinsegne la propriété 'class_name' 'class_identifier', 'class_id'
			$ez_properties['class_name'] = $class_name;
			$ez_properties['class_identifier'] = $class_identifier;
			$ez_properties['class_id'] = eZContentClass::classIDByIdentifier($class_identifier);
		}

		if($continue)
		{
			$entry_list = $sugarSynchro->getSugarModuleEntryList();
			if( !$entry_list )
			{
				$cli->error("SugarConnector ERROR : ");
				$cli->warning("SugarSynchro.log : ");
				$cli->dgnotice( show(SugarSynchro::lastLogContent()) );
				$continue = false;
			}
		}
		
		if($continue)
		{	
			$objects_count[$sugar_properties['sugar_module']] = count($entry_list);
			
			foreach($entry_list as $entry)
			{
				$continue = true;
				
				// ID SUGAR
				$sugarid = $entry['id'];
				// remote_ID EZ
				$remoteID = $ez_properties['class_identifier'] . '_' . $sugarid;
				
				// reinsegne les propriétés 'object_remote_id', 'sugar_id', 'content_object'
				$ez_properties['object_remote_id'] = $remoteID;
				$sugar_properties['sugar_id'] = $sugarid;
				$ez_properties['content_object'] = eZContentObject::fetchByRemoteID($remoteID);
				
				$cli->emptyline();
				$cli->gnotice("*******************************************");
				$cli->notice("remote ID : $remoteID");
				$cli->gnotice("*******************************************");
				
				// nouvelle instance de owObjectsMaster()
				$objectsMaster = owObjectsMaster::instance($ez_properties);
				
				if( !is_object($objectsMaster) )
				{
					$cli->error("owObjectsMaster::instance ne renvoie pas un objet");
					$cli->warning("owObjectsMaster.log");
					$cli->dgnotice( show(owObjectsMaster::lastLogContent()) );
					$continue = false;
				}
				
				if( is_null($ez_properties['content_object']) )
				{
					$cli->error("Je n'ai pas trouvé d'objet avec remote_id : $remoteID ");
					$continue = false;
				}
				
				if($continue)
				{
					$relations_array = $sugarSynchro->getRelations($sugar_properties);
					
					if(!$relations_array)
					{
						$cli->error("sugarSynchro->getRelations renvoie FALSE! ");
						$cli->warning( show(SugarSynchro::lastLogContent()));
						$continue = false;
					}
					elseif( is_array($relations_array) && count($relations_array) == 0 )
					{
						$cli->warning("Aucun relations trouvé pour le module " . $sugar_properties['sugar_module']);
						$continue = false;
					}
					else
						$cli->dgnotice(show($relations_array));
				}
				
				if($continue)
				{
					$sugarrelations = $sugarSynchro->relationsArrayToRemoteId($relations_array);
					
					foreach( $sugarrelations as $related_class_identifier => $related_ids )
					{
						if( is_null($related_ids) or empty($related_ids) )
							$cli->gnotice("L'objet avec remoteID " . $ez_properties['object_remote_id'] . " n'as pas de relation avec d'objets de type $related_class_identifier");
						else
						{
							$setRelation = $objectsMaster->setObjectRelationByRemoteIds($ez_properties, $related_class_identifier, $related_ids);
							if( !$setRelation )
								$cli->warning(show(owObjectsMaster::lastLogContent()));
							else
							{
								foreach( $setRelation as $id => $result )
								{
									if( $result )
										$cli->gnotice("La relation pour l'objet avec remoteID " . $ez_properties['object_remote_id'] . " avec l'objet remoteID: $id a été crée avec succes");
									else
										$cli->warning("La relation pour l'objet avec remoteID " . $ez_properties['object_remote_id'] . " avec l'objet remoteID: $id n'a pas été crée! ");
								}
								
							}
								
								
						}
						
					}
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
	if( !isset($objects_count[$module_name]) )
		$mo_count = 0;
	else
		$mo_count =  $objects_count[$module_name];
	$cli->colorout('cyan-bg', $module_name, $indent );
	$cli->colorout( 'dark-cyan', "nombre d'objets traité pour le module " . $module_name . " : " . $mo_count, $indent );
}


// fin du script
$cli->endout("synchronize_relations.php");

?>