<?php

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

include_once( 'extension/connectezsugar/classes/Module.php' );

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);
$cli->beginout("synchro_relations_module.php");

$arguments   = array_slice( $_SERVER['argv'], 1 );
$sugarmodule = $arguments[ 1 ];

if ( !isset( $arguments[ 1 ] ) || !isset( $arguments[ 2 ] ) ) {
	$cli->error( "Usage:    php runcronjobs.php importrelationsmodule <sugar_module> <mode> " );
	$cli->error( "Example:  php runcronjobs.php importrelationsmodule $sugarmodule [ sync | simul ]" );
} else {
	// connexion à SUGAR
	$sugarConnector = new SugarConnector();
	$connection     = $sugarConnector->login();
	
	$simulation     = $arguments[ 2 ];
	$all            = isset ( $arguments[ 3 ] );
	
	$cli->notice("*******************************************");
	$cli->notice("Sugar Module : $sugarmodule");
	$cli->notice("*******************************************");
	
	$module = new Module( $sugarmodule, $cli, $simulation );
	if ( $simulation != 'check' ) {
		if ( $all ) {
			$cli->notice( '** Import all relations' );
			$module->import_module_relations_all( );
		} else {
			$cli->notice( '** Import relations from last synchro' );
			$module->import_module_relations( );
		}
	}
}

$cli->endout("synchro_relations_module.php");
?>