<?php
session_start();

if (!isset($_SESSION['email_utente']) || $_SESSION['ruolo_utente'] !== 'creatore') {
    die("Accesso negato.");
}
$email_utente_creatore = $_SESSION['email_utente'];

require_once __DIR__ . '/../mamp_xampp.php';

// Recupero dei dati POST
$data_candidatura = $_POST['data_candidatura'] ?? null;
$nome_profilo = $_POST['nome_profilo'] ?? null;
$azione = $_POST['azione'] ?? null;

if (!$data_candidatura || !$nome_profilo || !in_array($azione, ['accetta', 'rifiuta'])) {
    $errore = "❌ Dati non validi.";
} else {
    $stmt = $conn->prepare("CALL GestioneCandidatura(?, ?, ?, ?)");
    if ($stmt === false) {
        $errore = "❌ Errore nella preparazione della procedura: " . $conn->error;
    } else {
        $stmt->bind_param("ssss", $email_utente_creatore, $nome_profilo, $data_candidatura, $azione);
        if ($stmt->execute()) {
            $successo = "✅ Candidatura <strong>" . strtoupper($azione === 'accetta' ? 'accettata' : 'rifiutata') . "</strong> correttamente.";
        } else {
            $errore = "❌ Errore nell'esecuzione: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Esito gestione candidatura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

<div class="card shadow p-4" style="max-width: 500px; width: 100%;">
    <h4 class="mb-4 text-center">📋 Esito gestione candidatura</h4>

    <?php if (isset($errore)): ?>
        <div class="alert alert-danger text-center"><?= $errore ?></div>
    <?php elseif (isset($successo)): ?>
        <div class="alert alert-success text-center"><?= $successo ?></div>
    <?php endif; ?>

    <div class="text-center mt-3">
        <a href="gestione_candidatura.php" class="btn btn-success"> Torna a gestione candidature</a>
    </div>
</div>

</body>
</html>
