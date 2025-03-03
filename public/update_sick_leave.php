<?php
include('db_connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $full_name = $_POST['full_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $sick_leave_number = $_POST['sick_leave_number'];

    $stmt = $conn->prepare("UPDATE SickLeaves SET full_name=?, start_date=?, end_date=?, sick_leave_number=? WHERE id=?");
    $stmt->bind_param("ssssi", $full_name, $start_date, $end_date, $sick_leave_number, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Больничный обновлен"]);
    } else {
        echo json_encode(["success" => false, "message" => "Ошибка при обновлении"]);
    }

    $stmt->close();
    $conn->close();
}
?>
