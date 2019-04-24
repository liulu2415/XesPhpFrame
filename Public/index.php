<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

require __DIR__ . '/../FrameWork/Autoloader.php';

FrameWork\Autoloader::init();

$settings = require __DIR__ . '/../Config/Config.php';

$app = new FrameWork\Core();

$app->run();
