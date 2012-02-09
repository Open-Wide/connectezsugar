<?php
include_once( 'kernel/common/template.php' );

if (isset ($Params["sugarid"]))
{
   $sugarid = $Params["sugarid"];
}
else
{
   $sugarid=null;
}

eZDebug::writeError("Sugar Identifiant ".$sugarid);
$result=$sugarid;
$objet = eZContentObject::fetchByRemoteID( 'hotel_'.$sugarid );
if( !$objet )
{
    $result='Object inexistant : '.$sugarid;
}
else
{
    $current_eZ_version = $objet->createNewVersion( false, true, 'fre-FR' );
    $dataMap  = $current_eZ_version->attribute( 'data_map' );

    $sugarConnector=new SugarConnector();
    $connection=$sugarConnector->login('admin','admin');
    $sugardata = $sugarConnector->get_entry('OTCP_Hotel',$sugarid,array('name','description'));
    $hotel=$sugardata['entry_list'][0];
    $msg='';
    foreach ($hotel['name_value_list'] as $fields)
    {
        if ($fields['name']=='description')
        {
            $attributeName='short_description';
        }
        else
        {
            $attributeName=$fields['name'];
        } 
        $msg=$msg.$fields['name'].' ';
        $contentObjectAttribute=$dataMap[$attributeName];
        if( $contentObjectAttribute )
        {
            $contentObjectAttribute->fromString($fields['value']);
            $contentObjectAttribute->store();
            $msg=$msg.'value='.$fields['value'].' ';
        }
        else
        {
            $msg=$msg.'erreur attributes';
        }
    }
    $objet->store();
    eZOperationHandler::execute(
              'content',
              'publish',
               array(
                   'object_id' => $objet->attribute( 'id' ),
                   'version'   => $current_eZ_version->attribute( 'version' ),
                  )
            );

    $result=$sugarid." synchrone ".$msg;
}


$tpl =& templateInit();
$tpl->setVariable('sugarid',$result);
$Result = array();
$Result['content'] =& $tpl->fetch( 'design:sugarcrm/synchro.tpl' );
$Result['pagelayout']=false;
$Result['path'] = array( array( 'url' => false,
                               'text' => 'synchroEZ' ) );

?>

