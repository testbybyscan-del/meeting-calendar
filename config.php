<?php
/**
 * Конфигурационный файл
 * - Загружает переменные из .env (если файл существует)
 * - Создаёт PDO-соединение с PostgreSQL
 */

/**
 * Простая функция для загрузки .env файла
 * Помещает переменные в $_ENV и putenv()
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Пропускаем комментарии
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Разделяем по первому знаку '='
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name  = trim($parts[0]);
            $value = trim($parts[1]);
            // Убираем кавычки, если есть
            if (preg_match('/^"(.+)"$/', $value, $matches) || preg_match("/^'(.+)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Загружаем .env из корня проекта
loadEnv(__DIR__ . '/.env');

// Читаем переменные окружения (с значениями по умолчанию)
$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'postgres';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'postgres';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'UTF8'");
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}