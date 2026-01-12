<?php
require "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['nom']) && isset($data['coords'])) {
    // Escapar datos para seguridad
    $nom = $conn->real_escape_string($data['nom']);
    $ref = $conn->real_escape_string($data['ref'] ?? '');
    $area = floatval($data['area'] ?? 0);
    $reg = $conn->real_escape_string($data['reg'] ?? '');
    $coords = json_encode($data['coords']); // Convertir array de JS a string JSON

    // Si viene un ID, es una edición (UPDATE), si no, es nuevo (INSERT)
    if (isset($data['id']) && !empty($data['id'])) {
        $id = intval($data['id']);
        $sql = "UPDATE parceles SET nom='$nom', ref_cadastral='$ref', area=$area, reg_sistema='$reg', coordinates='$coords' WHERE id=$id";
    } else {
        $sql = "INSERT INTO parceles (nom, ref_cadastral, area, reg_sistema, coordinates) VALUES ('$nom', '$ref', $area, '$reg', '$coords')";
    }

    if($conn->query($sql)) {
        echo json_encode(["status" => "ok", "id" => $conn->insert_id ?: $id]);
    } else {
        echo json_encode(["status" => "error", "msg" => $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "msg" => "Dades incompletes"]);
}
?>