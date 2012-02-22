<?php

function vd($var)
{
	return var_dump($var);
}

function evd($var)
{
	exit(vd($var));
}

?>