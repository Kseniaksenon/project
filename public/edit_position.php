<?php
include('db_connect.php'); // Подключение к БД

// Устанавливаем кодировку соединения
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    // Получаем данные из формы
    $id = $_POST['id'];
    $name = $_POST['name'];
    $rate = $_POST['rate'];
    $salary = $_POST['salary'];

    // Обновляем запись в базе данных
    $sql = "UPDATE Positions SET name = '$name', rate = '$rate', salary = '$salary' WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "Должность обновлена.";
        header("Location: positions.php"); // Перенаправляем на страницу с должностями
        exit();
    } else {
        echo "Ошибка: " . $conn->error;
    }
}

// Получаем id должности для редактирования
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM Positions WHERE id = $id");

    if ($result->num_rows > 0) {
        $position = $result->fetch_assoc();
    } else {
        echo "Должность не найдена.";
        exit();
    }
} else {
    echo "ID должности не передан.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать должность</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="card shadow p-4">
        <h1 class="text-center text-primary">Редактировать должность</h1>

        <form method="POST" class="row g-3">
            <input type="hidden" name="id" value="<?= $position['id'] ?>">

            <div class="col-md-6">
                <input type="text" name="name" class="form-control" placeholder="Название должности" value="<?= htmlspecialchars($position['name'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6">
                <input type="number" step="0.01" name="rate" class="form-control" placeholder="Ставка" value="<?= $position['rate'] ?>" required>
            </div>
            <div class="col-md-6">
                <input type="number" step="0.01" name="salary" class="form-control" placeholder="Оклад" value="<?= $position['salary'] ?>" required>
            </div>
            <div class="col-12 text-center">
                <button type="submit" name="update" class="btn btn-success">Обновить</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
