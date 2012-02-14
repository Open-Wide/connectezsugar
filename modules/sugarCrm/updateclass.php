<?php
include_once( 'kernel/common/template.php' );

if (isset ($Params["sugarmodule"]))
   $sugarmodule = $Params["sugarmodule"];
else
   $sugarmodule="test_Hotel";

eZDebug::writeNotice("Sugar Module : " . $sugarmodule); 

$notice = null;
$result = null;
$continue = true;

if( is_null($sugarmodule) )
{
	$notice = "sugarmodule manquant !!!";
}
else
{
	// debug notice
	eZDebug::writeNotice("Sugar Class : ". $sugarmodule);
	
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
	$sugar_attributes = $sugarSynchro->getSugarFields($sugar_properties);
	if(!$sugar_attributes)
		$result[] = "\$sugarSynchro->getSugarFields(\$sugar_properties) return false !!!";
	
	// reinsegne la propriété 'class_attributes' après avoir normalisé les identifiants
	$class_attributes = $sugarSynchro->synchronizeFieldsNames($sugar_attributes);
	if(!$class_attributes)
		$result[] = "\$sugarSynchro->synchronizeFieldsNames(\$sugar_attributes) return false !!!";
			
	if( !$sugar_attributes or !$class_attributes )
		$continue = true;
	
	if($continue)
	{
		//$class_attributes = owObjectsMaster::normalizeIdentifiers($sugar_attributes);
		$ez_properties['class_attributes'] = $class_attributes;
		
		$ezclassID = eZContentClass::classIDByIdentifier($class_identifier);
		if(!$ezclassID)
		{
			$result[] = "class de contenu EZ " . $class_identifier . " non trouvé.";
			$continue = false;
		}
		else
		{
			// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
			// MET À JOUR LA CLASS EZ EXISTANT ***
			// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
			// @TODO : update de la class dans le cas d'une modification du model SUGAR
			
			// reinsegne les propriétés 'class_id'
			$ez_properties['class_id'] = $ezclassID;
			
			// nouvelle instance de owObjectsMaster()
			$objectsMaster = owObjectsMaster::instance($ez_properties);
			
			//debug notice
			$notice = $objectsMaster->getProperties();
			
			// fais un update la class de contenu EZ
			$updateClass = $objectsMaster->updateClassEz($ez_properties);
			if($updateClass)
			{
				$result[] = "La class " . $ez_properties['class_identifier'] . " a été mise à jour avec succes!";
				// continue l'execution du script
				$continue = true;
				//$continue = false;
			}	
			else
				$result[] = array('updateClassEz()' => $updateClass);
			
			
		}
		
	}
}


$tpl =& templateInit();
$tpl->setVariable('result',$result);
$tpl->setVariable('notice',$notice);
$tpl->setVariable('title', "Interrogation sugarCRM : " . $query);
$Result = array();
$Result['content'] = $tpl->fetch( 'design:sugarcrm/notice_result.tpl' );
$Result['pagelayout']=false;
$Result['path'] = array( array( 'url' => false,
                               'text' => 'querysugar' ) );

?>
