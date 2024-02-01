<?php

function loadUsers() {
    $usersFile = 'users.json';

    if (file_exists($usersFile)) {
        return json_decode(file_get_contents($usersFile), true);
    } else {
        return [];
    }
}

function saveUsersToFile($users) {
    $jsonContent = json_encode($users, JSON_PRETTY_PRINT);
    file_put_contents('users.json', $jsonContent);
}
