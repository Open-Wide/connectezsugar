<?php

/*
Cronjob pour le nettoyage de tous les objets d'une class EZ correspondant à un module SUGAR

@author Pasquesi Massimiliano <massimiliano.pasquesi@openwide.fr>
*/

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

function this_cronjob_usage($cli, $script)
{
    $cli->error('usage: php runcronjobs cleanupmodule <module_name>');
    $script->shutdown();
    exit();
}


// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);

$script = eZScript::instance( array( 'description' => ('Exhibition synchronization for Premiere Vision'),
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


$sugarmodule = $arguments[1];

$cli->notice( $sugarmodule );


// debut du script
$cli->notice("start cleanup_module.php");


$continue = true;
	
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

$cli->notice("Mémoire utilisée avant getSugarModuleIdList() : " . memory_get_usage_hr());

$entry_list = $sugarSynchro->getSugarModuleIdList($sugar_properties);

$cli->notice("Mémoire utilisée après getSugarModuleIdList() : " . memory_get_usage_hr());

if( !$entry_list )
{
	$cli->error("SugarConnector ERROR : ");
	$cli->warning("SugarSynchro.log : ");
	$cli->warning( show(SugarSynchro::lastLogContent()) );
	$continue = false;
}

if($continue)
{
	$objects_count[$sugar_properties['sugar_module']] = count($entry_list);
	
	foreach($entry_list as $entry)
	{
		$sugarid = $entry['id'];
		
		$remoteID = $ez_properties['class_identifier'] . '_' . $sugarid;
		
		// reinsegne les propriétés 'object_remote_id', 'sugar_id'
		$ez_properties['object_remote_id'] = $remoteID;
		$sugar_properties['sugar_id'] = $sugarid;
		
		
		$id = owObjectsMaster::objectIDByRemoteID($remoteID);
		
		$cli->notice("Mémoire utilisée après owObjectsMaster::objectIDByRemoteID() : " . memory_get_usage_hr());
		
		if(!$id)
		{
			$cli->warning("L'ID de l'objet avec remote_id " . $remoteID . " n'existe pas dans la base !");
		}
		else
		{
			$cli->notice("L'objet avec remote_id " . $remoteID . " a l'ID : " . $id);
			
			$objectToDelete = eZContentObject::fetch($id);
			
			$cli->notice("Mémoire utilisée après eZContentObject::fetch() : " . memory_get_usage_hr());
			
			if(!$objectToDelete)
			{
				$cli->dgnotice("L'objet avec id " . $id . " n'est pas connu !\n Procede au nettoyage de la base");
				$purgeObject = owObjectsMaster::objectPurge($id);
				if($purgeObject)
					$cli->notice("Toute trace de L'objet avec remote_id " . $remoteID . " ont été supprimé de la base avec succes !");
				else
					$cli->warning("L'objet avec remote_id " . $remoteID . " n'a pas été trouvé dans la table ezcontentobject !");
					
				$cli->notice("Mémoire utilisée après owObjectsMaster::objectPurge() : " . memory_get_usage_hr());
			}
			else
			{
				$deleteObject = eZContentObjectOperations::remove($id);
				if($deleteObject)
					$cli->notice("L'objet avec remote_id " . $remoteID . " a été supprimé avec succes !");
				else
					$cli->error("L'objet avec remote_id " . $remoteID . " n'a pas pu être supprimé !");
					
				$cli->notice("Mémoire utilisée après eZContentObjectOperations::remove() : " . memory_get_usage_hr());
			}
		}
		
	}
	
	// get du class_id
	$classID = eZContentClass::classIDByIdentifier($class_identifier);
	
	// si la class existe on la supprime
	if(!$classID)
		$cli->warning("La class avec identifiant " . $class_identifier . " n'est pas connue !");
	else
	{
		//$deleteClass = eZContentClassOperations::remove($classID);
		$deleteClass = owObjectsMaster::removeContentClass($classID, false);
		if($deleteClass)
			$cli->notice("La class " . $class_identifier . " a été supprimé avec succes !");
		else
			$cli->error("La class " . $class_identifier . " n'a pas pu être supprimé !");
			
		$cli->notice( "Mémoire utilisée aprés eZContentClassOperations::remove() : " . memory_get_usage_hr() );
	}
	
	// liberation de la memoire de $entry_list
	$cli->notice("Mémoire utilisée avant desctruction \$entry_list : " . memory_get_usage_hr());
	unset($entry_list);
	$cli->notice("Mémoire utilisée apres desctruction \$entry_list : " . memory_get_usage_hr());
}


$cli->notice("end cleanup_module.php");
$script->shutdown();

?>