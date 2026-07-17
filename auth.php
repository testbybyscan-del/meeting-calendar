<?php
/**
 * Модуль аутентификации и логирования
 * Содержит функции для работы с пользователями и записью действий
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

/**
 * Логирует действие в таблицу actions_log
 * @param string $action      Код действия (ADD_MEETING, EDIT_MEETING, ...)
 * @param array  $details     Дополнительные данные в виде ассоциативного массива
 */
function logAction($action, $details = []) {
    global $pdo;
    $username = $_SESSION['user']['username'] ?? 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $details_json = json_encode($details, JSON_UNESCAPED_UNICODE);
    
    $stmt = $pdo->prepare("
        INSERT INTO actions_log (action_time, ip, username, action, details)
        VALUES (NOW(), :ip, :username, :action, :details)
    ");
    $stmt->execute([
        'ip'       => $ip,
        'username' => $username,
        'action'   => $action,
        'details'  => $details_json
    ]);
}