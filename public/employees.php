<?php
// Подключение к БД
include('db_connect.php'); 


function generateQuery($search, $gender, $ageFilter, $sortBy, $sortOrder, $experienceDate) {
    $query = "
    SELECT Employees.*, Positions.id AS position_id, Positions.name AS position_name,
        TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) AS age,
        TIMESTAMPDIFF(YEAR, hire_date, '$experienceDate') AS experience_years
    FROM Employees
    JOIN Positions ON Employees.position_id = Positions.id
    ";

    $conditions = [];
    
    if (!empty($search)) {
        $conditions[] = "(full_name LIKE '%$search%' OR staff_number LIKE '%$search%')";
    }
    
    if (!empty($gender)) {
        $conditions[] = "gender = '$gender'";
    }

    if (!empty($ageFilter)) {
        $conditions[] = "TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= $ageFilter";
    }

    if (count($conditions) > 0) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $orderByFields = ['id', 'full_name', 'staff_number', 'hire_date', 'age'];
    if (!in_array($sortBy, $orderByFields)) {
        $sortBy = 'id';
    }
    $query .= " ORDER BY $sortBy $sortOrder";
    
    return $query;
}
if (isset($_GET['action']) && $_GET['action'] == 'print') {
    exportToPrint("SELECT * FROM Employees", $conn);
    exit(); // Важно: чтобы HTML ниже не загружался
}


// Фильтры из GET-запроса
$search = isset($_GET['search']) ? $_GET['search'] : '';
$gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'id';
$ageFilter = isset($_GET['age']) ? intval($_GET['age']) : '';
$sortOrder = isset($_GET['sort_order']) && $_GET['sort_order'] == 'desc' ? 'desc' : 'asc';
$experienceDate = isset($_GET['experience_date']) ? $_GET['experience_date'] : date('Y-m-d'); // <-- Добавляем эту строку


// Генерируем SQL-запрос, передавая дату расчета стажа
$query = generateQuery($search, $gender, $ageFilter, $sortBy, $sortOrder, $experienceDate);
$result = $conn->query($query);
// Удаление сотрудника
if (isset($_POST['delete_id'])) {
    $deleteId = intval($_POST['delete_id']);
    $conn->query("DELETE FROM Employees WHERE id = $deleteId");
    header("Location: employees.php");
    exit();
}

// Экспорт в CSV
function exportToPrint($query, $conn) {
    $result = $conn->query($query);

    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Печать отчета</title>
        <style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            @media print {
                button { display: none; } /* Скрываем кнопку при печати */
            }
        </style>
    </head>
    <body>
        <h2>Отчет по сотрудникам</h2>
        <button onclick="window.print()">🖨️ Печать</button>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Табельный номер</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Подразделение</th>
                    <th>Должность</th>
                    <th>Дата приема</th>
                </tr>
            </thead>
            <tbody>';

    // Вывод данных
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody>
        </table>
        <script>
            window.onload = function() {
                window.print(); // Автоматически открываем окно печати
            };
        </script>
    </body>
    </html>';
}


