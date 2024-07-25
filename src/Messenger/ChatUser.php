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
     * User ID from the database
     * @var int
     */
    private int $userId;
    /**
     * User FD from the WebSocket server
     * @var int
     */
    private int $userFd;
    /**
     * Active group ID used for message sending and updating
     * @var int
     */
    private int $activeGID;
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
     * @param $userId
     * @param int $activeGID
     * @param int $lastMID
     * @param Server $server
     * @param DBHandler $storage
     */
    public function __construct(int $userFd, $userId, int $activeGID, int $lastMID, Server $server, DBHandler $storage)
    {
        $this->userId = $userId;
        $this->userFd = $userFd;
        $this->activeGID = $activeGID;
        $this->lastMID = $lastMID;
        $this->server = $server;
        $this->storage = $storage;
    }

    /**
     * Gets user ID from the database
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
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
     * If activeGID = -1 or lastMID = -1, send []
     * @return void
     */
    private function update(): void
    {
        if ($this->activeGID === -1 || $this->lastMID === -1) {
            $this->server->push($this->userFd, json_encode([]));
        }
        $messages = $this->storage->getLastMessages($this->activeGID, $this->lastMID);
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
     * Sets the active group ID for message handling
     * @param int $activeGID
     * @return void
     */
    public function setActiveGID(int $activeGID): void
    {
        $this->activeGID = $activeGID;
    }

    /**
     *  Closes the user connection and stops the timer update cycle
     * @return void
     */
    public function close(): void
    {
        $this->storage->deleteSession($this->userId);
        Timer::clear($this->timerId);
    }

    /**
     * Processes commands from the user, including sending messages, changing the active group ID, or closing the connection
     * @param array $message [command, data] from ['message', message text], ['setGID', [GID, MID]], ['setMID', MID], ['close']
     * @return void
     * @throws UpdateStartException
     * @throws MessageStoreException
     * @throws IncorrectCommandException
     */
    public function process(array $message): void
    {
        switch ($message[0]) {
            case 'message': {
                $result = $this->storage->storeMessage($this->userId, $this->activeGID, $message[1]);
                if (!$result) {
                    throw new MessageStoreException('Failed to send message to user FD '.$this->userFd);
                }
                break;
            }
            case 'setGID': {
                Timer::clear($this->timerId);
                $this->setActiveGID((int)$message[1]['GID']);
                $this->setLastMID((int)$message[1]['MID']);
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
