<?php
include('db_connect.php'); // Подключение к БД

// Проверка подключения
if ($conn->connect_error) {
    die("Ошибка соединения: " . $conn->connect_error);
}

// Обработка редактирования больничного листа
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_sick_leave'])) {
    $employee_id = $_POST['employee_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $sick_leave_number = $_POST['sick_leave_number'];

    // Обновление данных в таблице SickLeaves
    $stmt = $conn->prepare("UPDATE SickLeaves SET start_date = ?, end_date = ?, sick_leave_number = ? WHERE employee_id = ?");
    $stmt->bind_param("sssi", $start_date, $end_date, $sick_leave_number, $employee_id);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Больничный лист обновлён!</div>";
    } else {
        echo "<div class='alert alert-danger'>Ошибка обновления: " . $stmt->error . "</div>";
    }
}

// Обработка удаления больничного листа
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_sick_leave'])) {
    $sick_leave_id = $_POST['sick_leave_id'];

    // Запрос на удаление
    $stmt = $conn->prepare("DELETE FROM SickLeaves WHERE id = ?");
    $stmt->bind_param("i", $sick_leave_id);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Больничный лист удалён!</div>";
    } else {
        echo "<div class='alert alert-danger'>Ошибка удаления: " . $stmt->error . "</div>";
    }
}

// Обработка добавления нового больничного
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_sick_leave'])) {
    $employee_id = $_POST['employee_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $sick_leave_number = $_POST['sick_leave_number'];

    // Вставка данных о больничном
    $stmt = $conn->prepare("INSERT INTO SickLeaves (employee_id, sick_leave_number, start_date, end_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $employee_id, $sick_leave_number, $start_date, $end_date);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Больничный лист добавлен!</div>";
    } else {
        echo "<div class='alert alert-danger'>Ошибка добавления: " . $stmt->error . "</div>";
    }
}


// Получение списка сотрудников для отображения в выпадающем списке
$employeesQuery = "SELECT id, full_name FROM Employees";
$employeesResult = $conn->query($employeesQuery);
if (!$employeesResult) {
    die("Ошибка запроса на получение списка сотрудников: " . $conn->error);
}

// Запрос для получения данных о больничных
$query = "SELECT 
    GROUP_CONCAT(DISTINCT sl.id ORDER BY sl.start_date SEPARATOR ',') AS ids, 
    e.full_name, 
    COUNT(*) AS sick_leave_count, 
    GROUP_CONCAT(DISTINCT sl.start_date ORDER BY sl.start_date SEPARATOR ',') AS start_dates, 
    GROUP_CONCAT(DISTINCT sl.end_date ORDER BY sl.end_date SEPARATOR ',') AS end_dates, 
    GROUP_CONCAT(DISTINCT sl.sick_leave_number ORDER BY sl.start_date SEPARATOR ',') AS sick_leave_numbers,
    GROUP_CONCAT(DISTINCT sl.employee_id ORDER BY sl.start_date SEPARATOR ',') AS employee_ids
FROM 
    SickLeaves sl
JOIN 
    Employees e ON sl.employee_id = e.id
GROUP BY 
    e.full_name";

$result = $conn->query($query);

// Функция для генерации CSV
function generate_csv($data, $filename) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF"; // Добавление BOM для правильной кодировки в Excel
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Сотрудник', 'Дата начала', 'Дата окончания', 'Номер больничного']);
    
    while ($row = $data->fetch_assoc()) {
        fputcsv($output, [$row['ids'], $row['full_name'], $row['start_dates'], $row['end_dates'], $row['sick_leave_numbers']]);
    }
    fclose($output);
    exit;
}

// Скачивание CSV для всех данных
if (isset($_POST['generate_all_csv'])) {
    generate_csv($result, 'all_sick_leave_report.csv');
}

// Скачивание CSV по поисковому запросу
if (isset($_POST['generate_search_csv'])) {
    $search_value = $_POST['search_value'];
    $stmt = $conn->prepare("SELECT * FROM SickLeaves WHERE employee_id IN (SELECT id FROM Employees WHERE full_name LIKE ?)");
    $search_value = "%" . $search_value . "%"; // Поиск по части имени
    $stmt->bind_param("s", $search_value);
    $stmt->execute();
    $search_result = $stmt->get_result();
    generate_csv($search_result, 'search_sick_leave_report.csv');
}

