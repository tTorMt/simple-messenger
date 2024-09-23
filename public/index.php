<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use tTorMt\SChat\App;
use tTorMt\SChat\Storage\MySqlHandler;

session_start();

$app = new App(new MySqlHandler());

$app->run();


