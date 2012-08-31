<?php

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

include_once( 'extension/connectezsugar/classes/Module.php' );

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);
$cli->beginout('import_module.php');

$arguments   = array_slice( $_SERVER['argv'], 1 );
$sugarmodule = $arguments[ 1 ];

if ( !isset( $arguments[ 1 ] ) || !isset( $arguments[ 2 ] ) ) {
	$cli->error( "Usage:    php runcronjobs.php import_module <sugar_module> <mode> " );
	$cli->error( "Example:  php runcronjobs.php import_module $sugarmodule [ sync | simul ]" );
} else {
	// connexion à SUGAR
	$sugarConnector = new SugarConnector();
	$connection     = $sugarConnector->login();
	
	$simulation     = ( $arguments[ 2 ] != 'sync' );

	$cli->notice('*******************************************');
	$cli->notice("Sugar Module : $sugarmodule");
	$cli->notice('*******************************************');
	
	$module = new Module( $sugarmodule, $cli, $simulation );
	$module->import_module_objects( );
}
$cli->endout('import_module.php');
?>