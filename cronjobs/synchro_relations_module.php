<?php

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

include_once( 'extension/connectezsugar/classes/Module.php' );

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);
$cli->beginout("synchro_relations_module.php");

$arguments = array_slice( $_SERVER['argv'], 1 );
$sugarmodule = $arguments[ 1 ];
$all = isset ( $arguments[ 2 ] );

$cli->notice("*******************************************");
$cli->notice("Sugar Module : $sugarmodule");
$cli->notice("*******************************************");

$module = new Module( $sugarmodule, $cli );
if ( $all ) {
	$cli->notice( '** Import all relations' );
	$module->import_module_relations_all( );
} else {
	$cli->notice( '** Import relations from last synchro' );
	$module->import_module_relations( );
}

$cli->endout("synchro_relations_module.php");
?>