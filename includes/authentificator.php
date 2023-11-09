<?php

declare(strict_types=1);
require_once('utils.php');
require_once('storagehandler.php');

class Authenticator {
	private string $name;
	private string $pass;
	private string $errMsg;
	private StorageHandler $storage;

	public function __construct(string $name, string $pass, StorageHandler $storage) {
		$this->name = inputUtils::nameTrim($name);
		$this->pass = $pass;
		$this->storage = $storage;
	}

	public function regUser(): bool {
		if ($this->storage->isNameVacant($this->name)) {
			if (inputUtils::nameCheck($this->name) && inputUtils::passCheck($this->pass)) {
				$this->storage->storeUser($this->name, $this->pass);
				$this->storage->closeStorage();
				return true;
			} else {
				$this->errMsg = "<p class=\"input-error\" style=\"display: block;\">Логин и/или пароль не соответствуют требованиям. Повторите ввод.</p>";
				$this->storage->closeStorage();
				return false;
			}
		} else {
			$this->errMsg = "<p class=\"input-error\" style=\"display: block;\">Такой логин занят. Повторите ввод.</p>";
			$this->storage->closeStorage();
			return false;
		}
	}

	public function authUser(): bool {
		if ($this->storage->checkCredentials($this->name, $this->pass)) {
			$_SESSION['user'] = $this->name;
			$this->storage->closeStorage();
			return true;
		} else {
			$this->errMsg = "<p class=\"input-error\" style=\"display: block;\">Связка логин-пароль не верная. Повторите ввод.</p>";
			$this->storage->closeStorage();
			return false;
		}
	}

	public function showError() {
		if ($this->errMsg)
			echo $this->errMsg;
	}

	public function nameVacantJSON(): string {
		return $this->storage->isNameVacant($this->name) ? json_encode(['vacant' => 'true'])
			: json_encode(['vacant' => 'false']);
	}
}
