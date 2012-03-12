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
   $sugarid="25cc743a-df11-c88c-8e0f-4f3405904130";
   

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
	// instance de la class SugarSynchro pour le module
	$sugarSynchro = SugarSynchro::instance();
	if( !is_object($sugarSynchro) )
	{
		$notice['SugarSynchro.log'] = SugarSynchro::lastLogContent();
		$continue = false;
	}
}

if($continue)
{
	// initialise les tableaux $ez_properties et $sugar_properties
	$ez_properties = array();
	$sugar_properties = array();
	
	// definie le nom de la class EZ pour le module
	$class_name = $sugarSynchro->defClassName($sugarmodule);
	// definie l'identifier de la class EZ pour le module
	$class_identifier = $sugarSynchro->defClassIdentifier($sugarmodule);
	
	// reinsegne la propriété 'class_name' 'class_identifier' pour le module
	$ez_properties['class_name'] = $class_name;
	$ez_properties['class_identifier'] = $class_identifier;
	
	// reinsegne la propriété 'sugar_module' pour le module
	$sugar_properties['sugar_module'] = $sugarmodule;
	
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
	
	$relation_type = "name";
	$sugarrelations = $sugarSynchro->getRelations($sugar_properties, $relation_type);
	
	$notice['sugarrelations'] = $sugarrelations;
	
	foreach( $sugarrelations as $related_class_identifier => $related_name )
	{
		$setRelation = $objectsMaster->setObjectRelation($ez_properties, $related_class_identifier, $related_name, $relation_type);
		if( $setRelation )
			$result[$related_name] = "La relation d'objets a été crée avec succes";
		else
			$result[$related_name] = owObjectsMaster::lastLogContent();;
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
