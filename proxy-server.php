<?php

/**
 * Code to set up a simple HTTP proxy server
 */

require_once 'src/Proximate/Proxier.php';

$proxier = new Proximate\Proxier();
$proxier->
    initialiseServerSocket('localhost', 9001)->
    listenLoop();