// Обработка добавления нового сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $fullName = $conn->real_escape_string($_POST['full_name']);
    $staffNumber = $conn->real_escape_string($_POST['staff_number']);
    $birthDate = $_POST['birth_date'];
    $gender = $conn->real_escape_string($_POST['gender']);
    $subdivision = $conn->real_escape_string($_POST['subdivision']);
    $positionId = intval($_POST['position_id']);
    $hireDate = $_POST['hire_date'];

    // Проверяем, существует ли сотрудник с таким табельным номером
    $checkQuery = "SELECT id FROM Employees WHERE staff_number = '$staffNumber'";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        echo "<script>alert('Ошибка: Табельный номер уже существует!');</script>";
    } else {
        // Добавляем нового сотрудника
        $insertQuery = "INSERT INTO Employees (full_name, staff_number, birth_date, gender, subdivision, position_id, hire_date) 
                        VALUES ('$fullName', '$staffNumber', '$birthDate', '$gender', '$subdivision', $positionId, '$hireDate')";
        
        if ($conn->query($insertQuery)) {
            header("Location: employees.php"); // Перенаправление после успешного добавления
            exit();
        } else {
            echo "<script>alert('Ошибка при добавлении: " . $conn->error . "');</script>";
        }
    }
    if (isset($_GET['action']) && $_GET['action'] == 'print') {
        exportToPrint("SELECT * FROM employees", $conn);
    }
    
}
// Обновление данных сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $updateId = intval($_POST['update_id']);
    $fullName = $conn->real_escape_string($_POST['full_name']);
    $staffNumber = $conn->real_escape_string($_POST['staff_number']);
    $birthDate = $_POST['birth_date'];
    $gender = $conn->real_escape_string($_POST['gender']);
    $subdivision = $conn->real_escape_string($_POST['subdivision']);
    $positionId = intval($_POST['position_id']);
    $hireDate = $_POST['hire_date'];

    $updateQuery = "UPDATE Employees 
                    SET full_name = '$fullName', 
                        staff_number = '$staffNumber', 
                        birth_date = '$birthDate', 
                        gender = '$gender', 
                        subdivision = '$subdivision', 
                        position_id = $positionId, 
                        hire_date = '$hireDate' 
                    WHERE id = $updateId";

    if ($conn->query($updateQuery)) {
        header("Location: employees.php"); // Перенаправление после успешного обновления
        exit();
    } else {
        echo "<script>alert('Ошибка при обновлении: " . $conn->error . "');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сотрудники</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="card shadow p-4">
        <h1 class="text-center text-primary">Управление сотрудниками</h1>

        <!-- Форма поиска и фильтрации -->
        <div class="input-group mb-3">
            <form action="" method="GET" class="d-flex gap-3">
                <input type="text" class="form-control" name="search" placeholder="Поиск"
                <!-- Фильтрация по полу -->
                <select name="gender" class="form-select" style="width: 150px;">
                    <option value="">Все</option>
                    <option value="M" <?= $gender == 'M' ? 'selected' : '' ?>>Мужчина</option>
                    <option value="F" <?= $gender == 'F' ? 'selected' : '' ?>>Женщина</option>
                </select>
                <input type="number" class="form-control" name="age" placeholder="Возраст" value="<?= isset($_GET['age']) ? htmlspecialchars($_GET['age']) : '' ?>">


                <!-- Кнопка поиска -->
               
            <input type="date" class="form-control" name="experience_date" value="<?= isset($_GET['experience_date']) ? $_GET['experience_date'] : date('Y-m-d') ?>">
            <button class="btn btn-primary" type="submit">Поиск</button>
        </form>
        </div>

        <!-- Кнопки экспорта -->
        <div class="mb-3 text-center">
        <a href="?action=print" class="btn btn-primary">Печать отчета</a>


        </div>

        <!-- Сортировка -->
        <div class="mb-3 text-center">
            <a href="?search=<?= $search ?>&gender=<?= $gender ?>&sort_by=full_name&sort_order=<?= $sortOrder == 'asc' ? 'desc' : 'asc' ?>" class="btn btn-info">Сортировать по ФИО</a>
            <a href="?search=<?= $search ?>&gender=<?= $gender ?>&sort_by=staff_number&sort_order=<?= $sortOrder == 'asc' ? 'desc' : 'asc' ?>" class="btn btn-info">Сортировать по табельному номеру</a>
            <a href="?search=<?= $search ?>&gender=<?= $gender ?>&sort_by=hire_date&sort_order=<?= $sortOrder == 'asc' ? 'desc' : 'asc' ?>" class="btn btn-info">Сортировать по дате приема</a>
        </div>

        <!-- Форма добавления сотрудника -->
        <div class="card p-3 mb-4">
            <h2 class="text-center text-secondary">Добавить нового сотрудника</h2>
            <form method="POST" class="row g-3">
    <input type="hidden" name="add_employee" value="1">
    <div class="col-md-6">
        <input type="text" name="full_name" class="form-control" placeholder="ФИО" required>
    </div>
    <div class="col-md-6">
        <input type="text" name="staff_number" class="form-control" placeholder="Табельный номер" required>
    </div>
    <div class="col-md-6">
        <label for="birth_date" class="form-label">Дата рождения</label>
        <input type="date" id="birth_date" name="birth_date" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Дата приема</label>
        <input type="date" name="hire_date" class="form-control" required>
    </div>
    <div class="col-md-6">
        <select name="gender" class="form-select" required>
            <option value="M">Мужчина</option>
            <option value="F">Женщина</option>
        </select>
    </div>
    <div class="col-md-6">
        <input type="text" name="subdivision" class="form-control" placeholder="Подразделение" required>
    </div>
    <div class="col-md-6">
        <select name="position_id" class="form-select" required>
            <?php
            $positions = $conn->query("SELECT * FROM Positions");
            while ($pos = $positions->fetch_assoc()): ?>
                <option value="<?= $pos['id'] ?>"><?= $pos['name'] ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-12 text-center">
        <button class="btn btn-success">Добавить сотрудника</button>
    </div>
</form>

        <!-- Вывод сотрудников -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Табельный номер</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Подразделение</th>
                    <th>Должность</th>
                    <th>Дата приема</th>
                    <th>Стаж (лет)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['full_name'] ?></td>
                        <td><?= $row['staff_number'] ?></td>
                        <td><?= $row['birth_date'] ?></td>
                        <td><?= $row['gender'] == 'M' ? 'Мужчина' : 'Женщина' ?></td>
                        <td><?= $row['subdivision'] ?></td>
                        <td><?= $row['position_name'] ?></td>
                        <td><?= $row['hire_date'] ?></td>
                        <td><?= $row['experience_years'] ?></td>
                        <td>
                        <td>
                        <td>
    <div class="d-flex justify-content-between">
        <form method="POST" style="display:inline-block;">
            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
            <button class="btn btn-danger btn-sm" onclick="return confirm('Удалить сотрудника?')">Удалить</button>
        </form>
        <button class="btn btn-warning btn-sm" onclick='editEmployee(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' data-bs-toggle="modal" data-bs-target="#editEmployeeModal">Изменить</button>
    </div>
</td>


                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<script>
  function editEmployee(employee) {
    // Заполняем скрытое поле ID сотрудника для обновления
    document.querySelector('#editEmployeeModal [name="update_id"]').value = employee.id;
    document.querySelector('#editEmployeeModal [name="full_name"]').value = employee.full_name;
    document.querySelector('#editEmployeeModal [name="staff_number"]').value = employee.staff_number;
    document.querySelector('#editEmployeeModal [name="birth_date"]').value = employee.birth_date;
    document.querySelector('#editEmployeeModal [name="gender"]').value = employee.gender;
    document.querySelector('#editEmployeeModal [name="subdivision"]').value = employee.subdivision;
    document.querySelector('#editEmployeeModal [name="hire_date"]').value = employee.hire_date;

    // Проверяем, есть ли ID должности и устанавливаем его
    if (employee.position_id) {
        document.querySelector('#editEmployeeModal [name="position_id"]').value = employee.position_id;
    }
}




</script>

</body>
</html>
<a href="index.php" class="back-to-home">
    <span class="icon">&#8592;</span> На главную
</a>

<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
<!-- Модальное окно -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактирование сотрудника</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="employees.php">
                    <input type="hidden" name="update_id">
                    
                    <div class="mb-3">
                        <label class="form-label">ФИО</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Табельный номер</label>
                        <input type="text" name="staff_number" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Дата рождения</label>
                        <input type="date" name="birth_date" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Пол</label>
                        <select name="gender" class="form-control">
                            <option value="M">Мужчина</option>
                            <option value="F">Женщина</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Подразделение</label>
                        <input type="text" name="subdivision" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Должность</label>
                        <select name="position_id" class="form-control">
                            <?php
                            $positions = $conn->query("SELECT * FROM Positions");
                            while ($pos = $positions->fetch_assoc()) {
                                echo "<option value='{$pos['id']}'>{$pos['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
    <label class="form-label">Дата приема</label>
    <input type="date" name="hire_date" class="form-control" required>
</div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</html>
