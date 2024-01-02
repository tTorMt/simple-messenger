<?php

declare(strict_types=1);

interface StorageHandler {
	public function isNameVacant(string $name): bool;
	public function storeUser(string $name, string $pass);
	//Return user_id if authorized or false if not
	public function checkCredentials(string $name, string $pass): bool | int;
	public function closeStorage();
	public function searchUserNames(string $namePart):array;
	//Save session in database
	public function storeSession(string $sessionId, int $userId);
	public function storeConversationId(string $sessionId, int $convId);
	public function getUserId(string $sessionId): int | bool;
	public function getMessages(int $convId): array;
	public function getMessagesFromDate(int $convId, DateTime $lastRefreshTime): array;
	public function storeMessage(string $message, int $userId, int $convId);
	//Get or create new conversation. Return conversation id
	public function openConversation(int $firstUserId, int $secondUserId):int;
}
