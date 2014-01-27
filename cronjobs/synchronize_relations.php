<?php

	include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );
	
	gc_enable();
	
	// init CLI
	$cli = SmartCLI::instance();
	$cli->setIsQuiet(false);
	$cli->beginout("synchronize_relations.php");
	$cli->notice('course::DEMARRAGE synchronize_relations.php');
	    
	// Initialisation du code retour
	$GLOBALS['cr'] = 0;
	
	$date = date("mdy");

	$arguments = array_slice( $_SERVER['argv'], 1 );
	
	// connexion à SUGAR
	$sugarConnector = new SugarConnector();
	$connection     = $sugarConnector->login();
		
	$simulation 	= isset($arguments[ 1 ]) ? $arguments[ 1 ] : 'sync';
	 
	// modules SUGAR à synchroniser
	$modules_list = SugarSynchro::getModuleListToImport();
	$cli->gnotice("mem_test::Mémoire utilisée avant boucle sur les modules : " . memory_get_usage_hr());
		
	foreach ( $modules_list as $sugarmodule ) {
	    $cli->gnotice("mem_test::Mémoire utilisée debut import relations module $sugarmodule : " . memory_get_usage_hr());
	    exec( "php runcronjobs.php --debug --logfiles importrelationsmodule $sugarmodule $simulation", $output, $cr );  
	    gc_collect_cycles();
	    $cli->gnotice("mem_test::Mémoire utilisée fin import relations module : " . memory_get_usage_hr());
	    if ( $cr != 0 ) {
	    	$GLOBALS['cr'] = 1;
	    }	    
	}
	
	$cli->notice( 'return_code::' . $GLOBALS['cr'] );
	$cli->notice( 'course::FIN' );
	
	$script->setExitCode( $GLOBALS['cr'] );
	$script->exitCode();
	print "return_code::synchronize_relations::$date::" . $GLOBALS['cr']."\n";
	
	
	// fin du script	
	$cli->endout("synchronize_relations.php");
?>