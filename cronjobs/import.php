<?php
/*
Cronjob pour l'import des objets SUGAR vers eZ
*/

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

include_once( 'extension/connectezsugar/classes/Module.php' );

gc_enable();

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);

// debut du script
$cli->beginout("import.php");

$arguments = array_slice( $_SERVER['argv'], 1 );

if ( !isset( $arguments[ 1 ] ) ) {
	$cli->error( "Usage:    php runcronjobs.php import <mode> " );
	$cli->error( "Example:  php runcronjobs.php import [ sync | simul ]" );
} else {
	// connexion à SUGAR
	$sugarConnector = new SugarConnector();
	$connection = $sugarConnector->login();
	
	$simulation = $arguments[ 1 ];
 
	// modules SUGAR à synchroniser
	$modules_list = SugarSynchro::getModuleListToSynchro();
	$cli->gnotice("Mémoire utilisée avant boucle sur les modules : " . memory_get_usage_hr());
	
	foreach ( $modules_list as $sugarmodule ) {
	    $cli->gnotice("Mémoire utilisée debut import module $sugarmodule : " . memory_get_usage_hr());
	    exec("php runcronjobs.php --debug --logfiles importmodule $sugarmodule $simulation");  
	    gc_collect_cycles();
	    $cli->gnotice("Mémoire utilisée fin import module : " . memory_get_usage_hr());
	}
}


// fin du script
$cli->endout("import.php");

?>