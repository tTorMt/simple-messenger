<?php

declare(strict_types=1);

namespace tTorMt\SChat\Messenger;

use Swoole\WebSocket\Server;
use Swoole\Timer;
use tTorMt\SChat\Storage\DBHandler;

/**
 * Messenger class. Updates and receives messages
 */
class ChatUser
{
    /**
     * Session ID of the user
     * @var string
     */
    private string $sessionId;
    /**
     * User FD from the WebSocket server
     * @var int
     */
    private int $userFd;
    /**
     * Last updated message ID used for retrieving new messages from the database
     * @var int
     */
    private int $lastMID;
    /**
     * Swoole WebSocket server handle
     * @var Server
     */
    private Server $server;
    /**
     * Database handler
     * @var DBHandler
     */
    private DBHandler $storage;
    /**
     * Timer ID used for stopping the timer
     * @var int
     */
    private int $timerId;
    /**
     * Message update period in milliseconds
     */
    private const int UPDATE_PERIOD = 1000;

    /**
     * Initialize ChatUser
     * @param int $userFd
     * @param string $sessionId
     * @param int $lastMID
     * @param Server $server
     * @param DBHandler $storage
     */
    public function __construct(int $userFd, string $sessionId, int $lastMID, Server $server, DBHandler $storage)
    {
        $this->sessionId = $sessionId;
        $this->userFd = $userFd;
        $this->lastMID = $lastMID;
        $this->server = $server;
        $this->storage = $storage;
    }

    /**
     * Starts update cycles and records the timer ID
     * @return void
     * @throws UpdateStartException
     */
    public function startUpdates(): void
    {
        $timerId = Timer::tick(self::UPDATE_PERIOD, [$this, 'update']);
        if ($timerId === false) {
            throw new UpdateStartException('Failed to start updates on user FD '.$this->userFd);
        }
        $this->timerId = $timerId;
    }

    /**
     * Checks for new messages and sends them to the user.
     * If lastMID = -1, send []
     * @return void
     */
    private function update(): void
    {
        if ($this->lastMID === -1) {
            $this->server->push($this->userFd, json_encode([]));
            return;
        }
        $messages = $this->storage->getLastMessages($this->sessionId, $this->lastMID);
        $messages = json_encode($messages);
        $this->server->push($this->userFd, $messages);
    }

    /**
     * Sets the last message ID shown to the user
     * @param int $lastMID
     * @return void
     */
    public function setLastMID(int $lastMID): void
    {
        $this->lastMID = $lastMID;
    }

    /**
     *  Closes the user connection and stops the timer update cycle
     * @return void
     */
    public function close(): void
    {
        if (isset($this->timerId)) {
            Timer::clear($this->timerId);
            unset($this->timerId);
        }
    }

    /**
     * Processes commands from the user, including sending messages, changing the active group ID, or closing the connection
     * @param array $message [command, data] from ['message', message text], ['setGID', [GID, MID]], ['setMID', MID], ['close']
     * @return void
     * @throws UpdateStartException
     * @throws MessageStoreException
     * @throws IncorrectCommandException
     * @throws SessionDataException
     */
    public function process(array $message): void
    {
        switch ($message[0]) {
            case 'message': {
                try {
                    $isStored = $this->storage->storeMessage($this->sessionId, $message[1], false);
                    if (!$isStored) {
                        throw new MessageStoreException('Failed to send message to user FD '.$this->userFd);
                    }
                } catch (\Exception $exception) {
                    throw new MessageStoreException('Failed to send message to user FD '.$this->userFd, 0, $exception);
                }
                break;
            }
            case 'setGID': {
                if (isset($this->timerId)) {
                    Timer::clear($this->timerId);
                    unset($this->timerId);
                }

                if (!$this->storage->setActiveChat($this->sessionId, (int)$message[1][0])) {
                    throw new SessionDataException();
                };
                $this->setLastMID((int)$message[1][1]);
                $this->startUpdates();
                break;
            }
            case 'setMID': {
                $this->setLastMID((int)$message[1]);
                break;
            }
            case 'close': {
                $this->close();
                break;
            }
            default: throw new IncorrectCommandException();
        }
    }
}
