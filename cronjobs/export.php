<?php
/*
Cronjob pour l'export des objets EZ vers SUGAR
*/

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

include_once( 'extension/connectezsugar/classes/Module.php' );

gc_enable();

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);

// debut du script
$cli->beginout("export.php");

$arguments = array_slice( $_SERVER['argv'], 1 );

if ( !isset( $arguments[ 1 ] ) ) {
	$cli->error( "Usage:    php runcronjobs.php export <mode> " );
	$cli->error( "Example:  php runcronjobs.php export [ sync | simul ]" );
} else {
	// connexion à SUGAR
	$sugarConnector = new SugarConnector();
	$connection = $sugarConnector->login();
	
	$simulation = $arguments[ 1 ];
 
	// modules SUGAR à synchroniser
	$modules_list = SugarSynchro::getModuleListToSynchro();
	$cli->gnotice("Mémoire utilisée avant boucle sur les modules : " . memory_get_usage_hr());
	
	foreach ( $modules_list as $sugarmodule ) {
	    $cli->gnotice("Mémoire utilisée debut export module $sugarmodule : " . memory_get_usage_hr());
	    exec("php runcronjobs.php --debug --logfiles exportmodule $sugarmodule $simulation");  
	    gc_collect_cycles();
	    $cli->gnotice("Mémoire utilisée fin export module : " . memory_get_usage_hr());
	}
}


// fin du script
$cli->endout("export.php");

?>