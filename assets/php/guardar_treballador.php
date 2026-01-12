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

    // Funció petita per convertir dates buides a NULL
    function checkDateVal($val) {
        return empty($val) ? null : $val;
    }

    // RECOLLIDA DE DADES (22 camps)
    $nom = $_POST['nom'];
    $fotografia = $_POST['fotografia'];
    $doc_identitat = $_POST['document_identitat'];
    
    // Camps de data amb tractament especial
    $data_naixement = checkDateVal($_POST['data_naixement']);
    $data_inici = checkDateVal($_POST['data_inici']);
    $data_fi = checkDateVal($_POST['data_fi']);

    $lloc_naixement = $_POST['lloc_naixement'];
    $nacionalitat = $_POST['nacionalitat'];
    $residencia = $_POST['residencia'];
    $telefon = $_POST['telefon'];
    $email = $_POST['email'];
    $adreca = $_POST['adreca'];
    $contacte_emergencia = $_POST['contacte_emergencia'];
    $compte_bancari = $_POST['compte_bancari'];
    $categoria = $_POST['categoria_professional'];
    $tipus_contracte = $_POST['tipus_contracte'];
    $historial = $_POST['historial_laboral'];
    $formacio = $_POST['formacio'];
    $habilitats = $_POST['habilitats'];
    $idiomes = $_POST['idiomes'];
    $num_ss = $_POST['num_seguretat_social'];
    $permis_treball = $_POST['permis_treball'];

    $sql = "INSERT INTO TREBALLADOR (
        nom, fotografia, document_identitat, data_naixement, lloc_naixement, 
        nacionalitat, residencia, telefon, email, adreca, contacte_emergencia, 
        compte_bancari, categoria_professional, tipus_contracte, data_inici, 
        data_fi, historial_laboral, formacio, habilitats, idiomes, 
        num_seguretat_social, permis_treball
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $types = str_repeat("s", 22); 
        
        $stmt->bind_param($types, 
            $nom, $fotografia, $doc_identitat, $data_naixement, $lloc_naixement,
            $nacionalitat, $residencia, $telefon, $email, $adreca, $contacte_emergencia,
            $compte_bancari, $categoria, $tipus_contracte, $data_inici,
            $data_fi, $historial, $formacio, $habilitats, $idiomes,
            $num_ss, $permis_treball
        );

        if ($stmt->execute()) {
            echo "<script>
                    alert('Treballador guardat correctament!');
                    window.location.href = '../../index.html';
                  </script>";
        } else {
            echo "❌ Error a l'executar: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparant la consulta: " . $conn->error;
    }
}

$conn->close();
?>