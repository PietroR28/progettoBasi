<?php
session_start();
require_once __DIR__ . '/../mamp_xampp.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$messaggio = "";

// Inserimento nuova competenza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    
    if (!empty($nome)) {
        $stmt = $conn->prepare("CALL InserisciCompetenza(?)");
        $stmt->bind_param("s", $nome);

        if ($stmt->execute()) {
            $messaggio = "✅ Competenza inserita con successo!";
        } else {
            $messaggio = "❌ Errore durante l'inserimento: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $messaggio = "⚠️ Il nome della competenza non può essere vuoto.";
    }
}

// Recupero competenze esistenti
$competenze = [];
$result = $conn->query("SELECT nome FROM competenza ORDER BY nome ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $competenze[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci Competenza</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="mb-4">Inserisci una nuova competenza</h2>
        <form method="post">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome competenza</label>
                <input type="text" name="nome" id="nome" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Inserisci</button>
        </form>

        <?php if (!empty($messaggio)): ?>
            <div class="alert alert-info mt-3"><?= $messaggio ?></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($competenze)): ?>
        <div class="card shadow mt-4 p-4">
            <h4 class="mb-3">Competenze già presenti</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competenze as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nome']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
    <a href="../Autenticazione/home_amministratore.php" class="btn btn-success">
         Torna alla Home
        </a>
    </div>
</div>
</body>
</html>