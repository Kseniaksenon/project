<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "123456789_K";
$dbname = "sotrudniki";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $username_input = $_POST["username"]; // Получаем логин из формы
    $password_input = $_POST["password"];

    // Проверка пароля
    $sql = "SELECT password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Сравниваем введённый пароль с "admin13"
        if ($password_input === "admin13") {
            $_SESSION['loggedin'] = true;
        } else {
            $login_error = "Неверный пароль!";
        }
    } else {
        $login_error = "Пользователь не найден!";
    }
}
    

$loggedin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учёт сотрудников</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background: #e3f2fd;
            color: #333;
            text-align: center;
        }
        .header {
            background-color: rgb(25, 31, 34);
            color: #fff;
            padding: 60px 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            margin: 10px 0;
            font-weight: 700;
            font-size: 3rem;
            letter-spacing: 2px;
        }
        .container {
            padding: 50px 20px;
        }
        .button-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            justify-content: center;
            max-width: 800px;
            margin: 30px auto;
        }
        .button {
            display: flex;
            justify-content: center;
            align-items:center;
            padding: 20px 30px;
            font-size: 22px;
            font-weight: 500;
            text-align: center;
            color: #fff;
            background-color: rgb(102, 133, 148);
            border-radius: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .button:hover {
            background-color: rgb(66, 126, 158);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            transform: translateY(-5px);
        }
        .login-box {
    background: #bcd6e3;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
    width: 300px;
    margin: auto;
    margin-top: -39px; /* Уменьшаем отступ сверху */
}

        .login-box input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .login-box button {
            width: 100%;
            padding: 12px;
            background: rgb(102, 133, 148);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        .logout-container {
            margin-top: 30px;
        }
        .logout-button {
            background: rgb(58, 51, 51);
            padding: 12px 30px;
            font-size: 18px;
            font-weight: 500;
            border: none;
            color: #fff;
            border-radius: 30px;
            cursor: pointer;
            transition: 0.3s;
        }
        .logout-button:hover {
            background: rgb(172, 50, 50);
        }
        .footer {
            background-color: rgb(14, 19, 21);
            color: #fff;
            text-align: center;
            padding: 20px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Учёт сотрудников на предприятии</h1>
    </div>

    <div class="container">
        <?php if (!$loggedin): ?>
            <div class="login-box">
                <h2>Вход</h2>
                <img src="images.jpg" alt="Человечек" style="width: 100px; height: auto; margin-top: 10px;">
                <form method="POST">
                    <input type="text" name="username" placeholder="Логин" required>
                    <input type="password" name="password" placeholder="Пароль" required>
                    <div style="color: red; font-size: 14px;"><?php echo $login_error; ?></div>
                    <button type="submit">Войти</button>
                </form>
            </div>
        <?php else: ?>
            <div class="button-container">
                <a href="employees.php" class="button">Сотрудники</a>
                <a href="positions.php" class="button">Должности</a>
                <a href="departments.php" class="button">Подразделения</a>
                <a href="attendance.php" class="button">Текущие сотрудники</a>
                <a href="vacation.php" class="button">Отпуск</a>
                <a href="sick_leave.php" class="button">Больничный</a>
            </div>
            <div class="logout-container">
                <form method="POST">
                    <button type="submit" name="logout" class="logout-button">Выйти</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>© 2025 Учёт сотрудников - Все права защищены</p>
    </div>
</body>
</html>
