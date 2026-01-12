<?php
require "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['name']) && isset($data['coords'])) {
    $name = $conn->real_escape_string($data['name']);
    $coords = json_encode($data['coords']);

    $sql = "INSERT INTO zones (name, coordinates) VALUES ('$name', '$coords')";
    if($conn->query($sql)) {
        echo json_encode(["status" => "ok"]);
    } else {
        echo json_encode(["status" => "error", "msg" => $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "msg" => "Dades incompletes"]);
}
?>
