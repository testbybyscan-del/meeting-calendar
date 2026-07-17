<?php
session_start();
require_once 'auth.php';

// Если уже авторизован – перенаправляем на главную
if (isAuthenticated()) {
    header('Location: index.php');
    exit();
}

$error = null;

// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $user = getUserByUsername($username);
        if ($user) {
            // Проверка пароля: поддерживаем оба метода
            $valid = false;
            // 1) Если пароль хеширован через password_hash
            if (password_verify($password, $user['password'])) {
                $valid = true;
            }
            // 2) Если пароль хеширован через MD5 (для совместимости со старыми данными)
            if (!$valid && md5($password) === $user['password']) {
                $valid = true;
                // Опционально: обновить хеш на password_hash
                // $stmt = $pdo->prepare("UPDATE users SET password = :hash WHERE id = :id");
                // $stmt->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id']]);
            }

            if ($valid) {
                $_SESSION['user'] = $user;
                // Логируем успешный вход (если таблица существует)
                try {
                    logAction('LOGIN_SUCCESS');
                } catch (Exception $e) {
                    // Игнорируем ошибку логирования
                }
                header('Location: index.php');
                exit();
            }
        }
    }
    $error = 'Неверные учётные данные';
    // Логируем неудачную попытку (если таблица существует)
    try {
        logAction('LOGIN_FAILED', ['username' => $username]);
    } catch (Exception $e) {
        // Игнорируем ошибку логирования
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Вход в систему</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Логин</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Войти</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>