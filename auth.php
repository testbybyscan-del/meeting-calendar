<?php
/**
 * Модуль аутентификации
 * Содержит функции для работы с пользователями
 */

require_once 'config.php';

/**
 * Возвращает данные пользователя по логину
 * @param string $username
 * @return array|null
 */
function getUserByUsername($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Проверяет, авторизован ли пользователь
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['user']);
}