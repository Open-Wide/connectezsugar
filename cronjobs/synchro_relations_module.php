<?php

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

include_once( 'extension/connectezsugar/classes/Module.php' );

// init CLI
$cli = SmartCLI::instance();
$cli->setIsQuiet(false);
$cli->beginout("synchronize_relations.php");

$arguments = array_slice( $_SERVER['argv'], 1 );
$sugarmodule = $arguments[ 1 ];

$cli->notice("*******************************************");
$cli->notice("Sugar Module : $sugarmodule");
$cli->notice("*******************************************");

$module = new Module( $sugarmodule, $cli );
$module->import_module_objects( );
$cli->notice( var_dump( array_keys( $GLOBALS ) ) );

$cli->endout("synchronize_relations.php");
?>