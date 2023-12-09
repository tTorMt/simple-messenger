<?php
declare (strict_types=1);

use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;

$server = new Server('0.0.0.0', 2346, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);

$server->set(
    [
        'ssl_cert_file' => '/etc/letsencrypt/live/ttormt.fvds.ru/fullchain.pem',
        'ssl_key_file' => '/etc/letsencrypt/live/ttormt.fvds.ru/privkey.pem'
    ]
);

$server->on('Start', function () {
    echo 'Server started' . PHP_EOL;
});

$server->on('Open', function (Server $serv, Request $request) {
    echo 'New connection: ' . $request->fd . PHP_EOL;
    $sessionId = $request->cookie['PHPSESSID'];
    require_once('../includes/dbstorage.php');
    $storage = new DBStorage();
    $userId = $storage->getUserId($sessionId);
    if ($userId === false) {
        $serv->send($request->fd, 'No session opened. Closing connection');
        $serv->close($request->fd);
    }
    echo $userId;
});

$server->on('message', function (Server $serv, Frame $frame) {
    echo 'Received: '.$frame->data.PHP_EOL;
});

$server->start();