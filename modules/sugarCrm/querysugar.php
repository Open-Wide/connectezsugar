<?php
include_once( 'kernel/common/template.php' );

if (isset ($Params["query"]))
   $query = $Params["query"];
else
   $query="get_available_modules";

if (isset ($Params["sugarmodule"]))
   $sugarmodule = $Params["sugarmodule"];
else
   $sugarmodule="test_Hotel";
   
if (isset ($Params["sugarid"]))
   $sugarid = $Params["sugarid"];
else
   $sugarid="df72c261-9645-ec3a-d369-4f2a6ca53650";


eZDebug::writeNotice("Query : " . $query);
eZDebug::writeNotice("Sugar Module : " . $sugarmodule); 
eZDebug::writeNotice("Sugar Id : " . $sugarid);

$notice = null;
$result = null;

// connexion à SUGAR
$sugarConnector=new SugarConnector();
$connection=$sugarConnector->login('admin','admin');

switch($query)
{
	case "get_available_modules" :
		$sugardata = $sugarConnector->get_available_modules();
		$result=$sugardata;
		break;
	case "get_module_fields" :
		$sugardata = $sugarConnector->get_module_fields($sugarmodule);
		$result=$sugardata['module_fields'];
		break;
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