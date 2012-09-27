<?php

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

gc_enable();

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);
$cli->beginout("synchronize_relations.php");

$arguments = array_slice( $_SERVER['argv'], 1 );

if ( !isset( $arguments[ 1 ] ) ) {
	$cli->error( "Usage:    php runcronjobs.php importrelations <mode> " );
	$cli->error( "Example:  php runcronjobs.php importrelations [ sync | simul ]" );
} else {
	// connexion à SUGAR
	$sugarConnector = new SugarConnector();
	$connection     = $sugarConnector->login();
	
	$simulation     = $arguments[ 1 ];
 
	// modules SUGAR à synchroniser
	$modules_list = SugarSynchro::getModuleListToSynchro();
	$cli->gnotice("Mémoire utilisée avant boucle sur les modules : " . memory_get_usage_hr());
	
	foreach ( $modules_list as $sugarmodule ) {
	    $cli->gnotice("Mémoire utilisée debut import relations module $sugarmodule : " . memory_get_usage_hr());
	    exec("php runcronjobs.php --debug --logfiles importrelationsmodule $sugarmodule $simulation");  
	    gc_collect_cycles();
	    $cli->gnotice("Mémoire utilisée fin import relations module : " . memory_get_usage_hr());
	}
}

$cli->endout("synchronize_relations.php");
?>