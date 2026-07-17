<?php
/**
 * Конфигурационный файл
 * - Использует переменные окружения (POSTGRES_* и DB_HOST/DB_PORT)
 * - Создаёт PDO-соединение с PostgreSQL
 */

// Читаем переменные окружения (с значениями по умолчанию)
$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('POSTGRES_DB') ?: 'postgres';
$user = getenv('POSTGRES_USER') ?: 'postgres';
$password = getenv('POSTGRES_PASSWORD') ?: 'postgres';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'UTF8'");
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

/**
 * Функция для логирования действий (если нужна)
 */
function logAction($action, $data = []) {
    global $pdo;
    $username = $_SESSION['user']['username'] ?? 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $details_json = json_encode($data, JSON_UNESCAPED_UNICODE);
    
    try {
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
    } catch (PDOException $e) {
        // Если таблица actions_log не существует – молча игнорируем
    }
}