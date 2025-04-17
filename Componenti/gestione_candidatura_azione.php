<?php
session_start();
if (!isset($_SESSION['id_utente'])) {
    die("Accesso negato.");
}

$conn = new mysqli("localhost", "root", "", "bostarter_db");
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Dati ricevuti dal form
$id_candidatura = $_POST['id_candidatura'] ?? null;
$azione = $_POST['azione'] ?? null;

if (!$id_candidatura || !in_array($azione, ['accetta', 'rifiuta'])) {
    die("Dati non validi.");
}

// Converte l'azione in valore per il DB
$nuovo_stato = $azione === 'accetta' ? 'accettata' : 'rifiutata';

// Esegue l'update
$stmt = $conn->prepare("UPDATE candidatura SET accettazione = ? WHERE id_candidatura = ?");
$stmt->bind_param("si", $nuovo_stato, $id_candidatura);

if ($stmt->execute()) {
    echo "<p>✅ Candidatura " . strtoupper($nuovo_stato) . " correttamente.</p>";
} else {
    echo "<p>❌ Errore nell'aggiornamento: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();

echo "<p><a href='gestione_candidatura.php'>🔙 Torna a gestione candidature</a></p>";
?>
