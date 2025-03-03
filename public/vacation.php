<?php
include('db_connect.php'); // Подключение к БД

// Проверка подключения
if ($conn->connect_error) {
    die("Ошибка соединения: " . $conn->connect_error);
}
// Функция для экспорта всех отпусков в CSV
function exportVacationsToCSV($conn) {
    $query = "SELECT * FROM Vacations ORDER BY start_date ASC";
    $result = $conn->query($query);
    $filename = "vacations_report_" . date("Y-m-d") . ".csv";

    // Устанавливаем заголовки для браузера
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Открытие потока для вывода
    $output = fopen('php://output', 'w');

    // Добавляем BOM для корректного отображения в Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Заголовки столбцов
    fputcsv($output, ['ID', 'Сотрудник', 'Дата начала', 'Дата окончания', 'Тип отпуска']);

    // Записываем данные
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['full_name'],
            $row['start_date'],
            $row['end_date'],
            $row['type']
        ]);
    }

    fclose($output);
    exit();
}


// Проверяем, запрошен ли экспорт
if (isset($_GET['export_all_vacations'])) {
    exportVacationsToCSV($conn);
}




if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_vacation'])) {
    $vacation_id = $_POST['vacation_id'];

    // Подготовка и выполнение запроса на удаление отпуска
    $stmt = $conn->prepare("DELETE FROM Vacations WHERE id = ?");
    $stmt->bind_param("i", $vacation_id);
    $stmt->execute();

    // Проверка на успешность удаления
    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Отпуск успешно удален!'); window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Ошибка удаления отпуска'); window.location.href = window.location.href;</script>";
    }
    $stmt->close();
}



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_vacation'])) {
    if (isset($_POST['employee_id']) && !empty($_POST['employee_id'])) {
        $employee_id = $_POST['employee_id']; // Используем ID сотрудника
        $start_dates = $_POST['start_date'];
        $end_dates = $_POST['end_date'];
        $types = $_POST['type'];

        // Получаем полное имя сотрудника из базы данных, если нужно
        $query = "SELECT full_name FROM Employees WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->bind_result($full_name);
        $stmt->fetch();
        $stmt->close();

        // Подготовка и выполнение запроса для каждого отпуска
        for ($i = 0; $i < count($start_dates); $i++) {
            $start_date = $start_dates[$i];
            $end_date = $end_dates[$i];
            $type = $types[$i];

            $stmt = $conn->prepare("INSERT INTO Vacations (employee_id, full_name, start_date, end_date, type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $employee_id, $full_name, $start_date, $end_date, $type);

            if (!$stmt->execute()) {
                echo "<script>alert('Ошибка добавления отпуска');</script>";
                break;
            }
        }

        echo "<script>alert('Отпуска успешно добавлены!'); window.location.href = window.location.href;</script>";
        exit;
    } else {
        echo "<script>alert('Не выбран сотрудник!');</script>";
    }
}

    
// Обновление отпусков
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_vacation'])) {
    $vacation_ids = $_POST['vacation_ids']; // Массив ID отпусков
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $type = $_POST['type'];

    // Обновляем каждый отпуск
    foreach ($vacation_ids as $id) {
        $stmt = $conn->prepare("UPDATE Vacations SET start_date=?, end_date=?, type=? WHERE id=?");
        $stmt->bind_param("sssi", $start_date, $end_date, $type, $id);
        if (!$stmt->execute()) {
            echo "<script>alert('Ошибка обновления отпуска с ID $id');</script>";
            break;
        }
    }
    
    echo "<script>alert('Отпуска успешно обновлены!'); window.location.href = window.location.href;</script>";
    exit;
}



// Получение списка всех сотрудников из базы данных
$employees = $conn->query("SELECT id, full_name FROM Employees");

// Переменные для фильтрации (по умолчанию текущий месяц)
$start_date_filter = isset($_POST['start_date_filter']) ? $_POST['start_date_filter'] : date('Y-m-01');
$end_date_filter = isset($_POST['end_date_filter']) ? $_POST['end_date_filter'] : date('Y-m-t');
$type_filter = isset($_POST['type_filter']) ? $_POST['type_filter'] : '';

// Получение списка всех отпусков с учетом фильтрации и подсчёта количества отпусков для каждого сотрудника
$query = "
   SELECT 
    GROUP_CONCAT(DISTINCT id ORDER BY start_date SEPARATOR ',') AS ids, 
    full_name, 
    COUNT(*) AS vacation_count, 
    GROUP_CONCAT(DISTINCT start_date ORDER BY start_date SEPARATOR ',') AS start_dates, 
    GROUP_CONCAT(DISTINCT end_date ORDER BY end_date SEPARATOR ',') AS end_dates, 
    GROUP_CONCAT(DISTINCT type ORDER BY type SEPARATOR ', ') AS types
FROM 
    Vacations 
WHERE 
    start_date >= ? 
    AND end_date <= ?
";

if ($type_filter) {
    $query .= " AND type = ?";
}

$query .= " GROUP BY full_name";


