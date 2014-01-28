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
	
	$date = date("mdy");
	    
	// Initialisation du code retour
	$GLOBALS['cr'] = 0;
	
	// debut du script
	$cli->beginout("export.php");
	$cli->notice('course::DEMARRAGE export.php');
	
	$arguments = array_slice( $_SERVER['argv'], 1 );
	
	// connexion à SUGAR
	$sugarConnector = new SugarConnector();
	$connection = $sugarConnector->login();
		
	$simulation = isset($arguments[ 1 ]) ? $arguments[ 1 ] : 'sync';
	 
	// modules SUGAR à synchroniser
	$modules_list = SugarSynchro::getModuleListToExport();
	$cli->gnotice("mem_test::Mémoire utilisée avant boucle sur les modules : " . memory_get_usage_hr());
	
	foreach ( $modules_list as $sugarmodule ) {
	    $cli->notice("mem_test::Mémoire utilisée debut export module $sugarmodule : " . memory_get_usage_hr());
	    exec( "php runcronjobs.php --debug --logfiles exportmodule $sugarmodule $simulation", $output, $cr );  
	    gc_collect_cycles();
	    $cli->notice("mem_test::Mémoire utilisée fin export module : " . memory_get_usage_hr());
	    if ( $cr != 0 ) {
	    	$GLOBALS['cr'] = 1;
	    }
	}
	
	$cli->notice( "return_code::" . $GLOBALS['cr'] );
	$cli->notice( 'course::FIN' );
	
	$script->setExitCode( $GLOBALS['cr'] );
	$script->exitCode();
	print "return_code::export::$date::" . $GLOBALS['cr']."\n";
	
	OWMonitoringStatus::writeCR( "export::$date::", $GLOBALS['cr']);
	
	
	// fin du script
	$cli->endout("export.php");

?>