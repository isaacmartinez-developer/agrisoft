<?php
require "db.php";

$result = $conn->query("SELECT * FROM parceles");
$out = [];

while ($row = $result->fetch_assoc()) {
    $out[] = [
        "id" => $row["id"],
        "nom" => $row["nom"],
        "ref" => $row["ref_cadastral"],
        "area" => $row["area"],
        "reg" => $row["reg_sistema"],
        "coordenades" => json_decode($row["coordinates"]) // Decodificar JSON a array
    ];
}
echo json_encode($out);
?>