$stmt = $conn->prepare($query);
if ($type_filter) {
    $stmt->bind_param('sss', $start_date_filter, $end_date_filter, $type_filter);
} else {
    $stmt->bind_param('ss', $start_date_filter, $end_date_filter);
}

$stmt->execute();
$result_vacations = $stmt->get_result();

 



?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отпуска сотрудников</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card p-4 shadow-lg">
            <h1 class="text-center text-primary">Отпуска сотрудников</h1>
            <a href="?export_all_vacations=1" class="btn btn-success mt-3">📥 Экспортировать в CSV</a>

           <!-- Форма добавления отпусков -->
<h2>Добавить отпуск</h2>
<form method="POST">
    <div id="vacation_fields">
        <div class="row mb-3 align-items-center">
            <div class="col-md-4">
                <label for="employee_id" class="form-label">Сотрудник</label>
                <select name="employee_id" class="form-select" required>
                    <option value="">Выберите сотрудника</option>
                    <?php while ($emp = $employees->fetch_assoc()) { ?>
                        <option value="<?= htmlspecialchars($emp['id']) ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Дата начала</label>
                <input type="date" name="start_date[]" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Дата окончания</label>
                <input type="date" name="end_date[]" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Тип отпуска</label>
                <select name="type[]" class="form-select" required>
                    <option value="Ежегодный">Ежегодный</option>
                    <option value="Учебный">Учебный</option>
                    <option value="Неоплачиваемый">Неоплачиваемый</option>
                </select>
            </div>
        </div>
    </div>

    <button type="submit" name="add_vacation" class="btn btn-primary mt-3">Добавить</button>
</form>

<script>
function addVacationField() {
    const vacationFields = document.getElementById('vacation_fields');
    const newField = vacationFields.children[0].cloneNode(true);
    vacationFields.appendChild(newField);
}
</script>




              
            <h2 class="mt-5">Фильтрация отпусков</h2>
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Дата начала</label>
                        <input type="date" name="start_date_filter" class="form-control" value="<?= $start_date_filter ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Дата окончания</label>
                        <input type="date" name="end_date_filter" class="form-control" value="<?= $end_date_filter ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Тип отпуска</label>
                        <select name="type_filter" class="form-select">
                            <option value="">Все типы</option>
                            <option value="Ежегодный" <?= $type_filter == 'Ежегодный' ? 'selected' : '' ?>>Ежегодный</option>
                            <option value="Учебный" <?= $type_filter == 'Учебный' ? 'selected' : '' ?>>Учебный</option>
                            <option value="Неоплачиваемый" <?= $type_filter == 'Неоплачиваемый' ? 'selected' : '' ?>>Неоплачиваемый</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Применить фильтр</button>
                    </div>
                </div>
            </form>

            <table class="table table-bordered">
    <table class="table table-bordered">
    <thead>
        <tr>
            <th>Сотрудник</th>
            <th>Количество отпусков</th>
            <th data-column="start_dates" class="sortable">
                Дата начала <span class="sort-icon" data-order="asc">▲</span>
            </th>
            <th data-column="end_dates" class="sortable">
                Дата окончания <span class="sort-icon" data-order="asc">▲</span>
            </th>
            <th>Тип отпуска</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($vacation = $result_vacations->fetch_assoc()) { ?>
        <tr>
            <td><?= htmlspecialchars($vacation['full_name']) ?></td>
            <td><?= $vacation['vacation_count'] ?? 'Не указано' ?></td>
            <td data-timestamp="<?= strtotime(explode("\n", $vacation['start_dates'])[0]) ?>">
                <?= nl2br(htmlspecialchars($vacation['start_dates'])) ?>
            </td>
            <td data-timestamp="<?= strtotime(explode("\n", $vacation['end_dates'])[0]) ?>">
                <?= nl2br(htmlspecialchars($vacation['end_dates'])) ?>
            </td>
            <td><?= htmlspecialchars($vacation['types']) ?></td>
            <td class="text-center">
    <div class="d-flex flex-column gap-2">
        <button class="btn btn-warning btn-sm edit-btn"
            data-ids="<?= htmlspecialchars($vacation['ids']) ?>" 
            data-startdates="<?= htmlspecialchars($vacation['start_dates']) ?>" 
            data-enddates="<?= htmlspecialchars($vacation['end_dates']) ?>" 
            data-types="<?= htmlspecialchars($vacation['types']) ?>"
            data-bs-toggle="modal"
            data-bs-target="#editModal">
            Изменить
        </button>
    </div>
</td>
<td class="text-center">
    <div class="d-flex flex-column gap-2">
        <button class="btn btn-danger btn-sm delete-btn" 
                data-ids="<?= htmlspecialchars($vacation['ids']) ?>" 
                data-startdates="<?= htmlspecialchars($vacation['start_dates']) ?>" 
                data-enddates="<?= htmlspecialchars($vacation['end_dates']) ?>" 
                data-types="<?= htmlspecialchars($vacation['types']) ?>"
                data-bs-toggle="modal"
                data-bs-target="#deleteModal">
            Удалить
        </button>
    </div>
</td>


        </tr>
    <?php } ?>
    </tbody>
</table>



            
        </div>
        </div>
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Удаление отпуска</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="vacation_id" id="vacation_id">
                    <div class="mb-3">
                        <label for="vacation_select" class="form-label">Выберите отпуск для удаления</label>
                        <select class="form-select" id="vacation_select" name="vacation_id" required>
                            <!-- Опции будут добавлены через JS -->
                        </select>
                    </div>
                    <button type="submit" name="delete_vacation" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>

    </div>
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Редактирование отпуска</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="vacation_select_edit" class="form-label">Выберите отпуск</label>
                        <select class="form-select" id="vacation_select_edit" name="vacation_ids[]" required>
                            <!-- Опции добавляются через JS -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="start_date_edit" class="form-label">Дата начала</label>
                        <input type="date" id="start_date_edit" name="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date_edit" class="form-label">Дата окончания</label>
                        <input type="date" id="end_date_edit" name="end_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="type_edit" class="form-label">Тип отпуска</label>
                        <select id="type_edit" name="type" class="form-select" required>
                            <option value="Ежегодный">Ежегодный</option>
                            <option value="Учебный">Учебный</option>
                            <option value="Неоплачиваемый">Неоплачиваемый</option>
                        </select>
                    </div>
                    <button type="submit" name="edit_vacation" class="btn btn-primary">Сохранить изменения</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".sortable").forEach(header => {
        header.addEventListener("click", function () {
            let column = this.dataset.column;
            let order = this.querySelector(".sort-icon").dataset.order;
            let table = document.querySelector("table tbody");
            let rows = Array.from(table.rows);

            rows.sort((rowA, rowB) => {
                let valA = parseInt(rowA.querySelector(`td[data-timestamp]`).dataset.timestamp);
                let valB = parseInt(rowB.querySelector(`td[data-timestamp]`).dataset.timestamp);
                return order === "asc" ? valA - valB : valB - valA;
            });

            rows.forEach(row => table.appendChild(row));

            // Обновляем иконку сортировки
            let icon = this.querySelector(".sort-icon");
            icon.dataset.order = order === "asc" ? "desc" : "asc";
            icon.textContent = order === "asc" ? "▼" : "▲";
        });
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".edit-btn").forEach(button => {
        button.addEventListener("click", function () {
            // Разделяем данные отпуска на массивы
            let ids = this.dataset.ids.split(',');
            let startDates = this.dataset.startdates.split(',');
            let endDates = this.dataset.enddates.split(',');
            let types = this.dataset.types.split(', ');

            let select = document.getElementById("vacation_select");
            select.innerHTML = ""; // Очищаем текущие опции

            // Добавляем все отпуска в выпадающий список
            ids.forEach((id, index) => {
                let option = document.createElement("option");
                option.value = id;
                option.textContent = `${startDates[index]} - ${endDates[index]} (${types[index]})`;
                select.appendChild(option);
            });

            // Устанавливаем значения первого отпуска по умолчанию
            document.getElementById("vacation_id").value = ids[0];
            document.getElementById("edit_start_date").value = startDates[0];
            document.getElementById("edit_end_date").value = endDates[0];
            document.getElementById("edit_type").value = types[0];

            // Обработчик изменения выбора
            select.addEventListener("change", function () {
                let selectedIndex = this.selectedIndex;
                document.getElementById("vacation_id").value = ids[selectedIndex];
                document.getElementById("edit_start_date").value = startDates[selectedIndex];
                document.getElementById("edit_end_date").value = endDates[selectedIndex];
                document.getElementById("edit_type").value = types[selectedIndex];
            });
        });
    });
});
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".delete-btn").forEach(button => {
        button.addEventListener("click", function () {
            // Разделяем данные отпуска на массивы
            let ids = this.dataset.ids.split(',');
            let startDates = this.dataset.startdates.split(',');
            let endDates = this.dataset.enddates.split(',');
            let types = this.dataset.types.split(', ');

            let select = document.getElementById("vacation_select");
            select.innerHTML = ""; // Очищаем текущие опции

            // Добавляем все отпуска в выпадающий список
            ids.forEach((id, index) => {
                let option = document.createElement("option");
                option.value = id;
                option.textContent = `${startDates[index]} - ${endDates[index]} (${types[index]})`;
                select.appendChild(option);
            });
        });
    });
});


</script>

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



<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".edit-btn").forEach(button => {
        button.addEventListener("click", function () {
            let vacationIds = this.dataset.ids.split(",");
            let startDates = this.dataset.startdates.split(",");
            let endDates = this.dataset.enddates.split(",");
            let types = this.dataset.types.split(",");

            let vacationSelect = document.getElementById("vacation_select_edit");
            vacationSelect.innerHTML = ""; // Очищаем перед добавлением новых данных

            for (let i = 0; i < vacationIds.length; i++) {
                let option = document.createElement("option");
                option.value = vacationIds[i];
                option.textContent = `Отпуск ${startDates[i]} - ${endDates[i]} (${types[i]})`;
                vacationSelect.appendChild(option);
            }
        });
    });
});
</script>

</body>
</html>
