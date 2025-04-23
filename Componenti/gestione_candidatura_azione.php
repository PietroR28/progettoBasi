<?php
session_start();
if (!isset($_SESSION['id_utente'])) {
    die("Accesso negato.");
}
$id_creatore = $_SESSION['id_utente']; 

require_once __DIR__ . '/../mamp_xampp.php';

// Recupero e validazione dei dati dal form
$id_candidatura = isset($_POST['id_candidatura']) ? intval($_POST['id_candidatura']) : null;
$azione = $_POST['azione'] ?? null;

// Controllo dei valori ricevuti
if ($id_candidatura <= 0 || !in_array($azione, ['accetta', 'rifiuta'])) {
    die("❌ Dati non validi.");
}

// Verifica che la candidatura appartenga a un progetto del creatore
$check = $conn->prepare("
    SELECT c.id_candidatura
    FROM candidatura c
    JOIN profilo p ON c.id_profilo = p.id_profilo
    JOIN progetto pr ON p.id_progetto = pr.id_progetto
    WHERE c.id_candidatura = ? AND pr.id_utente_creatore = ?
");
$check->bind_param("ii", $id_candidatura, $id_creatore);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    $check->close();
    die("⛔ Non puoi modificare questa candidatura.");
}
$check->close();

// Converte l'azione in valore per il DB
$nuovo_stato = $azione === 'accetta' ? 'accettata' : 'rifiutata';

// Esegue l'update
$stmt = $conn->prepare("UPDATE candidatura SET accettazione = ? WHERE id_candidatura = ?");
$stmt->bind_param("si", $nuovo_stato, $id_candidatura);

if ($stmt->execute()) {
    echo "<p>✅ Candidatura <strong>" . strtoupper($nuovo_stato) . "</strong> correttamente.</p>";
} else {
    echo "<p>❌ Errore nell'aggiornamento: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();

// Link per tornare alla gestione
echo "<p><a href='gestione_candidatura.php'>🔙 Torna a gestione candidature</a></p>";
?>
