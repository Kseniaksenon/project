<?php
include('db_connect.php'); // Подключаем базу данных

// Проверяем, передан ли ID сотрудника
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Ошибка: ID сотрудника не указан.");
}

$id = $_GET['id'];

// Получаем данные сотрудника
$sql = "SELECT * FROM Employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Сотрудник не найден.");
}

$employee = $result->fetch_assoc();

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $staff_number = $_POST['staff_number'];
    $birth_date = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $subdivision = $_POST['subdivision'];
    $position_id = $_POST['position_id'];
    $hire_date = $_POST['hire_date'];

    // Обновляем данные сотрудника в базе данных
    $update_query = "
        UPDATE Employees 
        SET full_name = ?, staff_number = ?, birth_date = ?, gender = ?, subdivision = ?, position_id = ?, hire_date = ? 
        WHERE id = ?
    ";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('sssssssi', $full_name, $staff_number, $birth_date, $gender, $subdivision, $position_id, $hire_date, $id);
    $stmt->execute();

    // Перенаправляем на страницу со списком сотрудников после обновления
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование сотрудника</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="card shadow p-4">
        <h1 class="text-center text-primary">Редактирование сотрудника</h1>

        <!-- Форма редактирования сотрудника -->
        <form method="POST">
            <div class="mb-3">
                <label for="full_name" class="form-label">ФИО</label>
                <input type="text" name="full_name" id="full_name" class="form-control" value="<?= htmlspecialchars($employee['full_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="staff_number" class="form-label">Табельный номер</label>
                <input type="text" name="staff_number" id="staff_number" class="form-control" value="<?= htmlspecialchars($employee['staff_number']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="birth_date" class="form-label">Дата рождения</label>
                <input type="date" name="birth_date" id="birth_date" class="form-control" value="<?= $employee['birth_date'] ?>" required>
            </div>

            <div class="mb-3">
                <label for="gender" class="form-label">Пол</label>
                <select name="gender" id="gender" class="form-select" required>
                    <option value="M" <?= $employee['gender'] == 'M' ? 'selected' : '' ?>>Мужчина</option>
                    <option value="F" <?= $employee['gender'] == 'F' ? 'selected' : '' ?>>Женщина</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="subdivision" class="form-label">Подразделение</label>
                <input type="text" name="subdivision" id="subdivision" class="form-control" value="<?= htmlspecialchars($employee['subdivision']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="position_id" class="form-label">Должность</label>
                <select name="position_id" id="position_id" class="form-select" required>
                    <?php
                    // Получаем список должностей
                    $positions = $conn->query("SELECT * FROM Positions");
                    while ($pos = $positions->fetch_assoc()): ?>
                        <option value="<?= $pos['id'] ?>" <?= $employee['position_id'] == $pos['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pos['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="hire_date" class="form-label">Дата найма</label>
                <input type="date" name="hire_date" id="hire_date" class="form-control" value="<?= $employee['hire_date'] ?>" required>
            </div>

            <div class="text-center">
                <button class="btn btn-success" type="submit">Сохранить изменения</button>
                <a href="index.php" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
