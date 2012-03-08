<?php
include_once( 'kernel/common/template.php' );
include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

if (isset ($Params["query"]))
   $query = $Params["query"];
else
   $query="get_available_modules";

if (isset ($Params["sugarmodule"]))
   $sugarmodule = $Params["sugarmodule"];
else
   $sugarmodule="otcp_room";
   
if (isset ($Params["sugarid"]))
   $sugarid = $Params["sugarid"];
else
   $sugarid="25cc743a-df11-c88c-8e0f-4f3405904130";

// $sugarid="a6fe081b-75b5-c950-60f6-4f3a4e9b434f";
// $sugarid="df72c261-9645-ec3a-d369-4f2a6ca53650"; 
//  $sugarid="25cc743a-df11-c88c-8e0f-4f3405904130";

if (isset ($Params["related_module"]))
   $related_module = $Params["related_module"];
else
   $related_module="otcp_accommodation";
   
eZDebug::writeNotice("Query : " . $query);
eZDebug::writeNotice("Sugar Module : " . $sugarmodule); 
eZDebug::writeNotice("Sugar Id : " . $sugarid);

// init result
$result = null;
// init notice
$notice = null;

$notice['sugarmodule'] = $sugarmodule;
$notice['sugarid'] = $sugarid;
$notice['related_module'] = $related_module;

// connexion à SUGAR
$sugarConnector=new SugarConnector();
$connection=$sugarConnector->login(); //evd($connection);
if( $connection === false )
{
	$notice = $sugarConnector->lastLogContent(true);
}

switch($query)
{
	case "get_available_modules" :
		$sugardata = $sugarConnector->get_available_modules();
		$result = $sugardata;
		break;
	case "get_module_fields" :
		$notice = $sugarmodule;
		$sugardata = $sugarConnector->get_module_fields($sugarmodule);
		$result = $sugardata;
		break;
	case "get_entry_list" :
		$notice = $sugarmodule;
		// get_entry_list($module,$query='',$order_by='',$offset='',$select_fields=array(),$max_results=999,$deleted=false)
		$sugardata = $sugarConnector->get_entry_list($sugarmodule);
		$result = $sugardata;
		break;
	case "get_entry" :
		// get_entry($module,$id,$select_fields=array())
		$sugardata = $sugarConnector->get_entry($sugarmodule,$sugarid);
		$result = $sugardata;
		break;
	case "get_relationships" :
		$sugardata = $sugarConnector->get_relationships($sugarmodule,$sugarid,$related_module);
		
		$relation_name =  $related_module . "_one_name"; //"otcp_accommation_one_name";
		if ( strlen($relation_name) > 25 )
		{
			$relation_name_part_1 = substr($relation_name,0,11);
			$relation_name_part_2 = substr($relation_name,-14);
			$relation_name = $relation_name_part_1 . $relation_name_part_2; //evd($relation_name);
		}
		
		foreach( $sugardata['data'] as $k1 => $v1 )
		{
			foreach( $v1['name_value_list'] as $k2 => $v2 )
			if( $v2['name'] == $relation_name)
			{
				$related_name[] = $v2['value'];
				break;
			}
		}
		$notice['related_name'] = $related_name;
		$result = $sugardata['data'][0]['name_value_list'];
		//$result = $sugardata['data'][0]['name_value_list'];
		break;
	default :
		$result = "I don't know query : " . $query . ", de type : " . gettype($query);
		break;
}





$tpl = templateInit();
$tpl->setVariable('result',$result);
$tpl->setVariable('notice',$notice);
$tpl->setVariable('title', "Interrogation sugarCRM : " . $query);
$Result = array();
$Result['content'] = $tpl->fetch( 'design:sugarcrm/notice_result.tpl' );
$Result['pagelayout']=false;
$Result['path'] = array( array( 'url' => false,
                               'text' => 'querysugar' ) );

?>