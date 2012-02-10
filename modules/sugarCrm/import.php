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
$logger = owLogger::CreateForAdd("var/log/sugarCrm_import.log");

//iconv_set_encoding("input_encoding", "UTF-8");
//iconv_set_encoding("output_encoding", "UTF-8");
//iconv_set_encoding("internal_encoding", "UTF-8");
//exit(var_dump(iconv_get_encoding('all')));
//exit(mb_http_output());

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
			
	if( is_array($sugar_attributes) and is_array($class_attributes) )
		$continue = true;
	
	if($continue)
	{
		//$class_attributes = owObjectsMaster::normalizeIdentifiers($sugar_attributes);
		$ez_properties['class_attributes'] = $class_attributes;
		
		$ezclassID = eZContentClass::classIDByIdentifier($class_identifier);
		if(!$ezclassID)
		{
			// debug notice
			eZDebug::writeNotice("class de contenu EZ " . $class_identifier . " non trouvé.\n Procede à la creation de la class.");
			
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
			
			// nouvelle instance de owObjectsMaster()
			$objectsMaster = owObjectsMaster::instance($ez_properties);
			//debug notice
			$notice = $objectsMaster->getProperties();
			// crée la nouvelle class de contenu EZ
			$createClass = $objectsMaster->createClassEz($ez_properties);
			if($createClass)
			{
				$result[] = "La class " . $class_identifier . " a été creé avec succes!";
				// get de l'id de la class crée
				$ezclassID = eZContentClass::classIDByIdentifier($class_identifier);
				// continue l'execution du script
				$continue = true;
				//$continue = false;
			}	
			else
				//$result = "Problème rencontré dans la création de la class " . $sugarmodule
					//		. ". Voir le log pour plus de details.";
				$result[] = $createClass;
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
	}
	
	if($continue)
	{
		// @ TODO : prevoir la possibilité de rendre parametrable la construction du remote_id
		// le remote_id des objet a la forme '$sugarmodule_$sugarid' !
		$remoteID = $class_identifier . '_' . $sugarid;
		
		// reinsegne les propriétés 'class_id', 'object_remote_id', 'sugar_id'
		$ez_properties['class_id'] = $ezclassID;
		$ez_properties['object_remote_id'] = $remoteID;
		$sugar_properties['sugar_id'] = $sugarid;
		
		// get des valeurs des attributes de la table sugar
		// ex.: $sugar_attributes_values = array('attr_1' => 'test attr 1', 'attr_2' => 'test attr 2');
		$sugar_attributes_values = $sugarSynchro->getSugarFieldsValues($sugar_properties);
		if(!$sugar_attributes_values)
			$result[] = "\$sugarSynchro->getSugarFieldsValues(\$sugar_properties) return false !!!";

		$object_attributes = $sugarSynchro->synchronizeFieldsNames($sugar_attributes_values);
		if(!$object_attributes)
			$result[] = "\$sugarSynchro->synchronizeFieldsNames(\$sugar_attributes_values) return false !!!";
		
		if( !$sugar_attributes_values  or !$object_attributes )
			$continue = false;
			
		if( $continue )
		{
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
				
				if($createObject)
					$result[] = "L'objet avec remote_id " . $remoteID . " a été creé avec succes!";
				else
					$result[] = array( 'createObjectEz()' => $createObject );
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
				$notice = $objectsMaster->getProperty('object_attributes');
				
			    // met à jour l'objet EZ existant
			    $updateObject = $objectsMaster->updateObjectEz();
			    
			    $result[] = array( 'updateObjectEz()' => $updateObject );
			}
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

