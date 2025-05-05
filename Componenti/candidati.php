<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Verifica sessione
if (!isset($_SESSION['email_utente'])) {
    die("Accesso negato: utente non loggato.");
}

$email_utente = $_SESSION['email_utente'];

// Verifica parametro POST
if (!isset($_POST['nome_profilo'])) {
    die("Errore: nome profilo non ricevuto.");
}

$nome_profilo = $_POST['nome_profilo'];

require_once __DIR__ . '/../mamp_xampp.php';

$esito = null;
$messaggio = "";
$errore = "";

// Esegui la candidatura
$stmt = $conn->prepare("CALL InserisciCandidatura(?, ?)");
if (!$stmt) {
    $errore = "Errore nella preparazione della query: " . $conn->error;
    $esito = false;
} else {
    $stmt->bind_param("ss", $email_utente, $nome_profilo);

    if ($stmt->execute()) {
        // Recupera info del profilo e progetto associato
        $queryInfo = "
            SELECT p.nome_profilo, pr.nome_progetto
            FROM profilo p
            JOIN progetto pr ON p.nome_progetto = pr.nome_progetto
            WHERE p.nome_profilo = ?
        ";
        $infoStmt = $conn->prepare($queryInfo);
        $infoStmt->bind_param("s", $nome_profilo);
        $infoStmt->execute();
        $info = $infoStmt->get_result()->fetch_assoc();
        $infoStmt->close();

        // Log su MongoDB
        try {
            require_once __DIR__ . '/../mongoDB/mongodb.php';

            log_event(
                'CANDIDATURA_INVIATA',
                $_SESSION['email_utente'],
                "L'utente {$_SESSION['email_utente']} ha inviato una candidatura per il profilo \"{$info['nome_profilo']}\" nel progetto \"{$info['nome_progetto']}\".",
                [
                    'email_utente' => $email_utente,
                    'nome_profilo' => $nome_profilo,
                    'nome_progetto' => $info['nome_progetto']
                ]
            );
        } catch (Exception $e) {
            error_log("❌ Errore nel log MongoDB: " . $e->getMessage());
        }

        $messaggio = "✅ Candidatura inviata con successo!";
        $esito = true;
    } else {
        $errore = "❌ Errore nell'invio candidatura: " . $stmt->error;
        $esito = false;
    }

    $stmt->close();
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Conferma Candidatura</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center min-vh-100">

<div class="card shadow p-4" style="max-width: 500px; width: 100%;">
    <h4 class="mb-4 text-center">📩 Risultato candidatura</h4>

    <?php if ($esito): ?>
        <div class="alert alert-success text-center"><?= $messaggio ?></div>
        <div class="text-center mt-3">
            <a href="candidatura_profilo.php" class="btn btn-success"> Torna all'elenco profili</a>
        </div>
    <?php else: ?>
        <div class="alert alert-danger text-center"><?= $errore ?></div>
        <div class="text-center mt-3">
            <a href="candidatura_profilo.php" class="btn btn-success"> Torna indietro</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

