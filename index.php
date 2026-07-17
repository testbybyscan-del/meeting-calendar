<?php
session_start();
require_once 'auth.php';

if (!isAuthenticated()) {
    header('Location: login.php');
    exit();
}

function dateToDb($dateStr) {
    $dt = DateTime::createFromFormat('d.m.Y', $dateStr);
    return $dt ? $dt->format('Y-m-d') : null;
}

function dateToDisplay($dateStr) {
    return date('d.m.Y', strtotime($dateStr));
}

function generateCalendar($selectedDate) {
    $month = date('n', strtotime(str_replace('.', '-', $selectedDate)));
    $year  = date('Y', strtotime(str_replace('.', '-', $selectedDate)));
    $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));
    $calendar = [];
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $calendar[] = sprintf('%02d.%02d.%04d', $day, $month, $year);
    }
    return $calendar;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    global $pdo;
    try {
        switch ($action) {
            case 'add':
                $date     = dateToDb($_POST['date']);
                $time     = $_POST['time'];
                $address  = trim($_POST['address']);
                $desc     = trim($_POST['description'] ?? '');
                $created_by = $_SESSION['user']['username'] ?? 'anonymous';
                $stmt = $pdo->prepare("
                    INSERT INTO meetings (date, time, address, description, created_at, created_by)
                    VALUES (:date, :time, :address, :description, NOW(), :created_by)
                ");
                $stmt->execute([
                    'date'        => $date,
                    'time'        => $time,
                    'address'     => $address,
                    'description' => $desc,
                    'created_by'  => $created_by
                ]);
                logAction('ADD_MEETING', ['id' => $pdo->lastInsertId(), 'date' => $date, 'time' => $time]);
                break;
            case 'edit':
                $id       = (int)$_POST['id'];
                $date     = dateToDb($_POST['date']);
                $time     = $_POST['time'];
                $address  = trim($_POST['address']);
                $desc     = trim($_POST['description'] ?? '');
                $updated_by = $_SESSION['user']['username'] ?? 'anonymous';
                $stmt = $pdo->prepare("
                    UPDATE meetings
                    SET date = :date, time = :time, address = :address, description = :description,
                        updated_at = NOW(), updated_by = :updated_by
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id'          => $id,
                    'date'        => $date,
                    'time'        => $time,
                    'address'     => $address,
                    'description' => $desc,
                    'updated_by'  => $updated_by
                ]);
                logAction('EDIT_MEETING', ['id' => $id]);
                break;
            case 'delete':
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("SELECT * FROM meetings WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $meeting = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($meeting) {
                    logAction('DELETE_MEETING', $meeting);
                }
                $stmt = $pdo->prepare("DELETE FROM meetings WHERE id = :id");
                $stmt->execute(['id' => $id]);
                break;
        }
        $redirectDate = $_POST['date'] ?? date('d.m.Y');
        header("Location: " . $_SERVER['PHP_SELF'] . "?date=" . urlencode($redirectDate));
        exit();
    } catch (Exception $e) {
        logAction('ERROR', ['message' => $e->getMessage()]);
        die("Ошибка: " . $e->getMessage());
    }
}

$selectedDate = $_GET['date'] ?? date('d.m.Y');
$selectedDateDb = dateToDb($selectedDate);
$stmt = $pdo->prepare("SELECT * FROM meetings WHERE date = :date ORDER BY time");
$stmt->execute(['date' => $selectedDateDb]);
$filteredMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
$calendar = generateCalendar($selectedDate);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Календарь встреч</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Календарь встреч</a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3"><?= htmlspecialchars($_SESSION['user']['username'] ?? 'Гость') ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Выйти</a>
            </div>
        </div>
    </nav>
    <div class="container py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Календарь</h5></div>
                    <div class="card-body">
                        <input type="text" id="datePicker" class="form-control mb-3" placeholder="Выберите дату">
                        <div class="calendar-grid">
                            <?php foreach ($calendar as $day): ?>
                                <div class="calendar-day p-2 mb-1 rounded <?= $day === $selectedDate ? 'active' : '' ?>"
                                     onclick="window.location.href='?date=<?= $day ?>'">
                                    <?= date('j', strtotime(str_replace('.', '-', $day))) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Встречи на <?= htmlspecialchars($selectedDate) ?></h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#meetingModal">
                            <i class="fas fa-plus"></i> Новая встреча
                        </button>
                    </div>
                    <div class="card-body" id="meetingsContainer">
                        <?php if (empty($filteredMeetings)): ?>
                            <div class="text-center py-4 text-muted">Нет встреч на выбранную дату</div>
                        <?php else: ?>
                            <?php foreach ($filteredMeetings as $meeting): ?>
                                <div class="card mb-3 meeting-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h5 class="card-title"><?= htmlspecialchars($meeting['time']) ?></h5>
                                            <div class="text-muted"><?= htmlspecialchars($meeting['address']) ?></div>
                                        </div>
                                        <?php if (!empty($meeting['description'])): ?>
                                            <p class="card-text"><?= htmlspecialchars($meeting['description']) ?></p>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-end mt-2">
                                            <button class="btn btn-sm btn-outline-primary me-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#meetingModal"
                                                    data-id="<?= $meeting['id'] ?>"
                                                    data-date="<?= dateToDisplay($meeting['date']) ?>"
                                                    data-time="<?= htmlspecialchars($meeting['time']) ?>"
                                                    data-address="<?= htmlspecialchars($meeting['address']) ?>"
                                                    data-description="<?= htmlspecialchars($meeting['description'] ?? '') ?>"
                                                    onclick="editMeeting(this)">
                                                <i class="fas fa-edit"></i> Редактировать
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteMeeting(<?= $meeting['id'] ?>)">
                                                <i class="fas fa-trash"></i> Удалить
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="meetingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Новая встреча</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="meetingForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="meetingId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="meetingDate" class="form-label">Дата</label>
                            <input type="text" class="form-control" id="meetingDate" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label for="meetingTime" class="form-label">Время</label>
                            <input type="time" class="form-control" id="meetingTime" name="time" required>
                        </div>
                        <div class="mb-3">
                            <label for="meetingAddress" class="form-label">Адрес</label>
                            <input type="text" class="form-control" id="meetingAddress" name="address" required>
                        </div>
                        <div class="mb-3">
                            <label for="meetingDescription" class="form-label">Описание</label>
                            <textarea class="form-control" id="meetingDescription" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>