<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use tTorMt\SChat\App;

session_start();

$app = new App(new \tTorMt\SChat\Storage\MySqlHandler());

$app->run();


