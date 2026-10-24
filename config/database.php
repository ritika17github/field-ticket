<?php

$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=field_ticket;charset=utf8mb4';
$username = 'root';
$password = '';

$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
