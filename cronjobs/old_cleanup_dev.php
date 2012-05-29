<?php
/*
Cronjob pour la synchronisation des objets EZ depuis SUGAR

@author Pasquesi Massimiliano <massimiliano.pasquesi@openwide.fr>
*/

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

gc_enable();

// init CLI
$cli = SmartCLI::instance();

//evd($cli);

// afficher(false) ou pas afficher(true) les 'cli->output'
$cli->setIsQuiet(false);

// debut du script
$cli->beginout("cleanup_dev.php");

// connexion à SUGAR
$sugarConnector=new SugarConnector();
$connection=$sugarConnector->login();

$cli->mnotice("Mémoire utilisée avant getModuleListToSynchro() : " . memory_get_usage_hr());

// classes EZ à nettoyers
// attention c'est la même liste que les modules SUGAR à synchroniser
$modules_list = SugarSynchro::getModuleListToSynchro();

$cli->mnotice( "Mémoire utilisée avant boucle sur les modules : " . memory_get_usage_hr() );

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
	
	$cli->mnotice("Mémoire utilisée avant getSugarModuleIdList() : " . memory_get_usage_hr());
	
	$entry_list = $sugarSynchro->getSugarModuleIdList($sugar_properties);
	
	$cli->mnotice("Mémoire utilisée après getSugarModuleIdList() : " . memory_get_usage_hr());
	
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
			
			$cli->mnotice("Mémoire utilisée après owObjectsMaster::objectIDByRemoteID() : " . memory_get_usage_hr());
			
			if(!$id)
			{
				$cli->warning("L'ID de l'objet avec remote_id " . $remoteID . " n'existe pas dans la base !");
			}
			else
			{
				$cli->dgnotice("L'objet avec remote_id " . $remoteID . " a l'ID : " . $id);
				
				$objectToDelete = eZContentObject::fetch($id);
				
				$cli->mnotice("Mémoire utilisée après eZContentObject::fetch() : " . memory_get_usage_hr());
				
				if(!$objectToDelete)
				{
					$cli->dgnotice("L'objet avec id " . $id . " n'est pas connu !\n Procede au nettoyage de la base");
					$purgeObject = owObjectsMaster::objectPurge($id);
					if($purgeObject)
						$cli->gnotice("Toute trace de L'objet avec remote_id " . $remoteID . " ont été supprimé de la base avec succes !");
					else
						$cli->warning("L'objet avec remote_id " . $remoteID . " n'a pas été trouvé dans la table ezcontentobject !");
						
					$cli->mnotice("Mémoire utilisée après owObjectsMaster::objectPurge() : " . memory_get_usage_hr());
				}
				else
				{
					$deleteObject = eZContentObjectOperations::remove($id);
					if($deleteObject)
						$cli->gnotice("L'objet avec remote_id " . $remoteID . " a été supprimé avec succes !");
					else
						$cli->error("L'objet avec remote_id " . $remoteID . " n'a pas pu être supprimé !");
						
					$cli->mnotice("Mémoire utilisée après eZContentObjectOperations::remove() : " . memory_get_usage_hr());
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
				$cli->gnotice("La class " . $class_identifier . " a été supprimé avec succes !");
			else
				$cli->error("La class " . $class_identifier . " n'a pas pu être supprimé !");
				
			$cli->mnotice( "Mémoire utilisée aprés eZContentClassOperations::remove() : " . memory_get_usage_hr() );
		}
		
		// liberation de la memoire de $entry_list
		$cli->gnotice("Mémoire utilisée avant desctruction \$entry_list : " . memory_get_usage_hr());
		unset($entry_list);
		$cli->gnotice("Mémoire utilisée apres desctruction \$entry_list : " . memory_get_usage_hr());
	}
	
	// force le passage du garbage collector
	$cli->gnotice("Mémoire utilisée avant gc_collect_cycles() : " . memory_get_usage_hr());
	gc_collect_cycles();
	$cli->gnotice("Mémoire utilisée après gc_collect_cycles() : " . memory_get_usage_hr());
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