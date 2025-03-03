<?php
include('db_connect.php'); // Подключение к базе

// Проверка подключения
if ($conn->connect_error) {
    die("Ошибка соединения: " . $conn->connect_error);
}
// Экспорт всех записей в CSV
if (isset($_GET['export_all'])) {
    exportToCSV("SELECT e.full_name, c.date, c.time_in, c.time_out, c.total_hours FROM CurrentEmployees c JOIN Employees e ON c.employee_id = e.id");
    exit();
}

// Экспорт результатов поиска в CSV
if (isset($_GET['export_search'])) {
    $search_name = $_GET['search_name'];
    $search_date = $_GET['search_date'];

    $query = "SELECT e.full_name, c.date, c.time_in, c.time_out, c.total_hours
              FROM CurrentEmployees c
              JOIN Employees e ON c.employee_id = e.id
              WHERE 1=1";

    if (!empty($search_name)) {
        $query .= " AND e.full_name LIKE ?";
    }
    if (!empty($search_date)) {
        $query .= " AND c.date = ?";
    }

    exportToCSV($query, [$search_name, $search_date]);
    exit();
}

// Функция для экспорта данных в CSV
function exportToCSV($query, $params = []) {
    global $conn;

    // Подготовка запроса
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $types = str_repeat("s", count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Заголовки для CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=attendance.csv');
    
    $output = fopen('php://output', 'w');
    
    // Запись заголовков столбцов
    $columns = ['Сотрудник', 'Дата', 'Время прихода', 'Время ухода', 'Часы'];
    fputcsv($output, $columns);

    // Запись данных
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

// Фиксация прихода (авто и вручную)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_in'])) {
    $employee_id = $_POST['employee_id'];
    $date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $time_in = !empty($_POST['time_in']) ? $_POST['time_in'] : date('H:i:s');

    // Проверяем, не отмечался ли уже сотрудник сегодня
    $check_query = "SELECT * FROM CurrentEmployees WHERE employee_id = ? AND date = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("is", $employee_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Записываем время прихода
        $insert_query = "INSERT INTO CurrentEmployees (employee_id, date, time_in) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iss", $employee_id, $date, $time_in);
        if ($stmt->execute()) {
            echo "<script>alert('Приход зафиксирован!'); window.location.href='attendance.php';</script>";
        } else {
            echo "<script>alert('Ошибка: " . $stmt->error . "');</script>";
        }
    } else {
        echo "<script>alert('Сотрудник уже отмечен в этот день!');</script>";
    }
}

// Фиксация ухода (авто и вручную)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_out'])) {
    $employee_id = $_POST['employee_id'];
    $date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $time_out = !empty($_POST['time_out']) ? $_POST['time_out'] : date('H:i:s');

    // Проверяем, отмечался ли приход сегодня
    $check_query = "SELECT time_in FROM CurrentEmployees WHERE employee_id = ? AND date = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("is", $employee_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $time_in = $row['time_in'];

        // Вычисляем разницу в минутах и переводим в часы
        $update_query = "UPDATE CurrentEmployees 
        SET time_out = ?, 
            total_hours = TIME_TO_SEC(TIMEDIFF(?, time_in)) / 3600.0
        WHERE employee_id = ? AND date = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssis", $time_out, $time_out, $employee_id, $date);

        if ($stmt->execute()) {
            echo "<script>alert('Уход зафиксирован!'); window.location.href='attendance.php';</script>";
        } else {
            echo "<script>alert('Ошибка обновления: " . $stmt->error . "');</script>";
        }
    } else {
        echo "<script>alert('Приход не найден!');</script>";
    }
}

// Фильтрация записей по имени и дате
$search_name = isset($_GET['search_name']) ? $_GET['search_name'] : '';
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : '';

// Изменяем запрос, чтобы получать id записи
$query = "SELECT c.id, e.full_name, c.date, c.time_in, c.time_out, c.total_hours
          FROM CurrentEmployees c
          JOIN Employees e ON c.employee_id = e.id
          WHERE 1=1";

$params = [];
$types = "";
// Фильтр по имени сотрудника
if (!empty($search_name)) {
    $query .= " AND e.full_name LIKE ?";
    $params[] = "%$search_name%";
    $types .= "s";
}

