<?php

class BlockchainPlatform {
    private $usersFile = 'users.json';
    private $users;

    public function __construct() {
        $this->loadUsers();
    }

    public function registerUser($username) {
        // Генерируем случайный закрытый ключ (16 символов)
        $privateKey = $this->generatePrivateKey();

        // Генерируем уникальный открытый ключ
        $publicKey = $this->generatePublicKey($privateKey);

        // Начисляем 100 токенов при регистрации
        $tokens = 100;

        // Регистрируем пользователя
        $this->users[$username] = [
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
            'tokens' => $tokens,
        ];

        // Сохраняем зарегистрированного пользователя в файл
        $this->saveUsersToFile();

        return [
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
            'tokens' => $tokens,
        ];
    }

    public function transferTokens($senderUsername, $recipientUsername, $signature, $amount) {
        // Проверяем, что отправитель существует и у него достаточно токенов

        if (!isset($this->users[$senderUsername]) || $this->users[$senderUsername]['tokens'] < $amount) {
            return false; // Ошибка: отправитель не существует или у него недостаточно токенов
        }

        // Проверяем подпись отправителя (пароль)
        $senderPublicKey = $this->users[$senderUsername]['publicKey'];

        if ($signature != $senderPublicKey) {
            return false; // Ошибка: неверная подпись
        }

        // Передаем токены
        $this->users[$senderUsername]['tokens'] -= $amount;
        $this->users[$recipientUsername]['tokens'] += $amount;

        // Сохраняем обновленную информацию в файл
        $this->saveUsersToFile();

        return true; // Успех: токены успешно переданы
    }


    private function generatePrivateKey() {
        // Генерация случайного закрытого ключа (16 символов)
        return bin2hex(random_bytes(8)); // 8 байт = 16 символов в шестнадцатеричной системе
    }

    private function generatePublicKey($privateKey) {
        // Пример генерации уникального открытого ключа на основе закрытого ключа
        return hash('sha256', $privateKey);
    }

    private function loadUsers() {
        if (file_exists($this->usersFile)) {
            $this->users = json_decode(file_get_contents($this->usersFile), true);
        } else {
            $this->users = [];
        }
    }

    private function saveUsersToFile() {
        $jsonContent = json_encode($this->users, JSON_PRETTY_PRINT);
        file_put_contents($this->usersFile, $jsonContent);
    }

  ////////// Реализуем дочерний смарт-контракт
  public function createContract($users, $contractName, $targetAmount) {
      // Проверяем, что массив пользователей содержит как минимум двух пользователей
      if (count($users) < 2) {
          return false; // Ошибка: недостаточно пользователей
      }

      // Проверяем, что массив пользователей не пустой и содержит хотя бы одного пользователя
      if (empty($users) || !is_array($users)) {
          return false; // Ошибка: некорректный массив пользователей
      }

      // Проверяем, что каждый пользователь существует
      foreach ($users as $user) {
          if (!isset($this->users[$user])) {
              return false; // Ошибка: один из пользователей не существует
          }
      }

      // Проверяем, что сумма корректна
      if ($targetAmount <= 0) {
          return false; // Ошибка: некорректная сумма
      }

      // Создаем дочерний смарт-контракт
      $childContract = [
          'parentPublicKeys' => array_map(function($user) {
              return $this->users[$user]['publicKey'];
          }, $users),
          'collectedAmount' => $targetAmount,
          'distribution' => [],
      ];

      // Рассчитываем равномерное распределение средств между пользователями
      $totalUsers = count($users);
      $amountPerUser = $targetAmount / $totalUsers;

      // Распределяем средства между пользователями
      foreach ($users as $user) {
          // Проверяем, что у пользователя достаточно средств для списания
          if ($this->users[$user]['tokens'] < $amountPerUser) {
              return false; // Ошибка: недостаточно средств у пользователя
          }

          // Уменьшаем баланс пользователя
          $this->users[$user]['tokens'] -= $amountPerUser;

          // Записываем в дочерний контракт информацию о распределении средств
          $childContract['distribution'][$user] = $amountPerUser;
      }

      // Сохраняем информацию о дочернем контракте в файл
      $this->saveContractToFile($contractName, $childContract);

      // Сохраняем обновленную информацию о пользователях в файл
      $this->saveUsersToFile();

      return true; // Успех: дочерний контракт успешно создан и средства списаны
  }




  private function saveContractToFile($contractName, $contractData) {
    $contractsFile = 'contracts.json';

    if (file_exists($contractsFile)) {
        $contracts = json_decode(file_get_contents($contractsFile), true);
    } else {
        $contracts = [];
    }

    $contracts[$contractName] = $contractData;

    $jsonContent = json_encode($contracts, JSON_PRETTY_PRINT);
    file_put_contents($contractsFile, $jsonContent);
  }
  ////////////// смотрим средства на контракте
  public function getContractCollectedAmount($contractName) {
      $contractsFile = 'contracts.json';

      // Проверяем, существует ли файл с контрактами
      if (file_exists($contractsFile)) {
          $contracts = json_decode(file_get_contents($contractsFile), true);

          // Проверяем, существует ли контракт с указанным именем
          if (isset($contracts[$contractName])) {
              return $contracts[$contractName]['collectedAmount'];
          }
      }

      return 0; // Возвращаем 0, если контракт не найден или произошла ошибка
  }

  //////// Добавим функцию просмотра контрактов
  
  // Функция возвращает контракты по публичному ключу
  public function getContractsByPublicKey($publicKey) {
      $contractsJsonPath = $_SERVER['DOCUMENT_ROOT'] . '/contracts.json';

      if (!file_exists($contractsJsonPath)) {
          return null; // Файл не существует
      }

      $contractsJson = file_get_contents($contractsJsonPath);

      if ($contractsJson === false) {
          return null; // Не удалось прочитать файл
      }

      $contractsData = json_decode($contractsJson, true);

      $matchingContracts = [];

      foreach ($contractsData as $contractName => $contractInfo) {
          if (isset($contractInfo['parentPublicKey']) && $contractInfo['parentPublicKey'] == $publicKey) {
              $matchingContracts[$contractName] = $contractInfo;
          } elseif (isset($contractInfo['parentPublicKeys']) && in_array($publicKey, $contractInfo['parentPublicKeys'])) {
              $matchingContracts[$contractName] = $contractInfo;
          }
      }

      return $matchingContracts;
  }



  /////////////// Завершим функцию просмотра контрактов

}

?>