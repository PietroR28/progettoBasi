<?php
session_start();
if (!isset($_SESSION['id_utente'])) {
    header("Location: ../Autenticazione/login.php");
    exit;
}

require_once __DIR__ . '/../mamp_xampp.php';

$id_utente = $_SESSION['id_utente'];

// Recupera tutte le reward ottenute dai finanziamenti dell'utente
$stmt = $conn->prepare("
    SELECT 
        f.id_finanziamento,
        f.importo,
        f.data,
        p.nome AS nome_progetto,
        r.descrizione AS descrizione_reward,
        r.foto AS immagine_reward
    FROM finanziamento f
    JOIN progetto p ON f.id_progetto = p.id_progetto
    LEFT JOIN reward r ON f.id_reward = r.id_reward
    WHERE f.id_utente = ?
    ORDER BY f.data DESC
");
$stmt->bind_param("i", $id_utente);
$stmt->execute();
$result = $stmt->get_result();
$rewards = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Le mie Reward</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">💫 Le mie Reward Ricevute</h1>

    <?php if (empty($rewards)): ?>
        <div class="alert alert-info">Non hai ancora ottenuto reward.</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php foreach ($rewards as $r): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($r['immagine_reward'])): ?>
                            <img src="../<?= htmlspecialchars($r['immagine_reward']) ?>" class="card-img-top" alt="Reward">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($r['nome_progetto']) ?></h5>
                            <p class="card-text">
                                <strong>Importo finanziato:</strong> €<?= number_format($r['importo'], 2, ',', '.') ?><br>
                                <strong>Data finanziamento:</strong> <?= date('d/m/Y', strtotime($r['data'])) ?><br>
                                <strong>Reward:</strong><br>
                                <?= nl2br(htmlspecialchars($r['descrizione_reward'] ?? 'Nessuna reward assegnata')) ?>
                            </p>
                        </div>
                        <div class="card-footer text-muted">
                            Finanziamento #<?= $r['id_finanziamento'] ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="finanzia.php" class="btn btn-success">Torna ai progetti da finanziare</a>
        <a href="../Autenticazione/<?php 
        echo ($_SESSION['ruolo'] === 'amministratore') ? 'home_amministratore.php' :
             (($_SESSION['ruolo'] === 'creatore') ? 'home_creatore.php' : 'home_utente.php');
    ?>" class="btn btn-success">Torna alla Home</a>
    </div>
</div>
</body>
</html>
