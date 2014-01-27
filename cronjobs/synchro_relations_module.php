<?php

	include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );
	
	include_once( 'extension/connectezsugar/classes/Module.php' );
	
	// init CLI
	$cli = SmartCLI::instance();
	$cli->setIsQuiet(false);
	$cli->beginout("synchro_relations_module.php");
	    
	// Initialisation du code retour
	$GLOBALS['partial_cr'] = 0;

	$arguments = array_slice( $_SERVER['argv'], 1 );
	
	if ( in_array($arguments[ 0 ], array('--debug', '--logfiles' ) ) ) {
		$slice = 1;
		if ( in_array($arguments[ 1 ], array('--debug', '--logfiles' ) ) ) {
			$slice++;
		}
		$arguments = array_slice( $arguments, $slice );
	}
	
	$sugarmodule = $arguments[ 1 ];
	
	if ( !isset( $arguments[ 1 ] ) || !isset( $arguments[ 2 ] ) ) {
		$cli->error( "Usage:    php runcronjobs.php importrelationsmodule <sugar_module> <mode> " );
		$cli->error( "Example:  php runcronjobs.php importrelationsmodule $sugarmodule [ sync | simul | check_relations ]" );
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
		if ( !in_array($simulation, array( 'check', 'check_relations' ) ) ) {
			if ( $all ) {
				$cli->notice( '** Import all relations' );
				$module->import_module_relations_all( );
			} else {
				$cli->notice( '** Import relations from last synchro' );
				$module->import_module_relations( );
			}
		}
	}
	
	$cli->notice( 'partial_return_code::' . $GLOBALS['partial_cr'] );
	$cli->notice( 'course::FIN' );
	
	$script->setExitCode( $GLOBALS['partial_cr'] );
	$script->exitCode();
	print $GLOBALS['partial_cr']."\n";
	
	$cli->endout("synchro_relations_module.php");
	
?>