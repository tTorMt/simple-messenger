<?php

declare(strict_types=1);

namespace tTorMt\SChat;

require_once __DIR__ . "/../vendor/autoload.php";

use tTorMt\SChat\Storage\MySqlHandlerGenerator;
use tTorMt\SChat\WebSocket\Server;

$ws = new Server(new MySqlHandlerGenerator());
$ws->start();
