<?php
require "db.php";

$result = $conn->query("SELECT * FROM zones");
$zones = [];

while ($row = $result->fetch_assoc()) {
    $zones[] = [
        "id" => $row["id"],
        "name" => $row["name"],
        "coords" => json_decode($row["coordinates"])
    ];
}

echo json_encode($zones);
?>
