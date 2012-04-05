<?php

// fonction pour afficher les variable en console
function show($var)
{
	$show = print_r($var,true);
	return $show; 
}

/*
 * fonction relative à l'utilisation de la memoire
 */

function convert($size)
{
	$unit=array('b','kb','mb','gb','tb','pb');
	return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

function memory_get_usage_hr()
{
	return convert(memory_get_usage(true));
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