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

require_once __DIR__ . '/../mamp_xampp.php';


// Esegui la stored procedure
$stmt = $conn->prepare("CALL InserisciCandidatura(?, ?)");
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}

$stmt->bind_param("ii", $id_utente, $id_profilo);

if ($stmt->execute()) {
    require_once __DIR__ . '/../mongoDB/mongodb.php';

    // Recupera nome profilo e progetto associato
    $queryInfo = "
        SELECT p.nome AS nome_profilo, pr.id_progetto, pr.nome AS nome_progetto
        FROM profilo p
        JOIN progetto pr ON p.id_progetto = pr.id_progetto
        WHERE p.id_profilo = ?
    ";
    $infoStmt = $conn->prepare($queryInfo);
    $infoStmt->bind_param("i", $id_profilo);
    $infoStmt->execute();
    $info = $infoStmt->get_result()->fetch_assoc();
    $infoStmt->close();

    log_event(
        'CANDIDATURA_INVIATA',
        $_SESSION['email'],
        "L'utente {$_SESSION['email']} ha inviato una candidatura per il profilo \"{$info['nome_profilo']}\" nel progetto \"{$info['nome_progetto']}\".",
        [
            'id_utente' => $_SESSION['id_utente'],
            'id_profilo' => $id_profilo,
            'nome_profilo' => $info['nome_profilo'],
            'id_progetto' => $info['id_progetto'],
            'nome_progetto' => $info['nome_progetto']
        ]
    );

    echo "<p>✅ Candidatura inviata con successo!</p>";
    echo "<p><a href='candidatura_profilo.php'>🔙 Torna all'elenco profili</a></p>";
}
else {
    echo "<p>❌ Errore nell'invio candidatura: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>
