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
    $tipus = $_POST['tipus'];

    $sql = "INSERT INTO ESPECIE (Nom, tipus) VALUES (?, ?)";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ss", $nom, $tipus);

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
        echo "Error preparant la consulta: " . $conn->error;
    }
}

$conn->close();
?>
