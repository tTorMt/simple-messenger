<?php

namespace tTorMt\SChat;

require __DIR__ . "/../vendor/autoload.php";

use tTorMt\SChat\WebSocket\Server;

(new Server())->start();