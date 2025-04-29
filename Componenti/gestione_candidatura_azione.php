<?php
session_start();
if (!isset($_SESSION['id_utente'])) {
    die("Accesso negato.");
}
$id_creatore = $_SESSION['id_utente']; 

require_once __DIR__ . '/../mamp_xampp.php';

$id_candidatura = isset($_POST['id_candidatura']) ? intval($_POST['id_candidatura']) : null;
$azione = $_POST['azione'] ?? null;

if ($id_candidatura <= 0 || !in_array($azione, ['accetta', 'rifiuta'])) {
    $errore = "❌ Dati non validi.";
} else {
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
        $errore = "⛔ Non puoi modificare questa candidatura.";
    } else {
        $nuovo_stato = $azione === 'accetta' ? 'accettata' : 'rifiutata';

        $stmt = $conn->prepare("UPDATE candidatura SET accettazione = ? WHERE id_candidatura = ?");
        $stmt->bind_param("si", $nuovo_stato, $id_candidatura);

        if ($stmt->execute()) {
            $successo = "✅ Candidatura <strong>" . strtoupper($nuovo_stato) . "</strong> correttamente.";
        } else {
            $errore = "❌ Errore nell'aggiornamento: " . $stmt->error;
        }
        $stmt->close();
    }
    $check->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Esito gestione candidatura</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
