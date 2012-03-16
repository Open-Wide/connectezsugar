<?php
include_once( 'kernel/common/template.php' );
include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

if(isset($Params["sugarmodule"]))
	$sugarmodule = $Params["sugarmodule"];
else
   $sugarmodule="otcp_room";
   
if(isset($Params["sugarid"]))
   $sugarid = $Params["sugarid"];
else
   $sugarid="71b4bfd0-0010-e187-c9dc-4f549e8988f1";

// $sugarid="df72c261-9645-ec3a-d369-4f2a6ca53650";
  
$notice = null;
$result = null;
$continue = true;
$logger = owLogger::CreateForAdd("var/log/sugarCrm_import.log");

if( is_null($sugarmodule) or is_null($sugarid) )
{
	$result[] = "sugarmodule et/ou sugarid manquant !!!";
}
else
{  
	eZDebug::writeNotice("Sugar Module : ". $sugarmodule); 
	eZDebug::writeNotice("Sugar Identifiant : ". $sugarid);
	
	// initialise les tableaux $ez_properties et $sugar_properties
	$ez_properties = array();
	$sugar_properties = array();
	
	// instance de la class SugarSynchro
	$sugarSynchro = SugarSynchro::instance();
	if( !is_object($sugarSynchro) )
	{
		$notice['SugarSynchro.log'] = SugarSynchro::lastLogContent();
		$continue = false;
	}
	
	if($continue)
	{
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
		else
		{
			$notice['SugarSynchro.log'] = SugarSynchro::lastLogContent();
			$continue = false;
		}
			
	}
	
	
	if($continue)
	{
		// OPTION 1 : definie $class_attributes après avoir normalisé les identifiants
		//$class_attributes = owObjectsMaster::normalizeIdentifiers($sugar_attributes);
		// OPTION 2 : ne change rien au tableau
		$ez_properties['class_attributes'] = $class_attributes;
		
		// nouvelle instance de owObjectsMaster()
		$objectsMaster = owObjectsMaster::instance($ez_properties);
		if( !is_object($objectsMaster) )
		{
			$notice['owObjectsMaster.log'] = owObjectsMaster::lastLogContent();
			$continue = false;
		}
		else
		{
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
				
				$objectsMaster->setProperty('class_object_name',$ez_properties['class_object_name']);
				
				//debug notice
				$notice['objectsMaster->getProperties()'] = $objectsMaster->getProperties();
				
				// crée la nouvelle class de contenu EZ
				$createClass = $objectsMaster->createClassEz($ez_properties);
				if($createClass)
				{
					$result[] = "La class " . $class_identifier . " a été creé avec succes!";
					// get de l'id de la class crée
					$ezclassID = eZContentClass::classIDByIdentifier($class_identifier);
					// reinsegne la propriété 'class_id'
					$ez_properties['class_id'] = $ezclassID;
					// continue l'execution du script
					$continue = true;
				}	
				else
				{
					$notice['owObjectsMaster.log'] = owObjectsMaster::lastLogContent();
					$result['createClassEz()'] = $createClass;
					$continue = false;
				}
					
			}
			else
			{	
				// debug notice
				eZDebug::writeNotice("ezclassID : ". $ezclassID);
				
				// reinsegne la propriété 'class_id'
				$ez_properties['class_id'] = $ezclassID;
				
				// verifie si la strucuture du model SUGAR n'a pas changé
				$verif  = $sugarSynchro->verifyClassAttributes($ez_properties);
				if(!$verif)
					$continue = false;
				elseif( is_array($verif) )
				{
					// OPTION 1 : si un attribute SUGAR n'existe pas chez EZ on ne met pas à jour l'objet
					// et on log les divergences. @TODO : envoyer un mail d'alerte
					$result['verifyClassAttributes'] = "ERROR : il y a des divergences entre les attributes de la class EZ et SUGAR";
					$result['attributes_not_founds'] = $verif;
					$continue = false;
					
					// OPTION 2 : si un attribute SUGAR n'existe pas chez EZ on le crée
					/*$objectsMaster->setProperty('class_id',$ezclassID);
					foreach($verif as $attr)
					{
						$newattr[] = $objectsMaster->createClassAttribute($attr);
						$result[] = "nouveu attribut avec identifier " . $attr['identifier'] . " crée pou la class " . $ez_properties['class_identifier'];
					}
					
					$continue = true;*/
				}
				else
				{
					// continue l'execution du script
					$continue = true;
				}
			}
		}
		
	}
	
	if($continue)
	{
		// @ TODO : prevoir la possibilité de rendre parametrable la construction du remote_id
		// le remote_id des objet a la forme '$sugarmodule_$sugarid' !
		$remoteID = $class_identifier . '_' . $sugarid;
		
		// reinsegne les propriétés 'object_remote_id', 'sugar_id'
		$ez_properties['object_remote_id'] = $remoteID;
		$sugar_properties['sugar_id'] = $sugarid;
		
		$sugarSynchro->setProperty('class_attributes',$ez_properties['class_attributes']);
		
		// get des valeurs des attributes de la table sugar
		// ex.: $sugar_attributes_values = array('attr_1' => 'test attr 1', 'attr_2' => 'test attr 2');
		$sugar_attributes_values = $sugarSynchro->getSugarFieldsValues($sugar_properties); //evd($sugar_attributes_values);
		if(!$sugar_attributes_values)
			$result[] = "\$sugarSynchro->getSugarFieldsValues(\$sugar_properties) return false !!!";

		$object_attributes = $sugarSynchro->synchronizeFieldsValues($sugar_attributes_values);
		if(!$object_attributes)
			$result[] = "\$sugarSynchro->synchronizeFieldsValues(\$sugar_attributes_values) return false !!!";
			
		
		if( !$sugar_attributes_values  or !$object_attributes )
		{
			$notice['SugarSynchro.log'] = SugarSynchro::lastLogContent();
			$continue = false;
		}
			
			
		if( $continue )
		{
			$ez_properties['object_attributes'] = $object_attributes;
			
			// si $objectsMaster n'existe pas on crée une nouvelle instance
			if(!isset($objectsMaster))
			{
				// nouvelle instance de owObjectsMaster()
				$objectsMaster = owObjectsMaster::instance($ez_properties);
				if( !is_object($objectsMaster) )
				{
					$notice['owObjectsMaster.log'] = owObjectsMaster::lastLogContent();
					$continue = false;
				}
			}
			else
				$objectsMaster->setProperties($ez_properties);
			
			if($continue)
			{
				$object = eZContentObject::fetchByRemoteID( $remoteID );
				if( !$object )
				{
					// debug notice
					eZDebug::writeNotice("remote ID " . $remoteID . " non trouvé.\n Procede à la creation de l'objet.");
					//debug notice
					$notice['objectsMaster->getProperties()'] = $objectsMaster->getProperties();
					
					// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
					// CRÉE UN NOUVEAU OBJET EZ ***
					// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
					
					// crée un nouveau objet de contenu EZ
					$createObject = $objectsMaster->createObjectEz();
					
					if($createObject)
						$result[] = "L'objet avec remote_id " . $remoteID . " a été creé avec succes!";
					else
					{
						$notice['owObjectsMaster.log'] = owObjectsMaster::lastLogContent();
						$result['createObjectEz()'] = $createObject;
					}
						
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
					$notice['objectsMaster->object_attributes'] = $objectsMaster->getProperty('object_attributes');
					
				    // met à jour l'objet EZ existant
				    $updateObject = $objectsMaster->updateObjectEz();
				    
				    $result['updateObjectEz()'] = $updateObject;
				}
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

