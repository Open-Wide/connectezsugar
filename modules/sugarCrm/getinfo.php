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
    $template = 'erreur.tpl';
    $node=null;
}
else
{
    $template='getinfo.tpl';
    $node=$objet;
}


$tpl =& templateInit();
$tpl->setVariable('sugarid',$result);
$tpl->setVariable('node',$node);
$Result = array();
$Result['content'] =& $tpl->fetch( 'design:sugarcrm/'.$template );
$Result['pagelayout']=false;
$Result['path'] = array( array( 'url' => false,
                               'text' => 'synchroEZ' ) );

?>

