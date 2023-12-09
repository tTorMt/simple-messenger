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
	public function getUserId(string $sessionId): int | bool;
}
