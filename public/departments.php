<?php
include('db_connect.php'); // Подключение к БД

// Добавление подразделения
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
    $name = $_POST['name'];
    $location = $_POST['location'];

    $sql = "INSERT INTO Departments (name, location) VALUES ('$name', '$location')";
    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert success'>Подразделение добавлено.</div>";
    } else {
        echo "<div class='alert error'>Ошибка: " . $conn->error . "</div>";
    }
}

// Удаление подразделения
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM Departments WHERE id=$id");
}

// Сортировка
$order_by = 'id'; // По умолчанию сортировка по ID
$order_direction = 'ASC'; // По умолчанию сортировка по возрастанию

if (isset($_GET['sort_by'])) {
    $order_by = $_GET['sort_by'];
}

if (isset($_GET['sort_order'])) {
    $order_direction = $_GET['sort_order'];
}

// Поиск подразделений
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $result = $conn->query("SELECT * FROM Departments WHERE name LIKE '%$search%' OR location LIKE '%$search%' ORDER BY $order_by $order_direction");
} else {
    $result = $conn->query("SELECT * FROM Departments ORDER BY $order_by $order_direction");
}

// Экспорт в CSV
function exportToCSV($conn, $search = "", $order_by = "id", $order_direction = "ASC") {
    $query = "SELECT * FROM Departments";
    if ($search) {
        $query .= " WHERE name LIKE '%$search%' OR location LIKE '%$search%'";
    }
    $query .= " ORDER BY $order_by $order_direction";

    $result = $conn->query($query);
    $filename = "departments_report_" . date("Y-m-d") . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['ID', 'Название подразделения', 'Местоположение']);

    while ($row = $result->fetch_assoc()) {
        $row['name'] = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
        fputcsv($output, [
            $row['id'], 
            $row['name'], 
            $row['location']
        ]);
    }

    fclose($output);
    exit();
}

if (isset($_GET['export_search'])) {
    exportToCSV($conn, $search, $order_by, $order_direction);
}

if (isset($_GET['export_all'])) {
    exportToCSV($conn, '', $order_by, $order_direction);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подразделения</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7fc;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .button {
            padding: 12px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .table-wrapper {
            margin-top: 30px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
        }
        td {
            background-color: #fff;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert.success {
            background-color: #28a745;
            color: white;
        }
        .alert.error {
            background-color: #dc3545;
            color: white;
        }
        .search-bar {
            margin-bottom: 20px;
        }
        .search-bar input {
            padding: 8px;
            width: 80%;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .search-bar button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .search-bar button:hover {
            background-color: #0056b3;
        }
        .back-to-home {
            position: fixed;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 30px;
            font-size: 18px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, background-color 0.3s;
            text-decoration: none;
        }
        .back-to-home:hover {
            background-color: #0056b3;
            transform: translateY(-50%) scale(1.1);
        }
        .back-to-home i {
            margin-right: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Список подразделений</h1>

    <!-- Форма поиска -->
    <div class="search-bar">
        <form method="GET">
            <input type="text" name="search" placeholder="Поиск по названию или местоположению" value="<?= $search ?>">
            <button type="submit" class="button">Поиск</button>
        </form>
    </div>

    <!-- Кнопки экспорта -->
    <div class="text-center">
        <a href="?export_search=1&search=<?= $search ?>&sort_by=<?= $order_by ?>&sort_order=<?= $order_direction ?>" class="button">Экспортировать результаты по поиску в CSV</a>
        <a href="?export_all=1&sort_by=<?= $order_by ?>&sort_order=<?= $order_direction ?>" class="button">Экспортировать все данные в CSV</a>
    </div>

    <!-- Форма добавления -->
    <h2>Добавить подразделение</h2>
    <form method="POST" class="form-group">
        <input type="text" name="name" placeholder="Название" required>
        <input type="text" name="location" placeholder="Местоположение" required>
        <button type="submit" name="add" class="button">Добавить</button>
    </form>

    <!-- Сообщения об ошибках или успешных действиях -->
    <?php if (isset($message)) { echo $message; } ?>

    <!-- Таблица подразделений -->
    <div class="table-wrapper">
        <table>
            <tr>
                <th><a href="?sort_by=id&sort_order=<?= $order_direction == 'ASC' ? 'DESC' : 'ASC' ?>">ID</a></th>
                <th><a href="?sort_by=name&sort_order=<?= $order_direction == 'ASC' ? 'DESC' : 'ASC' ?>">Название</a></th>
                <th><a href="?sort_by=location&sort_order=<?= $order_direction == 'ASC' ? 'DESC' : 'ASC' ?>">Местоположение</a></th>
                <th>Действия</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['location'] ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="?delete=<?= $row['id'] ?>" class="button" onclick="return confirm('Удалить подразделение?')">Удалить</a>
                            <a href="edit_department.php?id=<?= $row['id'] ?>" class="button">Изменить</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<!-- Кнопка на главную -->
<a href="index.php" class="back-to-home">
    <i class="bi bi-house-door icon"></i> На главную
</a>

</body>
</html>
