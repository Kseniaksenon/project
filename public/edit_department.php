<?php
include('db_connect.php');

// Обработка редактирования
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $location = $_POST['location'];

    $sql = "UPDATE Departments SET name='$name', location='$location' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert success'>Подразделение обновлено.</div>";
    } else {
        echo "<div class='alert error'>Ошибка: " . $conn->error . "</div>";
    }
}

// Получение данных для редактирования
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM Departments WHERE id=$id");
    $department = $result->fetch_assoc();
} else {
    echo "<div class='alert error'>Ошибка: Подразделение не найдено.</div>";
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование подразделения</title>
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
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .button-small {
            padding: 6px 12px;
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Редактирование подразделения</h1>

    <form method="POST">
        <input type="hidden" name="id" value="<?= $department['id'] ?>">
        <input type="text" name="name" value="<?= $department['name'] ?>" required>
        <input type="text" name="location" value="<?= $department['location'] ?>" required>
        <button type="submit" name="update" class="button button-small">Сохранить изменения</button>
    </form>

    <a href="departments.php" class="button button-small">Назад к списку подразделений</a>
</div>

</body>
</html>
