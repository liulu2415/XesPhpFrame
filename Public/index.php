<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require __DIR__ . '/../Common/Autoload.php';
require __DIR__ . '/../Route/Routes.php';

Autoloader::init();

setBasePath(dirname(__DIR__));
$settings = require __DIR__ . '/../Config/Config.php';

$app = new Core;

$app->run();
