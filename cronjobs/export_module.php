<?php

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

include_once( 'extension/connectezsugar/classes/Module.php' );

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);
$cli->beginout('export_module.php');

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
	
	// H19873 : Pour éviter de boucler chaque jour sur les mêmes fiches à resynchroniser, on affecte la date d'import du module
	if ( $simulation == 'sync' ) {
		$ini_synchro = eZINI::instance( 'synchro.ini.append.php' );
		$date_import_module = $ini_synchro->variable('import_module', 'last_synchro_' . $sugarmodule);
		$module->set_last_synchro_date_time( 'export_module', $date_import_module );
	}
}
$cli->endout('export_module.php');
?>