<?php

// fonction pour afficher les variable en console
function show($var)
{
	$show = print_r($var,true);
	return $show; 
}

/*
 * fonctions pour des exit et des var_dump
 */
function vd($var)
{
	return var_dump($var);
}

function evd($var)
{
	exit(vd($var));
}

function mvd($array)
{
	if(!is_array($array))
		return vd($array);
		
	foreach($array as $var)
	{
		vd($var);
	}
}

function emvd($array)
{
	if(!is_array($array))
		exit(vd($array));
		
	exit(mvd($array));
}

function ivd($name, $var)
{
	echo($name . " : \n");
	vd($var);
}

function eivd($name, $var)
{
	echo($name . " : \n");
	exit(vd($var));
}

function bp()
{
	exit("break-point ");
}

?>