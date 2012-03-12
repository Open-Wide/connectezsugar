<?php
//
// Created on: 
//
// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 3.9.x
// COPYRIGHT NOTICE: Openwide 
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
//

$Module = array( 'name' => 'sugarCrm',
                 'variable_params' => true,
                 );

$ViewList = array();
$ViewList['synchro'] = array(
    'script' => 'synchro.php',
    'params' => array('sugarid'));

$ViewList['getinfo'] = array(
    'script' => 'getinfo.php',
    'params' => array('sugarid'));

$ViewList['import'] = array(
    'script' => 'import.php',
    'params' => array('sugarmodule','sugarid'));

$ViewList['cleanup'] = array(
    'script' => 'cleanup.php',
    'params' => array('type','identifier'));

$ViewList['querysugar'] = array(
    'script' => 'querysugar.php',
    'params' => array('query','sugarmodule','sugarid','related_module'));

$ViewList['updateclass'] = array(
    'script' => 'updateclass.php',
    'params' => array('sugarmodule'));

$ViewList['import_relations'] = array(
    'script' => 'import_relations.php',
    'params' => array('sugarmodule','sugarid'));

?>
