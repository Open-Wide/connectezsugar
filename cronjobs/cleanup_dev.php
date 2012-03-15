<?php
/*
Cronjob pour la synchronisation des objets EZ depuis SUGAR

@author Pasquesi Massimiliano <massipasquesi@gmail.com>
*/

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

// init CLI
$cli = SmartCLI::instance();

// afficher(false) ou pas afficher(true) les 'cli->output'
$cli->setIsQuiet(false);

// debut du script
$cli->beginout("cleanup_dev.php");

// connexion à SUGAR
$sugarConnector=new SugarConnector();
$connection=$sugarConnector->login();

// classes EZ à nettoyers
// attention c'est la même liste que les modules SUGAR à synchroniser
$modules_list = SugarSynchro::getModuleListToSynchro();


foreach($modules_list as $sugarmodule)
{
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
	
	// get du class_id
	$classID = eZContentClass::classIDByIdentifier($class_identifier);
	
	// si la class existe on la supprime
	if(!$classID)
		$cli->warning("La class avec identifiant " . $class_identifier . " n'est pas connue !");
	else
	{
		$deleteClass = eZContentClassOperations::remove($classID);
		if($deleteClass)
			$cli->gnotice("La class " . $class_identifier . " a été supprimé avec succes !");
		else
			$cli->error("La class " . $class_identifier . " n'a pas pu être supprimé !");
	}
	
	
	$entry_list = $sugarSynchro->getSugarModuleEntryList($sugar_properties);
		
	if( !$entry_list )
	{
		$cli->error("SugarConnector ERROR : ");
		$cli->warning("SugarSynchro.log : ");
		$cli->dgnotice( show(SugarSynchro::lastLogContent()) );
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
			
			if(!$id)
			{
				$cli->warning("L'ID de l'objet avec remote_id " . $remoteID . " n'existe pas dans la base !");
			}
			else
			{
				$cli->dgnotice("L'objet avec remote_id " . $remoteID . " a l'ID : " . $id);
				
				$objectToDelete = eZContentObject::fetch($id);
				if(!$objectToDelete)
				{
					$cli->dgnotice("L'objet avec id " . $id . " n'est pas connu !\n Procede au nettoyage de la base");
					$purgeObject = owObjectsMaster::objectPurge($id);
					if($purgeObject)
						$cli->gnotice("Toute trace de L'objet avec remote_id " . $remoteID . " ont été supprimé de la base avec succes !");
					else
						$cli->warning("L'objet avec remote_id " . $remoteID . " n'a pas été trouvé dans la table ezcontentobject !");
				}
				else
				{
					$deleteObject = eZContentObjectOperations::remove($id);
					if($deleteObject)
						$cli->gnotice("L'objet avec remote_id " . $remoteID . " a été supprimé avec succes !");
					else
						$cli->error("L'objet avec remote_id " . $remoteID . " n'a pas pu être supprimé !");
				}
			}
			
		}
		
	}
	
}


// clear cache
eZCache::clearAll();


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
$cli->endout("cleanup_dev.php");

?>