<?php

function generatePrivateKey() {
    // Генерация случайного закрытого ключа (16 символов)
    return bin2hex(random_bytes(8)); // 8 байт = 16 символов в шестнадцатеричной системе
}

function generatePublicKey($privateKey) {
    // Пример генерации уникального открытого ключа на основе закрытого ключа
    return hash('sha256', $privateKey);
}


?>