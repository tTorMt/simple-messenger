<?php

declare(strict_types=1);

namespace tTorMt\SChat\WebSocket;

use tTorMt\SChat\Messenger\ChatUser;
use tTorMt\SChat\Messenger\IncorrectCommandException;
use tTorMt\SChat\Messenger\MessageStoreException;
use tTorMt\SChat\Messenger\UpdateStartException;
use Swoole\{WebSocket\Server as WsServer, Http\Request, WebSocket\Frame};
use tTorMt\SChat\Storage\DBHandler;

/**
 * Websocket server used for messaging
 */
class Server
{
    /**
     * Swoole\WebSocket\Server handle
     * @var WsServer
     */
    private WsServer $ws;
    /**
     * Database handler
     * @var DBHandler
     */
    private DBHandler $dbHandler;
    /**
     * Array of connections and ChatUser objects
     * @var array [ 'userFd' => ChatUser ]
     */
    private array $connections = [];

    /**
     * Initializes a WebSocket server. Reads the config file.
     */
    public function __construct()
    {
        $config = parse_ini_file(__DIR__ . '/../../config/config.ini');
        $ws = new WsServer($config['hosts_listen'], (int)$config['port_listen']);

        $ws->set([
            'worker_num' => $config['worker_num'] ?? swoole_cpu_num() * 2,
        ]);

        $ws->on('WorkerStart', [$this, 'onWorkerStart']);
        $ws->on('Open', [$this, 'onUserConnection']);
        $ws->on('Close', [$this, 'onUserDisconnect']);
        $ws->on('Message', [$this, 'onMessage']);

        $this->ws = $ws;
    }

    /**
     * Creates a database connection when a worker starts
     * @param WsServer $ws
     * @return void
     */
    public function onWorkerStart(WsServer $ws): void
    {
        $this->dbHandler = new DBHandler();
    }

    /**
     * On user connection reads the session and starts message updating.
     * To start receiving messages the activeGID and the lastMID needed to be set.
     * @param WsServer $ws
     * @param Request $request
     * @return void
     * @throws UpdateStartException
     */
    public function onUserConnection(WsServer $ws, Request $request): void
    {
        // TODO implement connection and error logging
        echo 'New connection FD: '.$request->fd.PHP_EOL;
        $userFd = $request->fd;
        $cookie = $request->cookie;
        $userId = $this->dbHandler->getSessionData($cookie);
        if ($userId === false) {
            $ws->push($userFd, 'User session not found');
            $ws->close($userFd);
            return;
        }
        $userId = $userId['user_id'];
        $chatUser = new ChatUser($userFd, $userId, -1, -1, $this->ws, $this->dbHandler);
        $chatUser->startUpdates();
        $this->connections[$userFd] = $chatUser;
    }

    /**
     * When user disconnects closes the ChatUser, deletes the session and removes the ChatUser from the connection array
     * @param WsServer $ws
     * @param int $fd
     * @return void
     */
    public function onUserDisconnect(WsServer $ws, int $fd): void
    {
        // TODO implement closing procedure
        echo "client-$fd is closed\n";
        $chatUser = $this->connections[$fd];
        $userId = $chatUser->getUserId();
        $this->dbHandler->deleteSession($userId);
        $chatUser->close();
        unset($this->connections[$fd]);
    }

    /**
     * Receives and processes a message.
     * @param WsServer $ws
     * @param Frame $frame
     * @return void
     */
    public function onMessage(WsServer $ws, Frame $frame): void
    {
        try {
            $userFd = $frame->fd;
            $chatUser = $this->connections[$userFd];
            $message = json_decode($frame->data);
            $chatUser->process($message);
        } catch (IncorrectCommandException $exception) {
            $ws->push($userFd, 'Incorrect command');
            // TODO implement error logging
            var_dump($exception);
        } catch (MessageStoreException $exception) {
            $ws->push($userFd, 'Message store error');
            // TODO implement error logging
            var_dump($exception);
        } catch (UpdateStartException $exception) {
            $ws->push($userFd, 'Update message start error');
            // TODO implement error logging
            var_dump($exception);
        }
    }

    /**
     * Starts the server
     * @return void
     */
    public function start(): void
    {
        $this->ws->start();
    }
}
