<?php

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

include_once( 'extension/connectezsugar/classes/Module.php' );

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);
$cli->beginout('import_module.php');

$arguments = array_slice( $_SERVER['argv'], 1 );
$sugarmodule = $arguments[ 1 ];

$cli->notice('*******************************************');
$cli->notice("Sugar Module : $sugarmodule");
$cli->notice('*******************************************');

$module = new Module( $sugarmodule, $cli );
$module->import_module_objects( );

$cli->endout('import_module.php');
?>