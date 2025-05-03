<?php
session_start();
require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = "";

// Inserimento nuovo componente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    
    if (!empty($nome)) {
        $stmt = $conn->prepare("CALL InserisciComponente(?)");
        $stmt->bind_param("s", $nome);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $id_componente = $row['id_componente'] ?? null;
        
            while ($stmt->more_results() && $stmt->next_result()) {;}
            $stmt->close();
        
            // Log evento
            require_once __DIR__ . '/../mongoDB/mongodb.php';
            log_event(
                'COMPONENTE_INSERITO',
                $_SESSION['email'] ?? 'utente_non_autenticato',
                "È stato inserito un nuovo componente: \"$nome\".",
                [
                    'id_componente' => $id_componente,
                    'nome_componente' => $nome,
                    'inserito_da' => $_SESSION['email'] ?? 'non_autenticato'
                ]
            );
        
            $messaggio = "✅ Componente inserito con successo!";
        }
        
    } else {
        $messaggio = "⚠️ Il nome del componente non può essere vuoto.";
    }
}

// Recupero componenti esistenti
$componenti = [];
$result = $conn->query("SELECT nome FROM componente ORDER BY nome ASC");
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
                <label for="nome" class="form-label">Nome componente</label>
                <input type="text" name="nome" id="nome" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-danger">Inserisci</button>
        </form>

        <?php if (!empty($messaggio)): ?>
            <div class="alert alert-info mt-3"><?= $messaggio ?></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($componenti)): ?>
        <div class="card shadow mt-4 p-4">
            <h4 class="mb-3">Componenti già presenti</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($componenti as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nome']) ?></td>
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
</div>
</body>
</html>