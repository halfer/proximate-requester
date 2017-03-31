<?php

/*
 * Code to set up a simple HTTP proxy server
 */

require_once 'src/Proxier.php';

$proxier = new Proximate\Proxier();
$proxier->
    initialiseServerSocket('localhost', 9001)->
    listenLoop();
