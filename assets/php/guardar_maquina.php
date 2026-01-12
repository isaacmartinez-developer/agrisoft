<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "agrisoft_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de connexió: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recollim dades
    $nom = $_POST['nom'];
    $tipus = $_POST['tipus'];
    $matricula = $_POST['matricula'];
    $tipusCombustible = $_POST['tipusCombustible'];
    
    $cavalls = !empty($_POST['cavalls']) ? $_POST['cavalls'] : 0;

    $sql = "INSERT INTO MAQUINARIA (nom, tipus, matricula, tipusCombustible, cavalls) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssssi", $nom, $tipus, $matricula, $tipusCombustible, $cavalls);

        if ($stmt->execute()) {
            echo "<script>
                    alert('Client guardat correctament!');
                    window.location.href = '../../index.html';
                  </script>";
        } else {
            echo "Error al guardar: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error en preparar la consulta: " . $conn->error;
    }
}

$conn->close();
?>