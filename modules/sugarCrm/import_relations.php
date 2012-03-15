<?php
include_once( 'kernel/common/template.php' );
include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

if (isset ($Params["sugarmodule"]))
   $sugarmodule = $Params["sugarmodule"];
else
   $sugarmodule="otcp_room";
   
if (isset ($Params["sugarid"]))
   $sugarid = $Params["sugarid"];
else
   $sugarid="3183d896-9573-12f6-4029-4f3402e785ee";
   

eZDebug::writeNotice("Sugar Module : " . $sugarmodule); 
eZDebug::writeNotice("Sugar Id : " . $sugarid);

// init result, notice et continue
$notice = null;
$result = null;
$continue = true;

if( is_null($sugarmodule) || is_null($sugarid) )
{
	$result[] = "sugarmodule et/ou sugarid manquant !!!";
	$continue = false;
}

if($continue)
{
	// initialise les tableaux $ez_properties et $sugar_properties
	$ez_properties = array();
	$sugar_properties = array();
	
	// reinsegne la propriété 'sugar_module' pour le module
	$sugar_properties['sugar_module'] = $sugarmodule;
	
	// instance de la class SugarSynchro pour le module
	$sugarSynchro = SugarSynchro::instance($sugar_properties);
	if( !is_object($sugarSynchro) )
	{
		$notice['SugarSynchro.log'] = SugarSynchro::lastLogContent();
		$continue = false;
	}
}

if($continue)
{
	$checkRelations = $sugarSynchro->checkForRelations();
	if( $checkRelations === false )
	{
		$notice['checkForRelations'] = "Aucun tableau relations_names[] trouvé pour le module " . $sugar_properties['sugar_module'];
		$continue = false;
	}
}

if($continue)
{	
	// definie le nom de la class EZ pour le module
	$class_name = $sugarSynchro->defClassName($sugarmodule);
	// definie l'identifier de la class EZ pour le module
	$class_identifier = $sugarSynchro->defClassIdentifier($sugarmodule);
	
	// reinsegne la propriété 'class_name' 'class_identifier' pour le module
	$ez_properties['class_name'] = $class_name;
	$ez_properties['class_identifier'] = $class_identifier;
	
	$remoteID = $class_identifier . '_' . $sugarid;
	// reinsegne les propriétés 'object_remote_id', 'sugar_id'
	$ez_properties['object_remote_id'] = $remoteID;
	$sugar_properties['sugar_id'] = $sugarid;
	
	// reinsegne les propriétés 'content_object', 'class_id'
	$class = eZContentClass::fetchByIdentifier($class_identifier);
	$ez_properties['class_id'] = $class->ID;
	$ez_properties['content_object'] = eZContentObject::fetchByRemoteID($remoteID);
	
	// nouvelle instance de owObjectsMaster()
	$objectsMaster = owObjectsMaster::instance($ez_properties);
	
	if( !is_object($objectsMaster) )
	{
		$notice['owObjectsMaster::instance'] = "owObjectsMaster::instance ne renvoie pas un objet";
		$notice['owObjectsMaster.log'] = owObjectsMaster::lastLogContent();
		$continue = false;
	}
	
	if( is_null($ez_properties['content_object']) )
	{
		$notice['NoContentObject'] = "Je n'ai pas trouvé d'objet avec remote_id : $remoteID ";
		$continue = false;
	}
	
}

if($continue)
{
	$relations_array = $sugarSynchro->getRelations($sugar_properties);
	
	if(!$relations_array)
	{
		$result['sugarrelations'] = "sugarSynchro->getRelations renvoie FALSE! ";
		$notice['SugarSynchro.log'] = SugarSynchro::lastLogContent();
		$continue = false;
	}
	elseif( is_array($relations_array) && count($relations_array) == 0 )
	{
		$result['sugarrelations'] = "Aucun relations trouvé pour le module " . $sugar_properties['sugar_module'];
		$continue = false;
	}
	else
		$notice['sugarrelations'] = $relations_array;
}

if($continue)
{
	$sugarrelations = $sugarSynchro->relationsArrayToRemoteId($relations_array);
	
	foreach( $sugarrelations as $related_class_identifier => $related_ids )
	{
		if( is_null($related_ids) or empty($related_ids) )
			$result[$related_class_identifier] = "L'objet avec remoteID " . $ez_properties['object_remote_id'] . " n'as pas de relation avec d'objets de type $related_class_identifier";
		else
		{
			$setRelation = $objectsMaster->setObjectRelationByRemoteIds($ez_properties, $related_class_identifier, $related_ids);
			if( !$setRelation )
			{
				$notice['owObjectsMaster.log'] = owObjectsMaster::lastLogContent();
				$result[$related_class_identifier] = "\$objectsMaster->setObjectRelationByRemoteIds renvoie FALSE ! ";
			}
			else
			{
				foreach( $setRelation as $id => $result )
				{
					if( $result )
						$result[$id] = "La relation pour l'objet avec remoteID " . $ez_properties['object_remote_id'] . " avec l'objet remoteID: $id a été crée avec succes";
					else
					{
						$notice['owObjectsMaster.log'] = owObjectsMaster::lastLogContent();
						$result[$id] = "La relation pour l'objet avec remoteID " . $ez_properties['object_remote_id'] . " avec l'objet remoteID: $id n'a pas été crée! ";
					}
				}
				
			}
	
		}
		
	}
}




$tpl = templateInit();
$tpl->setVariable('result',$result);
$tpl->setVariable('notice',$notice);
$tpl->setVariable('title', "Import Relations.");
$Result = array();
$Result['content'] = $tpl->fetch( 'design:sugarcrm/notice_result.tpl' );
$Result['pagelayout']=false;
$Result['path'] = array( array( 'url' => false,
                               'text' => 'import_relations' ) );

?>
