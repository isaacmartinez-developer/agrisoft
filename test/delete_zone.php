<?php
require "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'])) {
    $id = intval($data['id']);
    $sql = "DELETE FROM zones WHERE id = $id";
    if($conn->query($sql)) {
        echo json_encode(["status" => "ok"]);
    } else {
        echo json_encode(["status" => "error", "msg" => $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "msg" => "ID no definit"]);
}
?>
