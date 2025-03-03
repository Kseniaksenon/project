<?php
include('db_connect.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Ошибка: ID записи не указан.");
}

$id = $_GET['id'];

// Получение данных записи
$query = "SELECT * FROM CurrentEmployees WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) {
    die("Ошибка: Запись не найдена.");
}

// Обновление записи
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];

    // Пересчет часов
    $update_query = "UPDATE CurrentEmployees 
    SET time_in = ?, 
        time_out = ?, 
        total_hours = ROUND(TIME_TO_SEC(TIMEDIFF(?, ?)) / 3600, 2) 
    WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ssssi", $time_in, $time_out, $time_out, $time_in, $id);




    if ($stmt->execute()) {
        echo "<script>alert('Запись обновлена!'); window.location.href='attendance.php';</script>";
    } else {
        echo "<script>alert('Ошибка: " . $stmt->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование записи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow-lg p-4">
            <h2 class="text-center">Редактирование записи</h2>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Дата</label>
                    <input type="date" class="form-control" value="<?= $record['date'] ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Время прихода</label>
                    <input type="time" name="time_in" class="form-control" value="<?= $record['time_in'] ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Время ухода</label>
                    <input type="time" name="time_out" class="form-control" value="<?= $record['time_out'] ?>" required>
                </div>
                <button type="submit" class="btn btn-success">Сохранить</button>
                <a href="attendance.php" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
</body>
</html>