// Закрытие соединения
$conn->close();
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Больничные листы сотрудников</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow-lg p-4">
            <h1 class="text-center text-primary">Больничные листы сотрудников</h1>
            
            <hr>

            <!-- Форма добавления больничного листа -->
            <h3>Добавить новый больничный лист</h3>
            <form method="POST">
                <div class="mb-3">
                    <label for="employee_id" class="form-label">Выберите сотрудника</label>
                    <select name="employee_id" id="employee_id" class="form-control" required>
                        <option value="">Выберите сотрудника</option>
                        <?php while ($employee = $employeesResult->fetch_assoc()) { ?>
                            <option value="<?= $employee['id'] ?>"><?= $employee['full_name'] ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="start_date" class="form-label">Дата начала</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="end_date" class="form-label">Дата окончания</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="sick_leave_number" class="form-label">Номер больничного</label>
                    <input type="text" name="sick_leave_number" id="sick_leave_number" class="form-control" required>
                </div>
                <button type="submit" name="add_sick_leave" class="btn btn-success">Добавить больничный лист</button>
            </form>

            <hr>

            <!-- Поиск больничных -->
            <div class="mb-3">
                <input type="text" id="search" class="form-control" placeholder="Поиск по имени сотрудника" onkeyup="filterTable()">
            </div>

            <!-- Кнопки для генерации отчетов -->
            <form method="POST" class="mb-3">
                <button type="submit" name="generate_all_csv" class="btn btn-info">Скачать все данные в CSV</button>
                <input type="text" name="search_value" id="search_value" class="form-control mt-2" placeholder="Поиск по имени">
                <button type="submit" name="generate_search_csv" class="btn btn-info mt-2">Скачать поиск в CSV</button>
            </form>

            <table id="sickLeaveTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Сотрудник</th>
                        <th onclick="sortTable(2)">Дата начала <span id="sortIcon2">↕</span></th>
                        <th onclick="sortTable(3)">Дата окончания <span id="sortIcon3">↕</span></th>
                        <th>Номер больничного</th>
                        <th>Количество больничных</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo isset($row['full_name']) ? $row['full_name'] : '—'; ?></td>
                            <td>
                                <?php 
                                $start_dates = explode(',', $row['start_dates']);
                                foreach ($start_dates as $date) {
                                    echo $date . "<br>";
                                } 
                                ?>
                            </td>
                            <td>
                                <?php 
                                $end_dates = explode(',', $row['end_dates']);
                                foreach ($end_dates as $date) {
                                    echo $date . "<br>";
                                } 
                                ?>
                            </td>
                            <td>
                                <?php 
                                $sick_leave_numbers = explode(',', $row['sick_leave_numbers']);
                                foreach ($sick_leave_numbers as $number) {
                                    echo $number . "<br>";
                                } 
                                ?>
                            </td>
                            <td><?php echo isset($row['sick_leave_count']) ? $row['sick_leave_count'] : '0'; ?></td>
                            
                            <td> 
                           
                            <button type="button" class="btn btn-warning btn-sm edit-btn" 
    data-bs-toggle="modal" 
    data-bs-target="#editSickLeaveModal" 
    data-id="<?= $row['ids'] ?>" 
    data-start="<?= $row['start_dates'] ?>" 
    data-end="<?= $row['end_dates'] ?>" 
    data-number="<?= $row['sick_leave_numbers'] ?>" 
    data-employee-id="<?= $row['employee_ids'] ?>"
    data-employee-name="<?= htmlspecialchars($row['full_name'], ENT_QUOTES) ?>"
>Изменить</button>


    <button class="btn btn-danger btn-sm delete-btn" 
        data-ids="<?= htmlspecialchars($row['ids']) ?>" 
        data-startdates="<?= htmlspecialchars($row['start_dates']) ?>" 
        data-enddates="<?= htmlspecialchars($row['end_dates']) ?>" 
        data-numbers="<?= htmlspecialchars($row['sick_leave_numbers']) ?>"
        data-bs-toggle="modal"
        data-bs-target="#deleteModal">
    Удалить
</button>


                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Кнопка на главную -->
<a href="index.php" class="btn btn-green rounded-pill shadow-lg d-inline-flex align-items-center px-4 py-2 fw-bold fixed-button">
    <span class="me-2">←</span> На главную
</a>

<style>
    .btn-green {
        background-color: #28a745; /* Зеленый цвет */
        color: white;
        font-size: 1.2rem;
        border: none;
    }

    .btn-green:hover {
        background-color: #218838;
        color: white;
    }

    .fixed-button {
        position: fixed;
        top: 20px;  /* Отступ сверху */
        left: 20px; /* Отступ слева */
        z-index: 1000; /* Поверх всего */
    }
</style>
    </div>
        </div>
       <!-- Модальное окно для удаления -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Удаление больничного</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="sick_leave_id" id="sick_leave_id">
                    <div class="mb-3">
                        <label for="sick_leave_select" class="form-label">Выберите больничный для удаления</label>
                        <select class="form-select" id="sick_leave_select" name="sick_leave_id" required>
                            <!-- Опции будут добавлены через JS -->
                        </select>
                    </div>
                    <button type="submit" name="delete_sick_leave" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>



<!-- Модальное окно для редактирования -->
<div class="modal fade" id="editSickLeaveModal" tabindex="-1" aria-labelledby="editSickLeaveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSickLeaveModalLabel">Редактировать больничный лист</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="editSickLeaveForm" method="POST">
                    <div class="mb-3">
                        <label for="edit_employee_name" class="form-label">Сотрудник</label>
                        <input type="text" id="edit_employee_name" class="form-control" disabled>
                        <input type="hidden" name="employee_id" id="edit_employee_id">
                    </div>
                    <div class="mb-3">
                        <label for="edit_start_date" class="form-label">Дата начала</label>
                        <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_end_date" class="form-label">Дата окончания</label>
                        <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_sick_leave_number" class="form-label">Номер больничного</label>
                        <input type="text" name="sick_leave_number" id="edit_sick_leave_number" class="form-control" required>
                    </div>
                    <button type="submit" name="edit_sick_leave" class="btn btn-success">Сохранить изменения</button>
                </form>
    
        </div>
    </div>
</div>

<script>


document.addEventListener('DOMContentLoaded', function () {
    var deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var sickLeaveIds = button.getAttribute('data-ids').split(',');  // Список ID больничных
            var sickLeaveNumbers = button.getAttribute('data-numbers').split(',');  // Список номеров больничных
            var sickLeaveStartDates = button.getAttribute('data-startdates').split(',');  // Даты начала
            var sickLeaveEndDates = button.getAttribute('data-enddates').split(',');  // Даты окончания

            var select = document.getElementById('sick_leave_select');
            select.innerHTML = ''; // Очистить предыдущие опции

            sickLeaveIds.forEach(function (id, index) {
                var option = document.createElement('option');
                option.value = id;
                option.textContent = '№' + sickLeaveNumbers[index] + ' (' + sickLeaveStartDates[index] + ' - ' + sickLeaveEndDates[index] + ')';
                select.appendChild(option);
            });

            // Заполнение скрытого поля с ID больничного
            document.getElementById('sick_leave_id').value = sickLeaveIds[0];
        });
    });
});


document.addEventListener('DOMContentLoaded', function () {
    var editButtons = document.querySelectorAll('.edit-btn');

    editButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            // Получаем данные из атрибутов data-*
            var employeeId = button.getAttribute('data-employee-id').split(',')[0];  // Используем первое значение
            var employeeName = button.getAttribute('data-employee-name');
            var startDate = button.getAttribute('data-start').split(',')[0];  // Используем первое значение
            var endDate = button.getAttribute('data-end').split(',')[0];  // Используем первое значение
            var sickLeaveNumber = button.getAttribute('data-number').split(',')[0];  // Используем первое значение

            // Заполнение данных в модальном окне
            document.getElementById('edit_employee_id').value = employeeId;
            document.getElementById('edit_employee_name').value = employeeName;
            document.getElementById('edit_start_date').value = startDate;
            document.getElementById('edit_end_date').value = endDate;
            document.getElementById('edit_sick_leave_number').value = sickLeaveNumber;
        });
    });
});
</script>
 






</body>
</html>