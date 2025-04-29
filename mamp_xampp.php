<?php
// Scegliere "xampp" o "mamp" in base all'ambiente locale
$ambiente = "mamp"; // cambiare solo questa riga quando serve

$host = "localhost";
$user = "root";
$database = "bostarter_db";

// Configurazioni ambiente
switch ($ambiente) {
    case "mamp":
        $password = "root";
        $port = 3306;
        break;

    case "xampp":
        $password = "";
        $port = 3306;
        break;

    default:
        die("Ambiente non supportato.");
}

// Connessione al database
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Errore di connessione al database: " . $conn->connect_error);
}
?>
