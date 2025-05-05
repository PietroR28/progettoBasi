<?php
session_start();
require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = "";

// Inserimento nuovo componente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_componente = trim($_POST['nome_componente']);
    $descrizione_componente = trim($_POST['descrizione_componente']);

    if (!empty($nome_componente) && !empty($descrizione_componente)) {
        $stmt = $conn->prepare("CALL InserisciComponente(?, ?)");
        $stmt->bind_param("ss", $nome_componente, $descrizione_componente);

        try {
            if ($stmt->execute()) {
                while ($stmt->more_results() && $stmt->next_result()) {;}
                $stmt->close();

                require_once __DIR__ . '/../mongoDB/mongodb.php';
                log_event(
                    'COMPONENTE_INSERITO',
                    $_SESSION['email_utente'] ?? 'utente_non_autenticato',
                    "È stato inserito un nuovo componente: \"$nome_componente\".",
                    [
                        'nome_componente' => $nome_componente,
                        'inserito_da' => $_SESSION['email_utente'] ?? 'non_autenticato'
                    ]
                );

                $messaggio = "✅ Componente inserito con successo!";
            }
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'Componente già esistente') !== false) {
                $messaggio = "⚠️ Esiste già un componente con questo nome.";
            } else {
                $messaggio = "❌ Errore imprevisto: " . $e->getMessage();
            }
        }
    } else {
        $messaggio = "⚠️ Compila tutti i campi correttamente.";
    }
}

// Recupero componenti esistenti
$componenti = [];
$result = $conn->query("SELECT nome_componente, descrizione_componente FROM componente ORDER BY nome_componente ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $componenti[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci Componente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="mb-4">Inserisci un nuovo componente</h2>
        <form method="post">
            <div class="mb-3">
                <label for="nome_componente" class="form-label">Nome componente</label>
                <input type="text" name="nome_componente" id="nome_componente" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="descrizione_componente" class="form-label">Descrizione</label>
                <textarea name="descrizione_componente" id="descrizione_componente" class="form-control" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Inserisci</button>
        </form>

        <?php if (!empty($messaggio)): ?>
            <div class="alert alert-info mt-3 text-center fw-semibold"><?= $messaggio ?></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($componenti)): ?>
        <div class="card shadow mt-4 p-4">
            <h4 class="mb-3">Componenti già presenti</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrizione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($componenti as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nome_componente']) ?></td>
                            <td><?= htmlspecialchars($c['descrizione_componente']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="../Autenticazione/home_creatore.php" class="btn btn-success">
            Torna alla Home
        </a>
    </div>
</div>
</body>
</html>
