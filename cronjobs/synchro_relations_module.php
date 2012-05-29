<?php

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

function this_cronjob_usage($cli, $script)
{
    $cli->error('usage: php runcronjobs.php synchrorelationsmodule <module_name>');
    $script->shutdown();
    exit();
}

//set_time_limit ( 0 );

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);

$script = eZScript::instance( array( 'description' => ('test pour cronjob avec arguments'),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true,
                                     'debug-output' => true,
                                     'debug-message' =>true) );


$script->startup();
$script->initialize();
$options = $script->getOptions();
$arguments = $options['arguments'];

if (count($arguments) <= 1)
{
    this_cronjob_usage($cli, $script);
}

$cli->notice("start synchronize_relations.php");

$sugarmodule = $arguments[1];

// init de $continue
$continue = true;

$cli->notice("*******************************************");
$cli->notice("Sugar Module : $sugarmodule");
$cli->notice("*******************************************");

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
	$cli->warning( show(SugarSynchro::lastLogContent()) );
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
	$entry_list = $sugarSynchro->getSugarModuleIdList();
	if( !$entry_list )
	{
		$cli->error("SugarConnector ERROR : ");
		$cli->warning("SugarSynchro.log : ");
		$cli->warning( show(SugarSynchro::lastLogContent()) );
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
		
		//$cli->emptyline();
		$cli->notice("*******************************************");
		$cli->notice("remote ID : $remoteID");
		$cli->notice("*******************************************");
		
		// nouvelle instance de owObjectsMaster()
		$objectsMaster = owObjectsMaster::instance($ez_properties);
		
		if( !is_object($objectsMaster) )
		{
			$cli->error("owObjectsMaster::instance ne renvoie pas un objet");
			$cli->warning("owObjectsMaster.log");
			$cli->warning( show(owObjectsMaster::lastLogContent()) );
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
				$cli->warning(show($relations_array));
		}
		
		if($continue)
		{
			$sugarrelations = $sugarSynchro->relationsArrayToRemoteId($relations_array);
			
			foreach( $sugarrelations as $related_class_identifier => $related_ids )
			{
				if( is_null($related_ids) or empty($related_ids) )
					$cli->notice("L'objet avec remoteID " . $ez_properties['object_remote_id'] . " n'as pas de relation avec d'objets de type $related_class_identifier");
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
								$cli->notice("La relation pour l'objet avec remoteID " . $ez_properties['object_remote_id'] . " avec l'objet remoteID: $id a été crée avec succes");
							else
								$cli->warning("La relation pour l'objet avec remoteID " . $ez_properties['object_remote_id'] . " avec l'objet remoteID: $id n'a pas été crée! ");
						}
						
					}
						
						
				}
				
			}
		}
		
		if( isset($relations_array) )
			unset($relations_array);
			
		if( isset($sugarrelations) )
			unset($sugarrelations);
		
		unset($objectsMaster);
	}

}

$cli->notice("end synchronize_relations.php");

$script->shutdown();

?>