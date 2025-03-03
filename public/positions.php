<?php
include('db_connect.php'); // Подключение к БД

// Устанавливаем кодировку соединения
$conn->set_charset("utf8mb4");

// Добавление должности
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
    $name = $_POST['name'];
    $rate = $_POST['rate'];
    $salary = $_POST['salary'];

    $sql = "INSERT INTO Positions (name, rate, salary) VALUES ('$name', '$rate', '$salary')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Должность добавлена.";
    } else {
        echo "Ошибка: " . $conn->error;
    }
}

// Удаление должности
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM Positions WHERE id=$id");
}

// Поиск должностей
$search = "";
$query = "SELECT * FROM Positions";

if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $query .= " WHERE name LIKE '%$search%' OR rate LIKE '%$search%' OR salary LIKE '%$search%'";
}

// Добавление сортировки
$sortBy = "id"; // Сортировка по умолчанию по ID
$sortOrder = "ASC"; // По возрастанию

if (isset($_GET['sort_by'])) {
    $sortBy = $_GET['sort_by'];
}
if (isset($_GET['sort_order'])) {
    $sortOrder = $_GET['sort_order'];
}

$query .= " ORDER BY $sortBy $sortOrder";

$result = $conn->query($query);

// Функция для экспорта в CSV
function exportToCSV($conn, $search = "", $sortBy = "id", $sortOrder = "ASC") {
    // Учитываем поиск или просто выбираем все данные
    if ($search) {
        $query = "SELECT * FROM Positions WHERE name LIKE '%$search%' OR rate LIKE '%$search%' OR salary LIKE '%$search%' ORDER BY $sortBy $sortOrder";
    } else {
        $query = "SELECT * FROM Positions ORDER BY $sortBy $sortOrder";
    }
    
    $result = $conn->query($query);
    $filename = "positions_report_" . date("Y-m-d") . ".csv";
    
    // Устанавливаем заголовки для браузера
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Открытие потока для вывода
    $output = fopen('php://output', 'w');
    
    // Добавляем BOM для UTF-8, чтобы Excel правильно отображал символы
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    // Заголовки для CSV
    fputcsv($output, ['ID', 'Название должности', 'Ставка', 'Оклад']);
    
    // Данные
    while ($row = $result->fetch_assoc()) {
        // Преобразуем строки в кодировку UTF-8 только при необходимости
        $row['name'] = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
        
        // Записываем строку в CSV
        fputcsv($output, [
            $row['id'], 
            $row['name'], 
            $row['rate'], 
            $row['salary']
        ]);
    }
    
    fclose($output);
    exit();
}

// Экспорт по результатам поиска
if (isset($_GET['export_search'])) {
    exportToCSV($conn, $search, $sortBy, $sortOrder);
}

// Экспорт всех данных
if (isset($_GET['export_all'])) {
    exportToCSV($conn, "", $sortBy, $sortOrder);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Должности</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    /* Стили для креативной кнопки "На главную", расположенной сбоку */
    .back-to-home {
        position: fixed;
        top: 50%;
        left: 10px;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        background-color: #28a745;
        color: white;
        padding: 15px 20px;
        border-radius: 30px;
        font-size: 18px;
        font-weight: bold;
        text-decoration: none;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        transition: background-color 0.3s ease, transform 0.3s ease;
        z-index: 1000;
    }
    .back-to-home:hover {
        background-color: #218838;
        transform: translateY(-50%) scale(1.1);
    }
    .back-to-home .icon {
        margin-right: 10px;
    }
    /* Стили для кнопки при наведении */
    .back-to-home:hover i {
        transform: rotate(360deg);
        transition: transform 0.5s ease;
    }
</style>
<body class="bg-light">

<div class="container mt-4">
    <div class="card shadow p-4">
        <h1 class="text-center text-primary">Управление должностями</h1>

        <!-- Форма поиска -->
        <div class="input-group mb-3">
            <form action="" method="GET">
                <input type="text" class="form-control" name="search" placeholder="Поиск по названию или ставке" value="<?= $search ?>">
                <button class="btn btn-primary" type="submit">Поиск</button>
            </form>
        </div>

        <!-- Форма сортировки -->
        <div class="mb-3">
            <form action="" method="GET">
                <label for="sort_by" class="form-label">Сортировать по</label>
                <select name="sort_by" id="sort_by" class="form-select" onchange="this.form.submit()">
                    <option value="id" <?= $sortBy == 'id' ? 'selected' : '' ?>>ID</option>
                    <option value="name" <?= $sortBy == 'name' ? 'selected' : '' ?>>Название должности</option>
                    <option value="rate" <?= $sortBy == 'rate' ? 'selected' : '' ?>>Ставка</option>
                    <option value="salary" <?= $sortBy == 'salary' ? 'selected' : '' ?>>Оклад</option>
                </select>
                <select name="sort_order" class="form-select" onchange="this.form.submit()">
                    <option value="ASC" <?= $sortOrder == 'ASC' ? 'selected' : '' ?>>По возрастанию</option>
                    <option value="DESC" <?= $sortOrder == 'DESC' ? 'selected' : '' ?>>По убыванию</option>
                </select>
            </form>
        </div>

        <!-- Кнопки экспорта -->
        <div class="mb-3 text-center">
            <a href="?export_search=1&search=<?= $search ?>&sort_by=<?= $sortBy ?>&sort_order=<?= $sortOrder ?>" class="btn btn-warning">Экспортировать результаты в CSV</a>
            <a href="?export_all=1&sort_by=<?= $sortBy ?>&sort_order=<?= $sortOrder ?>" class="btn btn-success">Экспортировать все данные в CSV</a>
        </div>

        <!-- Форма добавления должности -->
        <div class="card p-3 mb-4">
            <h2 class="text-center text-secondary">Добавить новую должность</h2>
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="name" class="form-control" placeholder="Название должности" required>
                </div>
                <div class="col-md-6">
                    <input type="number" step="0.01" name="rate" class="form-control" placeholder="Ставка" required>
                </div>
                <div class="col-md-6">
                    <input type="number" step="0.01" name="salary" class="form-control" placeholder="Оклад" required>
                </div>
                <div class="col-12 text-center">
                    <button type="submit" name="add" class="btn btn-success">Добавить</button>
                </div>
            </form>
        </div>

        <!-- Таблица должностей -->
        <table class="table table-hover table-bordered text-center">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Название должности</th>
                    <th>Ставка</th>
                    <th>Оклад</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $row['rate'] ?></td>
                        <td><?= $row['salary'] ?></td>
                        <td class="d-flex justify-content-center gap-2">
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить должность?')">Удалить</a>
                            <a href="edit_position.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Изменить</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Кнопка на главную -->
<a href="index.php" class="back-to-home">
    <i class="bi bi-house-door icon"></i> На главную
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
