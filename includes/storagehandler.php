<?php

declare(strict_types=1);

interface StorageHandler {
	public function isNameVacant(string $name): bool;
	public function storeUser(string $name, string $pass);
	public function checkCredentials(string $name, string $pass): bool;
	public function closeStorage();
	public function searchUserNames(string $namePart):array;
}
