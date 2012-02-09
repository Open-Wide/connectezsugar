<?php
include_once( 'kernel/common/template.php' );

if(isset($Params["sugarmodule"]))
	$sugarmodule = $Params["sugarmodule"];
else
   $sugarmodule="test_Hotel";
   
if(isset($Params["sugarid"]))
   $sugarid = $Params["sugarid"];
else
   $sugarid="df72c261-9645-ec3a-d369-4f2a6ca53650";

$notice = null;
$result = null;
$continue = false;
   
if( is_null($sugarmodule) or is_null($sugarid) )
{
	$notice = "sugarclass et/ou sugarid manquant !!!";
}
else
{  
	eZDebug::writeNotice("Sugar Class : ". $sugarmodule);
	eZDebug::writeNotice("Sugar Identifiant : ". $sugarid);
	
	// initialise les tableaux $ez_properties et $sugar_properties
	$ez_properties = array();
	$sugar_properties = array();
	
	// instance de la class SugarSynchro
	$sugarSynchro = SugarSynchro::instance();
	
	// definie le nom de la class EZ
	// si il faut enlever le prefix du nom du module SUGAR pour la class EZ on le fait
	$class_name = $sugarSynchro->defClassName($sugarmodule);
	
	// reinsegne la propriété 'sugar_module'
	$sugar_properties['sugar_module'] = $sugarmodule;
	
	// get des attributes de la table sugar à synchroniser
	// ex.: $class_attributes = array(	'attr_1' => array( 'name' => 'attr_1', 'datatype' => 'ezstring', 'required' => 1 ),
	//									'attr_2' => array( 'name' => 'attr_2', 'datatype' => 'eztext', 'required' => 0 ) );
	$class_attributes = $sugarSynchro->getSynchroFields($sugar_properties);
	
	// reinsegne les propriétés 'class_attributes', 'sugar_attributes'
	$ez_properties['class_attributes'] = $class_attributes;
	$sugar_properties['sugar_attributes'] = $class_attributes;
	
	$ezclassID = eZContentClass::classIDByIdentifier(strtolower($class_name));
	if(!$ezclassID)
	{
		// debug notice
		eZDebug::writeNotice("class de contenu EZ " . $class_name . " non trouvé.\n Procede à la creation de la class.");
		
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
		
		// reinsegne la propriété 'class_name'
		$ez_properties['class_name'] = $class_name;
		
		// nouvelle instance de owObjectsMaster()
		$objectsMaster = owObjectsMaster::instance($ez_properties);
		//debug notice
		$notice = $objectsMaster->getProperties();
		// crée la nouvelle class de contenu EZ
		$createClass = $objectsMaster->createClassEz($ez_properties);
		if($createClass)
		{
			$result = "La class " . $class_name . " a été creé avec succes!";
			// get de l'id de la class crée
			$ezclassID = eZContentClass::classIDByIdentifier(strtolower($class_name));
			// continue l'execution du script
			$continue = true;
			//$continue = false;
		}	
		else
			//$result = "Problème rencontré dans la création de la class " . $sugarmodule
				//		. ". Voir le log pour plus de details.";
			$result = $createClass;
	}
	else
	{	
		// @TODO : verifier si la strucuture du model SUGAR n'a pas changé 
		
		// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
		// MET À JOUR LA CLASS EZ EXISTANT ***
		// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
		
		// @TODO : update de la class dans le cas d'une modification du model SUGAR
		
		// debug notice
		eZDebug::writeNotice("ezclassID : ". $ezclassID);
		// continue l'execution du script
		$continue = true;
		
	}
	
	if($continue)
	{
		// le remote_id des objet a la forme '$sugarmodule_$sugarid' !
		$remoteID = $class_name . '_' . $sugarid;
		
		// reinsegne les propriétés 'class_id', 'object_remote_id', 'sugar_id'
		$ez_properties['class_id'] = $ezclassID;
		$ez_properties['object_remote_id'] = $remoteID;
		$sugar_properties['sugar_id'] = $sugarid;
		
		// get des valeurs des attributes de la table sugar
		// ex.: $object_attributes = array('attr_1' => 'test attr 1', 'attr_2' => 'test attr 2');
		$object_attributes = $sugarSynchro->getSynchroFieldsValues($sugar_properties);
		$ez_properties['object_attributes'] = $object_attributes;
		
		// si $objectsMaster n'existe pas on crée une nouvelle instance
		if(!isset($objectsMaster))			
			// nouvelle instance de owObjectsMaster()
			$objectsMaster = owObjectsMaster::instance($ez_properties);	
		else
			$objectsMaster->setProperties($ez_properties);
		
			
		$object = eZContentObject::fetchByRemoteID( $remoteID );
		if( !$object )
		{
			// debug notice
			eZDebug::writeNotice("remote ID " . $remoteID . " non trouvé.\n Procede à la creation de l'objet.");
			//debug notice
			$notice = $objectsMaster->getProperties();
			
			// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
			// CRÉE UN NOUVEAU OBJET EZ ***
			// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
			
			// crée un nouveau objet de contenu EZ
			$createObject = $objectsMaster->createObjectEz();
			
			$result = $createObject;
		}
		else
		{
			// debug notice
			eZDebug::writeNotice("remote ID " . $remoteID . " trouvé.\n Procede à la mise à jour de l'objet.");
			
			// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
			// MET À JOUR L'OBJET EZ EXISTANT ***
			// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
			
			// reinsegne la propriété 'content_object'
			$ez_properties['content_object'] = $object;
			$objectsMaster->setProperties($ez_properties);
			
			//debug notice
			$notice = $objectsMaster->getProperties();
			
		    // met à jour l'objet EZ existant
		    $updateObject = $objectsMaster->updateObjectEz();
		    
		    $result = $updateObject;
		}
	}
	
}


$tpl = templateInit();
$tpl->setVariable('result',$result);
$tpl->setVariable('notice',$notice);
$tpl->setVariable('title', "Import de module et objets SUGAR");
$tpl->setVariable('subtitle', "Creation à la volée d'objet et classes EZ ou Update d'objets existants");
$Result = array();
$Result['content'] = $tpl->fetch( 'design:sugarcrm/notice_result.tpl' );
$Result['pagelayout']=false;
$Result['path'] = array( array( 'url' => false,
                               'text' => 'import' ) );

?>

