<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_competenza = trim($_POST['nome_competenza']); 
    if (!empty($nome_competenza)) {
        $stmt = $conn->prepare("INSERT INTO competenza (nome) VALUES (?)");
        $stmt->bind_param("s", $nome_competenza);
        
        if ($stmt->execute()) {
            require_once __DIR__ . '/../mongoDB/mongodb.php';
            log_event(
                'COMPETENZA_AGGIUNTA',
                $_SESSION['email'],
                "L'amministratore {$_SESSION['email']} ha aggiunto una nuova competenza: \"$nome_competenza\".",
                [
                    'id_utente' => $_SESSION['id_utente'],
                    'ruolo' => $_SESSION['ruolo'],
                    'competenza' => $nome_competenza
                ]
            );
            $messaggio = "✅ Competenza inserita!";
        } else {
            $messaggio = "❌ Errore: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $messaggio = "❌ Devi inserire un nome!";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuova Competenza</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="mb-4 text-center">➕ Inserisci una nuova competenza</h2>

        <?php if ($messaggio): ?>
            <div class="alert <?= strpos($messaggio, '✅') === 0 ? 'alert-success' : 'alert-danger'; ?> text-center">
                <?= htmlspecialchars($messaggio) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="nome_competenza" class="form-label">Nome Competenza:</label>
                <input type="text" id="nome_competenza" name="nome_competenza" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-danger w-100"> Salva</button>
        </form>

        <hr class="my-4">

        <h3 class="text-center mb-3"> Competenze già presenti:</h3>

        <?php 
        $query = "SELECT id_competenza, nome FROM competenza"; 
        $result = $conn->query($query); 
        
        if ($result->num_rows > 0): ?>
            <ul class="list-group">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <li class="list-group-item"><?= htmlspecialchars($row['nome']) ?></li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="text-danger text-center mt-3">❌ Nessuna competenza trovata.</p>
        <?php endif; 
        $conn->close();
        ?>

        <div class="text-center mt-4">
            <a href="../Autenticazione/home_amministratore.php" class="btn btn-success">
                 Torna alla Home
            </a>
        </div>
    </div>
</div>

</body>
</html>
