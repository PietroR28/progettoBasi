<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['id_utente'])) {
    die("Accesso negato: utente non loggato.");
}

$id_utente = $_SESSION['id_utente'];

// Verifica che il profilo sia stato inviato correttamente
if (!isset($_POST['id_profilo'])) {
    die("Errore: ID profilo non ricevuto.");
}

$id_profilo = $_POST['id_profilo'];

$conn = new mysqli("localhost", "root", "", "bostarter_db");
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Esegui la stored procedure
$stmt = $conn->prepare("CALL InserisciCandidatura(?, ?)");
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}

$stmt->bind_param("ii", $id_utente, $id_profilo);

if ($stmt->execute()) {
    echo "<p>✅ Candidatura inviata con successo!</p>";
    echo "<p><a href='candidatura_profilo.php'>🔙 Torna all'elenco profili</a></p>";
} else {
    echo "<p>❌ Errore nell'invio candidatura: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>
