<?php

include_once( 'kernel/common/template.php' );
include_once( 'extension/connectezsugar/classes/Module_Schema.php' );
include_once( 'extension/connectezsugar/classes/Module.php' );
include_once( 'extension/connectezsugar/classes/Module_Object.php' );

$sugar_module = $Params[ 'sugarmodule' ];
$sugar_id 	  = $Params[ 'sugarid' ];
$notice       = null;
$result       = null;

if( is_null( $sugar_module ) or is_null( $sugar_id ) ) {
	$result[ ] = 'sugar_module et/ou remote_id manquant !!!';
} else {

	$ez_identifier = Module_Schema::get_ez_class_identifier( $sugar_module );
	
	eZDebug::writeDebug( '[SugarCRM => eZ] remote_id = ' . $ez_identifier . '_' . $sugar_id );
	
	try {
		$sugarConnector = new SugarConnector( );
		$connection     = $sugarConnector->login( );
	} catch ( Exception $e ) {
		$result[ ] = $e->getMessage( );
	}
	$select_fields  = Module::load_include_fields( $sugar_module );
	$sugardata 		= $sugarConnector->get_entry( $sugar_module, $sugar_id, $select_fields );
	$num_item       = 1;
	$cli            = null;
	$simulation     = false;
	
	if (isset ( $sugardata[ 'data' ] ) ) {
		try {
			$schema = new Module_Schema( $sugar_module, $cli );
			$schema->load_relations( );
			
			$object = new Module_Object( $sugar_module, $sugar_id, $schema, $cli, $simulation, $num_item );
			$object->import( $sugardata );
			
			$notice[ ] = implode( '<br />', $object->logs );
			eZDebug::writeDebug( implode( "\n", array_merge( $object->logs, array('--------------') ) ) );
			unset( $object, $sugardata );
		} catch ( Exception $e ) {
			$result[ ] = $e->getMessage( );
		}
	} else {
		$result[ ] = 'Aucune donnée récupérée par get_entry() pour ID=' . $sugar_id . ' et sugar_module=' . $sugar_module;
		$result[] = var_dump($sugardata);
	}
}

$tpl = templateInit( );
$tpl->setVariable( 'result', $result );
$tpl->setVariable( 'notice', $notice );
$tpl->setVariable( 'title', 'Import d\'un objet SUGAR' );
$tpl->setVariable( 'subtitle', 'Creation / Update d\'objets existants' );
$Result = array( );
$Result['content']    = $tpl->fetch( 'design:sugarcrm/notice_result.tpl' );
$Result['pagelayout'] = false;
$Result['path']       = array(
	array(
		'url'  => false,
		'text' => 'import',
	)
);

?>