// Фильтр по дате
if (!empty($search_date)) {
    $query .= " AND c.date = ?";
    $params[] = $search_date;
    $types .= "s";
}

$query .= " ORDER BY c.date DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учет рабочего времени</title>
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
    <div class="container py-5">
        <div class="card shadow-lg p-4">
            <h1 class="text-center text-primary">Учет рабочего времени</h1>
            
            <!-- Форма поиска -->
            <h3 class="text-center">Поиск записей</h3>
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search_name" class="form-control" placeholder="Имя сотрудника" value="<?= htmlspecialchars(isset($_GET['search_name']) ? $_GET['search_name'] : '') ?>">
                </div>
                <div class="col-md-4">
                    <input type="date" name="search_date" class="form-control" value="<?= htmlspecialchars(isset($_GET['search_date']) ? $_GET['search_date'] : '') ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Поиск</button>
                </div>
            </form>
            <!-- Кнопки экспорта CSV -->
            <div class="text-center mt-4">
    <button onclick="printTable()" class="btn btn-primary">Печать таблицы</button>
</div>

<!-- Форма для отметки прихода -->
<h3 class="text-center">Отметить приход</h3>
<form method="POST">
    <div class="mb-3">
        <label for="employee_id" class="form-label">Сотрудник</label>
        <select name="employee_id" class="form-control" required>
            <?php
            // Получаем всех сотрудников для списка
            $employees = $conn->query("SELECT id, full_name FROM Employees");
            while ($row = $employees->fetch_assoc()) {
                echo "<option value='{$row['id']}'>{$row['full_name']}</option>";
            }
            ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="date" class="form-label">Дата</label>
        <input type="date" name="date" class="form-control">
    </div>
    <div class="mb-3">
        <label for="time_in" class="form-label">Время прихода</label>
        <input type="time" name="time_in" class="form-control">
    </div>
    <button type="submit" name="check_in" class="btn btn-success">Пришел</button>
</form>
<hr>

<!-- Форма для отметки ухода -->
<h3 class="text-center">Отметить уход</h3>
<form method="POST">
    <div class="mb-3">
        <label for="employee_id" class="form-label">Сотрудник</label>
        <select name="employee_id" class="form-control" required>
            <?php
            // Получаем всех сотрудников для списка
            $employees = $conn->query("SELECT id, full_name FROM Employees");
            while ($row = $employees->fetch_assoc()) {
                echo "<option value='{$row['id']}'>{$row['full_name']}</option>";
            }
            ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="date" class="form-label">Дата</label>
        <input type="date" name="date" class="form-control">
    </div>
    <div class="mb-3">
        <label for="time_out" class="form-label">Время ухода</label>
        <input type="time" name="time_out" class="form-control">
    </div>
    <button type="submit" name="check_out" class="btn btn-danger">Ушел</button>
</form>
<hr>

            <hr>
            <!-- Таблица учета рабочего времени -->
            <h3 class="text-center">История</h3>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Сотрудник</th>
                        <th>Дата</th>
                        <th>Время прихода</th>
                        <th>Время ухода</th>
                        <th>Часы</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo $row['time_in']; ?></td>
                            <td><?php echo isset($row['time_out']) ? $row['time_out'] : '—'; ?></td>
                            <td><?php echo isset($row['total_hours']) ? round($row['total_hours'], 2) : '—'; ?></td>
                            <!-- Кнопка редактирования. Передаем уникальный id записи -->
                            <td>
                            <a href="edit_attendance.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Редактировать</a>
                            <td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <a href="attendance.php" class="btn btn-secondary mt-3">Сбросить поиск</a>
        </div>
    </div>

    <a href="index.php" class="back-to-home">
        <span class="icon">&#8592;</span> На главную
    </a>
</body>
<script>
function printTable() {
    var printWindow = window.open('', '', 'width=900,height=700');
    printWindow.document.write('<html><head><title>Печать</title>');
    printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h2 class="text-center">Таблица учета рабочего времени</h2>');
    printWindow.document.write(document.querySelector('.table').outerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>

</html>
