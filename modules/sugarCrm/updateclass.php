<?php
include_once( 'kernel/common/template.php' );

if (isset ($Params["sugarmodule"]))
   $sugarmodule = $Params["sugarmodule"];
else
   $sugarmodule="otcp_room";

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
		//$class_attributes = owObjectsMaster::normalizeIdentifiers($sugar_attributes);
		$ez_properties['class_attributes'] = $class_attributes;
		
		$ezclassID = eZContentClass::classIDByIdentifier($class_identifier);
		if(!$ezclassID)
		{
			$notice[] = "class de contenu EZ " . $class_identifier . " non trouvé.\n Procede à la creation de la class.";
			$action = "create";
		}
		else
		{
			// reinsegne les propriétés 'class_id'
			$ez_properties['class_id'] = $ezclassID;
			$action = "update";
		}
	}
	
	
	if($continue)
	{
		// nouvelle instance de owObjectsMaster()
		$objectsMaster = owObjectsMaster::instance($ez_properties);
		if( !is_object($objectsMaster) )
		{
			$notice['owObjectsMaster.log'] = owObjectsMaster::lastLogContent();
			$continue = false;
		}
	}
	
	
	if($continue)
	{
		if( $action == "create" )
		{
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
		elseif( $action == "update" )
		{
			// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
			// UPDATE DE LA CLASS EZ ***
			// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
			
			// verifie si la strucuture du model SUGAR n'a pas changé
			$verif  = $sugarSynchro->verifyClassAttributes($ez_properties);
			if(!$verif)
				$continue = false;
			elseif( is_array($verif) )
			{
				// OPTION 1 : si un attribute SUGAR n'existe pas chez EZ on ne met pas à jour l'objet
				// et on log les divergences. @TODO : envoyer un mail d'alerte
				/*$result['verifyClassAttributes'] = "ERROR : il y a des divergences entre les attributes de la class EZ et SUGAR";
				$result['attributes_not_founds'] = $verif;
				$continue = false;*/
				
				// OPTION 2 : si un attribute SUGAR n'existe pas chez EZ on le crée
				foreach($verif as $attr)
				{
					$newattr[] = $objectsMaster->createClassAttribute($attr);
					$result[] = "nouveu attribut avec identifier " . $attr['identifier'] . " crée pou la class " . $ez_properties['class_identifier'];
				}
				
				//$notice[] = $newattr;
				
				$continue = true;
			}
			else
			{
				// continue l'execution du script
				$continue = true;
				$result[] = "Aucun changement pour la class " . $ez_properties['class_identifier'];
				$notice['class_attributes'] = $ez_properties['class_attributes'];
			}
			
		}
		
		
	}
	
	
	/*if($continue)
	{
		
		// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
		// MET À JOUR TOUS LES OBJETS DE LA CLASS EZ EXISTANTS ***
		// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
		
		//debug notice
		$notice['objectsMaster_getProperties'] = $objectsMaster->getProperties();
		
		// fait un update la class de contenu EZ
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
		
	}*/
}


$tpl = templateInit();
$tpl->setVariable('result',$result);
$tpl->setVariable('notice',$notice);
$tpl->setVariable('title', "Update Class EZ du module SUGAR : " . $sugarmodule);
$Result = array();
$Result['content'] = $tpl->fetch( 'design:sugarcrm/notice_result.tpl' );
$Result['pagelayout']=false;
$Result['path'] = array( array( 'url' => false,
                               'text' => 'querysugar' ) );

?>
