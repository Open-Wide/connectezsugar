<?php

	include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );
	
	include_once( 'extension/connectezsugar/classes/Module.php' );
	
	// init CLI
	$cli = SmartCLI::instance();
	$cli->setIsQuiet(false);
	$cli->beginout('export_module.php');
	    
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
	
	if ( !isset( $arguments[ 2 ] ) ) {
		$cli->error( "Usage:    php runcronjobs.php export_module <sugar_module> <mode> " );
		$cli->error( "Example:  php runcronjobs.php export_module $sugarmodule [ sync | simul ]" );
	} else {
		// connexion à SUGAR
		$sugarConnector = new SugarConnector();
		$connection = $sugarConnector->login();
		
		$simulation = $arguments[ 2 ];
		
		$cli->notice('*******************************************');
		$cli->notice("Sugar Module : $sugarmodule");
		$cli->notice('*******************************************');
		
		$module = new Module( $sugarmodule, $cli, $simulation );
		if ( $simulation != 'check' ) {
			$module->export_module_objects( );
		}
	}
	
	$cli->notice( 'partial_return_code::' . $GLOBALS['partial_cr'] );
	$cli->notice( 'course::FIN' );
	
	$script->setExitCode( $GLOBALS['partial_cr'] );
	$script->exitCode();
	print $GLOBALS['partial_cr']."\n";
	
	$cli->endout('export_module.php');
	
?>