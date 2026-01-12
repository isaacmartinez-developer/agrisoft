<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "agrisoft_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connexió fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'];
    $quantitat = $_POST['quantitat'];
    $unitat = $_POST['unitat'];
    $lot = $_POST['lot'];
    $caducitat = $_POST['caducitat'];
    $materia_activa = $_POST['materia_activa'];

    if (empty($nom) || empty($quantitat) || empty($lot)) {
        die("Error: Faltan dades obligatòries.");
    }

    $conn->begin_transaction();

    try {
        $sql_check = "SELECT idProducte FROM PRODUCTE WHERE nomComercial = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $nom);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        $idProducte = 0;

        if ($result_check->num_rows > 0) {
            $row = $result_check->fetch_assoc();
            $idProducte = $row['idProducte'];
        } else {
            $sql_insert_prod = "INSERT INTO PRODUCTE (nomComercial, materiaActiva, tipus) VALUES (?, ?, 'Fitosanitari')";
            $stmt_prod = $conn->prepare($sql_insert_prod);
            $stmt_prod->bind_param("ss", $nom, $materia_activa);
            $stmt_prod->execute();
            $idProducte = $conn->insert_id;
            $stmt_prod->close();
        }
        $stmt_check->close();

        $sql_estoc = "INSERT INTO ESTOC_PRODUCTE (idProducte, quantitatDisponible, unitatMesura, numLot, dataCaducitat, dataCompra) 
                      VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt_estoc = $conn->prepare($sql_estoc);
        $stmt_estoc->bind_param("idsss", $idProducte, $quantitat, $unitat, $lot, $caducitat);
        $stmt_estoc->execute();
        $stmt_estoc->close();

        $conn->commit();

        echo "<script>
                alert('Entrada de estoc guardada correctament!');
                window.location.href = '../../index.html'; 
              </script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error al guardar: " . $e->getMessage();
    }
}

$conn->close();
?>