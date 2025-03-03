<?php
$servername = "localhost"; // Сервер (обычно localhost)
$username = "root"; // Имя пользователя (по умолчанию root)
$password = "123456789_K"; // Пароль (если не задан, оставить пустым)
$dbname = "sotrudniki"; // Имя вашей базы данных

// Создание подключения
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка соединения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>
