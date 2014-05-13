<?php
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add( "", __DIR__ );

define("WORKER_DIR", __DIR__ . "/workers");
define("HOST", "127.0.0.1");
define("PORT", 11300);
