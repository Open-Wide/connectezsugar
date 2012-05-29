<?php
/*
Cronjob pour la synchronisation des objets EZ depuis SUGAR

@author Pasquesi Massimiliano <massimiliano.pasquesi@openwide.fr>
*/

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

gc_enable();

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);



// debut du script
$cli->beginout("cleanup_dev.php");

// connexion à SUGAR
$sugarConnector=new SugarConnector();
$connection=$sugarConnector->login();
 
// modules SUGAR à synchroniser
$modules_list = SugarSynchro::getModuleListToSynchro();
$cli->gnotice("Mémoire utilisée avant boucle sur les modules : " . memory_get_usage_hr());

foreach($modules_list as $sugarmodule)
{
    $cli->gnotice("Mémoire utilisée debut synchro module : " . memory_get_usage_hr());
    exec("php runcronjobs.php cleanupmodule $sugarmodule");
    gc_collect_cycles();
    $cli->gnotice("Mémoire utilisée fin synchro module : " . memory_get_usage_hr());
}

// clear cache
eZCache::clearAll();

// fin du script
$cli->endout("cleanup_dev.php");

?>