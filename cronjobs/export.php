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

// connexion à SUGAR
$sugarConnector = new SugarConnector();
$connection = $sugarConnector->login();
 
// modules SUGAR à synchroniser
$modules_list = SugarSynchro::getModuleListToSynchro();
$cli->gnotice("Mémoire utilisée avant boucle sur les modules : " . memory_get_usage_hr());

foreach($modules_list as $sugarmodule) {
    $cli->gnotice("Mémoire utilisée debut export module $sugarmodule : " . memory_get_usage_hr());
    
    $module = new Module($sugarmodule);
    $return = $module->export_module_objects();
    $cli->gnotice(print_r($return, true));    
    
    gc_collect_cycles();
    $cli->gnotice("Mémoire utilisée fin export module : " . memory_get_usage_hr());
}


// fin du script
$cli->endout("export.php");

?>