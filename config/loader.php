<?php

use Phalcon\Loader;

$loader = new Loader();
$loader->registerDirs(
    $config->get('appdirs')->toArray()
)->register();

