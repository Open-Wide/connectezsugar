<?php
include_once( 'kernel/common/template.php' );

if(isset($Params["type"]))
	$type = $Params["type"];
else
   $type = null;
   
if(isset($Params["identifier"]))
   $identifier = $Params["identifier"];
elseif( isset($type) and $type == "class")
	$identifier = "hotel";
elseif( isset($type) and $type == "object")
	$identifier = "hotel_df72c261-9645-ec3a-d369-4f2a6ca53650";
else
	$identifier = null;

$notice = null;
$result = null;
   
if( is_null($type) or is_null($identifier) )
{
	$notice = "type et/ou identifier manquant !!!";
}
else
{  
	switch($type)
	{
		case "class" :
			$classID = eZContentClass::classIDByIdentifier($identifier);
			if(!$classID)
				$notice = "La class avec identifiant " . $identifier . " n'est pas connue !";
			else
			{
				$deleteClass = eZContentClassOperations::remove($classID);
				if($deleteClass)
					$result = "La class " . $identifier . " a été supprimé avec succes !";
				else
					$result = "La class " . $identifier . " n'a pas pu être supprimé !";
				// clear cache
				eZCache::clearAll();
			}
			break;
		case "object" :
			$id = owObjectsMaster::objectIDByRemoteID($identifier);
			
			if(!$id)
			{
				$notice[] = "L'ID de l'objet avec remote_id " . $identifier . " n'existe pas dans la base !";
				break;
			}
			else
				$notice[] = "L'objet avec remote_id " . $identifier . " a l'ID : " . $id;
			
			$objectToDelete = eZContentObject::fetch($id);
			if(!$objectToDelete)
			{
				$notice[] = "L'objet avec id " . $id . " n'est pas connu !\n Procede au nettoyage de la base";
				$purgeObject = owObjectsMaster::objectPurge($id);
				if($purgeObject)
					$result = "Toute trace de L'objet avec remote_id " . $identifier . " ont été supprimé de la base avec succes !";
				else
					$result = "L'objet avec remote_id " . $identifier . " n'a pas été trouvé dans la table ezcontentobject !";
			}
			else
			{
				$deleteObject = eZContentObjectOperations::remove($id);
				if($deleteObject)
					$result = "L'objet avec remote_id " . $identifier . " a été supprimé avec succes !";
				else
					$result = "L'objet avec remote_id " . $identifier . " n'a pas pu être supprimé !";
				// clear cache
				eZCache::clearAll();
			}
			break;
		default :
			$notice = $type . " n'est pas un type connu !";
			break;
	}
	
}


$tpl = templateInit();
$tpl->setVariable('result',$result);
$tpl->setVariable('notice',$notice);
$tpl->setVariable('title',"CLEANUP : " . $type . " -> " . $identifier);
$Result = array();
$Result['content'] = $tpl->fetch( 'design:sugarcrm/notice_result.tpl' );
$Result['pagelayout']=false;
$Result['path'] = array( array( 'url' => false,
                               'text' => 'cleanup' ) );

?>

